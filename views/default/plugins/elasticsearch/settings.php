<?php
/**
 * Plugin settings for Elasticsearch
 */
$plugin = $vars["entity"];

$noyes_options = array(
    "no" => elgg_echo("option:no"),
    "yes" => elgg_echo("option:yes")
);

$title = elgg_echo('elasticsearch:settings:profile_fields');
$content = "";
$content .= "<div class='search-advanced-settings-profile-fields'>";
$content .= elgg_view("elasticsearch/settings/user_profile_fields", $vars);
$content .= "</div>";
echo elgg_view_module("inline", $title, $content);
