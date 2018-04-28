<?php
/**
 * Plugin settings for Elasticsearch
 */
$plugin = $vars["entity"];

$title = elgg_echo('elasticsearch:settings:management');

if (function_exists("subsite_manager_on_subsite") && subsite_manager_on_subsite()) {
    return;
}

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
