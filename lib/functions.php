<?php

function elasticsearch_get_subtypes() {
    $types = get_registered_entity_types();
    $types['object'] = array_merge($types['object'], elgg_trigger_plugin_hook('search_types', 'get_types', $params, array()));
    return $types;
}

function elasticsearch_get_view($object) {
    if (elgg_view_exists('search/' . $object->type . '/' . $object->subtype)) {
        return 'search/' . $object->type . '/' . $object->subtype;
    } else {
        return 'search/entity';
    }
}