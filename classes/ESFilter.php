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
            $return[$field] = strip_tags($object->$field);
        }

        return $return;
    }

    public function filterObject($object) {
        global $CONFIG;
        $dbprefix = $CONFIG->dbprefix;

        $subtype = get_subtype_from_id($object->subtype);

        // do not index specific types of content
        if (in_array($subtype, array('messages','plugin','widget','custom_profile_field','custom_profile_field_category','reported_content','custom_group_field','custom_profile_type','gruop_widget','multi_dashboard'))) {
            return false;
        }

        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['title'] = $object->title;
        $return['description'] = elgg_strip_tags($object->description); // remove HTML

        $metastring_id = get_metastring_id('tags');
        if (!$metastring_id) {
            throw new Exception("No metastring id for tags found");
        }

        $metadata = get_data("SELECT md.access_id, v.string AS value FROM {$dbprefix}metadata md JOIN {$dbprefix}metastrings v ON md.value_id = v.id WHERE md.entity_guid = {$object->guid} AND md.name_id = {$metastring_id} AND md.enabled = 'yes'");
        if (count($metadata) > 0) {
            $return['tags'] = array();
            foreach ($metadata as $item) {
                if ($item->value) {
                    $return['tags'][] = $item->value;
                }
            }
        }

        return $return;
    }

    public function filterUser($object) {
        global $CONFIG;
        $dbprefix = $CONFIG->dbprefix;

        if ($object->banned == "yes") {
            return false;
        }

        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['name'] = $object->name;
        $return['username'] = $object->username;
        $return['email'] = $object->email;
        $return['language'] = $object->language;

        $return['metadata'] = array();

        $metadata = get_data("SELECT md.access_id, n.string AS name, v.string AS value FROM {$dbprefix}metadata md JOIN {$dbprefix}metastrings n ON md.name_id = n.id JOIN {$dbprefix}metastrings v ON md.value_id = v.id WHERE md.entity_guid = {$object->guid} AND md.enabled = 'yes'");
        foreach ($metadata as $item) {
            $return['metadata'][] = array(
                'access_id' => $item->access_id,
                'name' => $item->name,
                'value' => elgg_strip_tags($item->value)
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

        $return['title'] = $object->name;
        $return['description'] = elgg_strip_tags($return['description']); // remove HTML
        return $return;
    }

    public function filterSite($object) {
        $return = array();
        foreach (self::$entity_fields as $field) {
            $return[$field] = $object->$field;
        }

        $return['title'] = $object->title;
        $return['description'] = elgg_strip_tags($return['description']); // remove HTML
        $return['url'] = $object->url;
        return $return;
    }

    public function filterOther($object) {
        return false;
    }

}