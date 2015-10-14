<?php

// Search supports RSS
global $autofeed;
$autofeed = true;

// $search_type == all || entities || trigger plugin hook
$search_type = get_input('search_type', 'all');

// @todo there is a bug in get_input that makes variables have slashes sometimes.
// @todo is there an example query to demonstrate ^
// XSS protection is more important that searching for HTML.

$query = stripslashes(get_input('q', get_input('tag', '')));

$profile_filter = get_input('search_advanced_profile_fields');
$entity_type = get_input('entity_type', ELGG_ENTITIES_ANY_VALUE);
$entity_subtype = get_input('entity_subtype', ELGG_ENTITIES_ANY_VALUE);
$owner_guid = get_input('owner_guid', ELGG_ENTITIES_ANY_VALUE);
$container_guid = get_input('container_guid', ELGG_ENTITIES_ANY_VALUE);
$friends = get_input('friends', ELGG_ENTITIES_ANY_VALUE);

$profile_fields = get_input('elasticsearch_profile_fields');
if ($entity_type == "user" && $profile_fields) {
    $execute_query = $query;
    foreach ($profile_fields as $field => $value) {
        $execute_query .= " AND " . $field . ":\"" . $value . "\"";
    }
} else {
    $execute_query = $query;
}

if ($search_type == "comments") {
    $types = "annotation";
    $execute_query .= " AND name:(generic_comment OR group_topic_post)";
} else {
    $types = $entity_type;
}

if ($entity_subtype) {
    $subtypes = $entity_subtype;
}

$sort = get_input('sort');
switch ($sort) {
    case 'relevance':
    case 'created':
    case 'updated':
    case 'action_on':
    case 'alpha':
        break;

    default:
        $sort = 'relevance';
        break;
}

$order = get_input('sort', 'desc');
if ($order != 'asc' && $order != 'desc') {
    $order = 'desc';
}

$limit = get_input('limit', 10);
if ($limit > 50 | $limit < 1) {
    $limit = 10;
}

$offset = get_input('offset', 0);

// @todo - create function for sanitization of strings for display in 1.8
// encode <,>,&, quotes and characters above 127
if (function_exists('mb_convert_encoding')) {
    $display_query = mb_convert_encoding($query, 'HTML-ENTITIES', 'UTF-8');
} else {
    // if no mbstring extension, we just strip characters
    $display_query = preg_replace("/[^\x01-\x7F]/", "", $query);
}
$display_query = htmlspecialchars($display_query, ENT_QUOTES, 'UTF-8', false);

// check that we have an actual query
if (!$query && !((count($profile_filter) > 0) && $entity_type == "user")) {
    $title = sprintf(elgg_echo('search:results'), "\"$display_query\"");

    $body  = elgg_view_title(elgg_echo('search:search_error'));
    if(!elgg_is_xhr()){
        $body .= elgg_view_form("elasticsearch/search", array("action" => "search", "method" => "GET", "disable_security" => true), array());
    }

    $body .= elgg_echo('search:no_query');
    if(!elgg_is_xhr()){
        $layout = elgg_view_layout('one_sidebar', array('content' => $body));
        $body = elgg_view_page($title, $layout);
    }
    echo $body;
    return;
}

// @todo refactor: Not a very nice solution. Best solution would maybe be to reuse container_guid value of user in Elasticsearch.
$container = get_entity($container_guid);
if (isset($container) && $container instanceof ElggGroup) {
    $results = ESInterface::get()->search($query, $types, $subtypes, 10000, 0, $sort, $order);

    foreach ($results['hits'] as $key => $hit) {
        $remove = false;

        if ($hit->type == "user") {
            if (!check_entity_relationship($hit->guid, "member", $container->guid)) {
                $remove = true;
            }
        } else {
            if ($hit->container_guid != $container->guid) {
                $remove = true;
            }
        }

        if ($remove === true) {
            unset($results['hits'][$key]);
            $results['count'] -= 1;
            $results['count_per_type'][$hit->type] -= 1;

            if ($hit->type == "object") {
                $subtype = get_subtype_from_id($hit->subtype);
                $results['count_per_subtype'][$subtype] -= 1;
            }
        }
    }

    foreach ($results['count_per_type'] as $key => $count) {
        if ($count === 0) {
            unset($results['count_per_type'][$key]);
        }
    }

    foreach ($results['count_per_subtype'] as $key => $count) {
        if ($count === 0) {
            unset($results['count_per_subtype'][$key]);
        }
    }

    $results['hits'] = array_slice($results['hits'], $offset, $limit);

} else {
    $results = ESInterface::get()->search($execute_query, $types, $subtypes, $limit, $offset, $sort, $order);
}

$body = elgg_view_title(elgg_echo('elasticsearch:nr_results', array($results['count'], "\"$display_query\"")));

// add search form
if(!elgg_is_xhr()){
    $body .= elgg_view_form("elasticsearch/search", array("action" => "search", "method" => "GET", "disable_security" => true), array(
        'query' => $query,
        'search_type' => $search_type,
        'type' => $entity_type,
        'subtype' => $entity_subtype,
        'container_guid' => $container_guid
    ));
}

$body .= elgg_view('elasticsearch/search/list', array(
    'results' => $results,
    'params' => array(
        'limit' => $limit,
        'offset' => $offset,
        'query' => $query,
        'search_type' => $search_type,
        'type' => $entity_type,
        'subtype' => $entity_subtype,
        'container_guid' => $container_guid
    )
));

$data = htmlspecialchars(http_build_query(array(
    'q' => $query,
    'search_type' => 'all'
)));
$url = elgg_get_site_url() . "search?$data";
$menu_item = new ElggMenuItem('all', elgg_echo('all'), $url);
elgg_register_menu_item('page', $menu_item);

$types = get_registered_entity_types();
$custom_types = elgg_trigger_plugin_hook('search_types', 'get_types', $params, array());

foreach ($types as $type => $subtypes) {
    // @todo when using index table, can include result counts on each of these.
    if (is_array($subtypes) && count($subtypes)) {
        foreach ($subtypes as $subtype) {
            $label = "item:$type:$subtype";

            $data = htmlspecialchars(http_build_query(array(
                'q' => $query,
                'entity_subtype' => $subtype,
                'entity_type' => $type,
                'owner_guid' => $owner_guid,
                'search_type' => 'entities'
            )));

            $url = elgg_get_site_url()."search?$data";

            $caption = elgg_echo($label);
            if (array_key_exists($subtype, $results['count_per_subtype'])) {
                $caption .= " <span class='elgg-quiet'>[" . $results['count_per_subtype'][$subtype] . "]</span>";
            }

            $menu_item = new ElggMenuItem($label, $caption, $url);
            elgg_register_menu_item('page', $menu_item);
        }
    } else {
        $label = "item:$type";

        $data = htmlspecialchars(http_build_query(array(
            'q' => $query,
            'entity_type' => $type,
            'owner_guid' => $owner_guid,
            'search_type' => 'entities'
        )));

        $url = elgg_get_site_url() . "search?$data";

        $caption = elgg_echo($label);
        if (array_key_exists($type, $results['count_per_type'])) {
            $caption .= " <span class='elgg-quiet'>[" . $results['count_per_type'][$type] . "]</span>";
        }

        $menu_item = new ElggMenuItem($label, $caption, $url);
        elgg_register_menu_item('page', $menu_item);
    }
}

// add sidebar for custom searches
foreach ($custom_types as $type) {
    $label = "search_types:$type";

    $data = htmlspecialchars(http_build_query(array(
        'q' => $query,
        'search_type' => $type,
    )));

    $url = elgg_get_site_url()."search?$data";

    $caption = elgg_echo($label);
    if ($type == "comments" && array_key_exists('annotation', $results['count_per_type'])) {
        $caption .= " <span class='elgg-quiet'>[" . $results['count_per_type']['annotation'] . "]</span>";
    }

    $menu_item = new ElggMenuItem($label, $caption, $url);
    elgg_register_menu_item('page', $menu_item);
}

if(elgg_is_xhr()){
    echo $body;
} else {
    $title = elgg_echo('elasticsearch:results', array("\"$display_query\""));
    $content = elgg_view_layout('two_column_left_sidebar', array('content' => $body));

    echo elgg_view_page($title, $content);
}