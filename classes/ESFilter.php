<?php

class ESFilter {

    static $entity_fields = array(
        'guid',
        'type',
        'subtype',
        'owner_guid',
        'site_guid',
        'container_guid',
        'access_id',
        'time_created',
        'time_updated',
        'last_action',
        'enabled'
    );

    static $annotation_fields = array(
        'id',
        'type',
        'entity_guid',
        'name',
        'value',
        'owner_guid',
        'site_guid',
        'access_id',
        'time_created'
    );

    public function __construct() {
        $types = get_registered_entity_types();
        $custom_types = elgg_trigger_plugin_hook('search_types', 'get_types', $params, array());
        $types['object'] = array_merge($types['object'], $custom_types);
        $this->types = $types;
    }

    public function apply($object) {
        switch ($object->type) {
            case 'annotation':
                return $this->filterAnnotation($object);
            case 'object':
                return $this->filterObject($object);
            case 'user':
                return $this->filterUser($object);
            case 'group':
                return $this->filterGroup($object);
            case 'site':
                return $this->filterSite($object);
            default:
                return $this->filterOther($object);
        }
    }

    public function filterAnnotation($object) {
        if (!in_array($object->name, array('group_topic_post', 'generic_comment'))) {
            return false;
        }

        if ($object->value_type != "text") {
            return false;
        }

        $return = array();
        foreach (self::$annotation_fields as $field) {
            $return[$field] = html_entity_decode(strip_tags($object->$field));
        }

        return $return;
    }

    public function filterObject($object) {
        global $CONFIG;
        $dbprefix = $CONFIG->dbprefix;

        $subtype = get_subtype_from_id($object->subtype);

        // do not index specific types of content
        if (in_array($subtype, array('messages','plugin','widget','custom_profile_field','custom_profile_field_category','reported_content','custom_group_field','custom_profile_type','gruop_widget','multi_dashboard','comment', 'answer', 'menu_builder_menu_item'))) {
            return false;
        }

        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        if ($subtype == "page_top") {
            $return["subtype"] = get_subtype_id("object", "page");
        }

        $return['title'] = html_entity_decode($object->title);
        $return['description'] = html_entity_decode(elgg_strip_tags($object->description)); // remove HTML
        $return['tags'] = $this->getTags($object);

        if (in_array($subtype, array('question', 'cafe', 'news', 'blog'))) {
            if ($subtype == "question") {
                $comment_subtype = "answer";
            } else {
                $comment_subtype = "comment";
            }

            $return['comments'] = $this->getComments($object, $comment_subtype);
        }

        return $return;
    }

    public function filterUser($object) {
        global $CONFIG;
        $dbprefix = $CONFIG->dbprefix;

        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['name'] = html_entity_decode($object->name);
        $return['username'] = $object->username;
        $return['email'] = $object->email;
        $return['language'] = $object->language;

        $return['metadata'] = array();

        $metadata = get_data("SELECT md.access_id, n.string AS name, v.string AS value FROM {$dbprefix}metadata md JOIN {$dbprefix}metastrings n ON md.name_id = n.id JOIN {$dbprefix}metastrings v ON md.value_id = v.id WHERE md.entity_guid = {$object->guid} AND md.enabled = 'yes'");
        foreach ($metadata as $item) {
            $return['metadata'][] = array(
                'access_id' => $item->access_id,
                'name' => $item->name,
                'value' => html_entity_decode(elgg_strip_tags($item->value))
            );
        }

        $sites = get_data("SELECT e.guid FROM {$dbprefix}entity_relationships er LEFT JOIN {$dbprefix}entities e ON er.guid_two = e.guid WHERE relationship = 'member_of_site' AND guid_one = {$object->guid} AND e.type = 'site'");
        $return['site_guid'] = array();
        foreach ($sites as $site) {
            $return['site_guid'][] = $site->guid;
        }

        $groups = get_data("SELECT e.guid FROM {$dbprefix}entity_relationships er LEFT JOIN {$dbprefix}entities e ON er.guid_two = e.guid WHERE relationship = 'member' AND guid_one = {$object->guid} AND e.type = 'group'");
        $return['container_guid'] = array();
        foreach($groups as $group) {
            $return['container_guid'][] = $group->guid;
        }

        return $return;
    }

    public function filterGroup($object) {
        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['title'] = html_entity_decode($object->name);
        $return['description'] = html_entity_decode(elgg_strip_tags($return['description'])); // remove HTML
        $return['tags'] = $this->getTags($object, "interests");

        return $return;
    }

    public function filterSite($object) {
        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['title'] = html_entity_decode($object->title);
        $return['description'] = html_entity_decode(elgg_strip_tags($return['description'])); // remove HTML
        $return['url'] = $object->url;
        return $return;
    }

    public function filterOther($object) {
        return false;
    }

    private function getTags($object, $metastring = "tags") {
        $dbprefix = elgg_get_config("dbprefix");

        $metastring_id = (int) get_metastring_id($metastring);
        if (!$metastring_id) {
            return [];
        }

        $results = get_data("SELECT md.access_id, v.string AS value FROM {$dbprefix}metadata md JOIN {$dbprefix}metastrings v ON md.value_id = v.id WHERE md.entity_guid = {$object->guid} AND md.name_id = {$metastring_id} AND md.enabled = 'yes'");
        
        $return = [];
        foreach ($results as $result) {
            if (!$result->value) {
                continue;
            }

            $return[] = $result->value;
        }

        return $return;
    }

    private function getComments($object, $subtype = "comment") {
        $options = array(
            "type" => "object",
            "subtype" => $subtype,
            "container_guid" => $object->guid,
            "site_guids" => null,
            "limit" => false
        );

        $results = elgg_get_entities($options);

        if (!$results) {
            return [];
        }

        $return = [];
        foreach ($results as $result) {
            $return[] = html_entity_decode(elgg_strip_tags($result->description));
        }

        return $return;
    }
}