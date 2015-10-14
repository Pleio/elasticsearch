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

        $metadata = elgg_get_metadata(array(
            'site_guids' => false,
            'guid' => $object->guid,
            'limit' => false,
            'metadata_name' => 'tags'
        ));

        if (count($metadata) > 0) {
            $return['tags'] = array();
            foreach ($metadata as $item) {
                $return['tags'][] = $item->value;
            }
        }

        return $return;
    }

    public function filterUser($object) {

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

        $metadata = elgg_get_metadata(array(
            'site_guids' => false,
            'guid' => $object->guid,
            'limit' => false
        ));

        foreach ($metadata as $item) {
            if ($item->access_id == ACCESS_PRIVATE) {
                continue;
            }

            $name = $item->name;
            $value = elgg_strip_tags($item->value);

            if (array_key_exists($name, $return)) {
                if (!is_array($return[$name])) {
                    $return[$name] = array($value);
                }
                $return[$name][] = $value;
            } else {
                $return[$name] = $value;
            }
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