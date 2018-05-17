<?php

require_once(dirname(__FILE__) . "/../../vendor/autoload.php");
require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/events.php");

function elasticsearch_init() {
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

    elgg_register_event_handler('create', 'member', 'elasticsearch_update_relationship_event');
    elgg_register_event_handler('delete', 'member', 'elasticsearch_update_relationship_event');
    elgg_register_event_handler('create', 'member_of_site', 'elasticsearch_update_relationship_event');
    elgg_register_event_handler('delete', 'member_of_site', 'elasticsearch_update_relationship_event');

    elgg_register_event_handler('shutdown' ,'system', 'elasticsearch_system_shutdown');

    elgg_register_action("elasticsearch/settings/save", dirname(__FILE__) . "/actions/plugins/settings/save.php", "admin");

    elgg_register_action("elasticsearch/reset_index", dirname(__FILE__) . "/actions/reset_index.php", "admin");
    elgg_register_action("elasticsearch/sync_all", dirname(__FILE__) . "/actions/sync_all.php", "admin");

    elgg_extend_view('css/elgg', 'search/css/site');
    elgg_extend_view('js/elgg', 'search/js/site');

    elgg_extend_view('page/elements/header', 'elasticsearch/header');

    elgg_register_widget_type("search", elgg_echo("search"), elgg_echo("search"), "profile,dashboard,index,groups", true);

    elgg_register_page_handler('search', 'elasticsearch_search_page_handler');
    elgg_register_page_handler('search_advanced', 'elasticsearch_search_page_handler');

    elgg_register_plugin_hook_handler("route", "groups", "elasticsearch_groups_hook", 100);

    elgg_unregister_plugin_hook_handler("search", "object", "search_objects_hook");
	elgg_unregister_plugin_hook_handler("search", "user", "search_users_hook");
    elgg_unregister_plugin_hook_handler("search", "group", "search_groups_hook");
    elgg_unregister_plugin_hook_handler("search", "tags", "search_tags_hook");
    elgg_unregister_plugin_hook_handler("search", "comments", "search_comments_hook");

    elgg_unregister_plugin_hook_handler("search", "object", "search_advanced_objects_hook");
    elgg_unregister_plugin_hook_handler("search", "user", "search_advanced_users_hook");
    elgg_unregister_plugin_hook_handler("search", "group", "search_advanced_groups_hook");
    elgg_unregister_plugin_hook_handler("search", "tags", "search_advanced_tags_hook");
    elgg_unregister_plugin_hook_handler("search", "comments", "search_advanced_comments_hook");

    elgg_register_plugin_hook_handler("search", "object", "elasticsearch_search_object_hook_handler");
    elgg_register_plugin_hook_handler("search", "user", "elasticsearch_search_user_hook_handler");
    elgg_register_plugin_hook_handler("search", "group", "elasticsearch_search_group_hook_handler");
    elgg_register_plugin_hook_handler("search", "tags", "elasticsearch_search_tags_hook_handler");
    elgg_register_plugin_hook_handler("search", "comments", "elasticsearch_search_comments_hook_handler");

    if (function_exists('pleio_register_console_handler')) {
        pleio_register_console_handler('es:index:reset', 'Reset the configured Elasticsearch index.', 'elasticsearch_console_index_reset');
        pleio_register_console_handler('es:sync:all', 'Synchronise all entities to Elasticsearch.', 'elasticsearch_console_sync_all');
    }
}

elgg_register_event_handler("init", "system", "elasticsearch_init");

function elasticsearch_search_object_hook_handler($hook, $type, $return_value, $params) {
    $limit = $params["limit"] ? $params["limit"] : 10;
    $offset = $params["offset"] ? $params["offset"] : 0;
    $query = $params["query"];

    $results = ESInterface::get()->search(
        $params["query"],
        SEARCH_TAGS,
        "object",
        [],
        $params["limit"],
        $params["offset"],
        null,
        null,
        $params["container_guid"],
        null
    );

    if ($params["type"]) {
        if ($params["subtype"]) {
            if (array_key_exists($params["subtype"], $results["count_per_subtype"])) {
                $count = $results["count_per_subtype"][$params["subtype"]];
            }
        } else {

            exit();
            if (array_key_exists($params["type"], $results["count_per_type"])) {
                $count = $results["count_per_subtype"][$params["type"]];
            }
        }
    }

    return [
        "count" => $count,
        "entities" => $results["hits"]
    ];
}

function elasticsearch_search_user_hook_handler($hook, $type, $return_value, $params) {
    $limit = $params["limit"] ? $params["limit"] : 10;
    $offset = $params["offset"] ? $params["offset"] : 0;
    $query = $params["query"];

    $profile_filter = [];
    foreach ($params["profile_filter"] as $key => $value) {
        if ($key && $value) {
            $profile_filter[$key] = $value;
        }
    }

    $results = ESInterface::get()->search(
        $params["query"],
        SEARCH_DEFAULT,
        "user",
        [],
        $params["limit"],
        $params["offset"],
        null,
        null,
        null,
        $profile_filter
    );

    return [
        "count" => $results["count"],
        "entities" => $results["hits"]
    ];
}

function elasticsearch_search_group_hook_handler($hook, $type, $return_value, $params) {
    $limit = $params["limit"] ? $params["limit"] : 10;
    $offset = $params["offset"] ? $params["offset"] : 0;
    $query = $params["query"];

    $results = ESInterface::get()->search(
        $query,
        SEARCH_TAGS,
        "group"
    );

    return [
        "count" => $results["count"],
        "entities" => $results["hits"]
    ];
}

function elasticsearch_search_comments_hook_handler($hook, $type, $return_value, $params) {
    $limit = $params["limit"] ? $params["limit"] : 10;
    $offset = $params["offset"] ? $params["offset"] : 0;
    $query = $params["query"];

    return [
        "count" => 0,
        "entities" => []
    ];
}

function elasticsearch_search_tags_hook_handler($hook, $type, $return_value, $params) {
    $limit = $params["limit"] ? $params["limit"] : 10;
    $offset = $params["offset"] ? $params["offset"] : 0;
    $query = $params["query"];

    $results = ESInterface::get()->search(
        $params["query"],
        SEARCH_TAGS,
        "object",
        [],
        $params["limit"],
        $params["offset"]
    );

    return [
        "count" => $results["count"],
        "entities" => $results["entities"]
    ];
}

function elasticsearch_groups_hook($hook_name, $entity_type, $return_value, $params) {
    $base_dir = dirname(__FILE__) . '/pages/groups';
    $page = elgg_extract("segments", $return_value);
    $tag = get_input("tag");

    switch ($page[0]) {
        case "search":
            forward("/search?q=\"${tag}\"&entity_type=group&search_type=tags");
            return false;
            break;
    }
}

function elasticsearch_search_page_handler($page) {
    $base_dir = dirname(__FILE__) . '/pages/search';

    switch ($page[0]) {
        case "autocomplete":
            include_once("$base_dir/autocomplete.php");
            return true;
    }

    include_once("$base_dir/index.php");
    return true;
}