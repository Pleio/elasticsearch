<?php

function elasticsearch_get_subtypes() {
    $types = get_registered_entity_types();
    $types['object'] = array_merge($types['object'], elgg_trigger_plugin_hook('search_types', 'get_types', $params, array()));
    return $types;
}