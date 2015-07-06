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
        $body .= elgg_view_form("search_advanced/search", array("action" => "search", "method" => "GET", "disable_security" => true), array());
    }
    
    $body .= elgg_echo('search:no_query');
    if(!elgg_is_xhr()){
        $layout = elgg_view_layout('one_sidebar', array('content' => $body));
        $body = elgg_view_page($title, $layout);
    }
    echo $body;
    return;
}

$entity_subtype = get_input('entity_subtype', ELGG_ENTITIES_ANY_VALUE);
$owner_guid = get_input('owner_guid', ELGG_ENTITIES_ANY_VALUE);
$container_guid = get_input('container_guid', ELGG_ENTITIES_ANY_VALUE);
$friends = get_input('friends', ELGG_ENTITIES_ANY_VALUE);
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

// set up search params
$params = array(
    'query' => $query,
    'offset' => $offset,
    'limit' => $limit,
    'sort' => $sort,
    'order' => $order,
    'search_type' => $search_type,
    'type' => $entity_type,
    'subtype' => $entity_subtype,
//  'tag_type' => $tag_type,
    'owner_guid' => $owner_guid,
    'container_guid' => $container_guid,
//  'friends' => $friends
    'pagination' => ($search_type == 'all') ? FALSE : TRUE,
    'profile_filter' => $profile_filter,
);

$types = get_registered_entity_types();
$types['object'] = array_merge($types['object'], elgg_trigger_plugin_hook('search_types', 'get_types', $params, array()));

$results = ESInterface::get()->search($query);

$body = "";
$body .= "<h2>" . elgg_echo('elasticsearch:nr_results', array($results['total'], "\"$display_query\"")) . "</h2>";

foreach ($results['hits'] as $result) {
    $body .= print_r($result, true) . "<br /><br />";
}

if(elgg_is_xhr()){
    echo $body;
} else {
    $title = elgg_echo('elasticsearch:results', array("\"$display_query\""));
    $content = elgg_view_layout('two_column_left_sidebar', array('content' => $body));
    
    echo elgg_view_page($title, $content);
}