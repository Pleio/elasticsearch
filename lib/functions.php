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

function elasticsearch_console_sync_all() {
    global $CONFIG;
    $CONFIG->memcache = false;

    ini_set('memory_limit', '1024M');
    set_time_limit(0);

    $ia = elgg_set_ignore_access(true);

    $interface = ESInterface::get();
    $bulksync = new ESBulkSync($interface);
    $bulksync->sync();

    elgg_set_ignore_access($ia);
}

function elasticsearch_console_index_reset() {
    return true;
}