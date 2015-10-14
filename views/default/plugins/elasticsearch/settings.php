<?php
/**
 * Plugin settings for Elasticsearch
 */
$plugin = $vars["entity"];

$noyes_options = array(
    "no" => elgg_echo("option:no"),
    "yes" => elgg_echo("option:yes")
);

$content = "<div>";
$content .= elgg_echo("elasticsearch:settings:enabled");
$content .= elgg_view("input/dropdown", array("name" => "params[is_enabled]", "options_values" => $noyes_options, "value" => $plugin->is_enabled, "class" => "mls"));
$content .= "</div>";
echo elgg_view_module("inline", elgg_echo("elasticsearch:settings:title"), $content);

$title = elgg_echo('elasticsearch:settings:profile_fields');
$content = "";
$content .= "<div class='search-advanced-settings-profile-fields'>";
$content .= elgg_view("elasticsearch/settings/user_profile_fields", $vars);
$content .= "</div>";
echo elgg_view_module("inline", $title, $content);
