<?php
/**
 * Plugin settings for Elasticsearch
 */
$plugin = $vars["entity"];

$title = elgg_echo('elasticsearch:settings:management');

$content = elgg_view("output/url", [
    "href" => "/action/elasticsearch/reset_index",
    "text" => elgg_echo("elasticsearch:reset_index"),
    "class" => "elgg-button elgg-button-submit",
    "is_action" => true
]);

$content .= elgg_view("output/url", [
    "href" => "/action/elasticsearch/sync_all",
    "text" => elgg_echo("elasticsearch:sync_all"),
    "class" => "elgg-button elgg-button-submit",
    "is_action" => true
]);

echo elgg_view_module("inline", $title, $content);

$title = elgg_echo('elasticsearch:settings:profile_fields');
$content = "";
$content .= "<div class='search-advanced-settings-profile-fields'>";
$content .= elgg_view("elasticsearch/settings/user_profile_fields", $vars);
$content .= "</div>";
echo elgg_view_module("inline", $title, $content);
