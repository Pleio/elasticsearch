<?php

require_once(dirname(__FILE__) . "/../../vendor/autoload.php");
require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/events.php");

function elasticsearch_init() {
    elgg_register_page_handler('search', 'elasticsearch_search_page_handler');
    elgg_register_page_handler('search_advanced', 'elasticsearch_search_page_handler');

    elgg_register_event_handler('create', 'user', 'elasticsearch_create_event');
    elgg_register_event_handler('create', 'group', 'elasticsearch_create_event');
    elgg_register_event_handler('create', 'object', 'elasticsearch_create_event');
    elgg_register_event_handler('create', 'site', 'elasticsearch_create_event');
    elgg_register_event_handler('create', 'annotation', 'elasticsearch_create_event');

    elgg_register_event_handler('update', 'user', 'elasticsearch_update_event');
    elgg_register_event_handler('update', 'group', 'elasticsearch_update_event');
    elgg_register_event_handler('update', 'object', 'elasticsearch_update_event');
    elgg_register_event_handler('update', 'site', 'elasticsearch_update_event');
    elgg_register_event_handler('update', 'annotation', 'elasticsearch_update_event');

    elgg_register_event_handler('delete', 'user', 'elasticsearch_delete_event');
    elgg_register_event_handler('delete', 'group', 'elasticsearch_delete_event');
    elgg_register_event_handler('delete', 'object', 'elasticsearch_delete_event');
    elgg_register_event_handler('delete', 'site', 'elasticsearch_delete_event');
    elgg_register_event_handler('delete', 'annotation', 'elasticsearch_delete_event');

    elgg_register_event_handler('enable', 'user', 'elasticsearch_enable_event');
    elgg_register_event_handler('enable', 'group', 'elasticsearch_enable_event');
    elgg_register_event_handler('enable', 'object', 'elasticsearch_enable_event');
    elgg_register_event_handler('enable', 'site', 'elasticsearch_enable_event');
    elgg_register_event_handler('enable', 'annotation', 'elasticsearch_enable_event');

    elgg_register_event_handler('disable', 'user', 'elasticsearch_disable_event');
    elgg_register_event_handler('disable', 'group', 'elasticsearch_disable_event');
    elgg_register_event_handler('disable', 'object', 'elasticsearch_disable_event');
    elgg_register_event_handler('disable', 'site', 'elasticsearch_disable_event');
    elgg_register_event_handler('disable', 'annotation', 'elasticsearch_disable_event');
}

elgg_register_event_handler("init", "system", "elasticsearch_init");

function elasticsearch_search_page_handler($page) {
    switch ($page[0]) {
        case "autocomplete":
            // @todo: build
            return true;
    }

    $base_dir = dirname(__FILE__) . '/pages/search';
    include_once("$base_dir/index.php");
}