<?php

class ESFilter {

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
        if (in_array($object->name, array('groupforumtopic'))) {
            return $object;
        } else {
            return false;
        }
    }

    public function filterObject($object) {
        $subtype = get_subtype_from_id($object->subtype);

        //if (in_array($subtype, $this->types['object'])) {
        if (!in_array($subtype, array('messages'))) {
            return $object;
        } else {
            return false;
        }
    }

    public function filterUser($object) {
        return $object;
    }

    public function filterGroup($object) {
        return $object;
    }

    public function filterSite($object) {
        return $object;
    }

    public function filterOther($object) {
        return false;
    }

}