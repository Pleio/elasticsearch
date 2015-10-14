<?php

if ($vars["search_type"]) {
    echo elgg_view("input/hidden", array("name" => "search_type", "value" => $vars["search_type"]));
}

if ($vars["type"]) {
    echo elgg_view("input/hidden", array("name" => "entity_type", "value" => $vars["type"]));
}

if ($vars["subtype"]) {
    echo elgg_view("input/hidden", array("name" => "entity_subtype", "value" => $vars["subtype"]));
}

if($vars["container_guid"]){
    echo elgg_view("input/hidden", array("name" => "container_guid", "value" => $vars["container_guid"]));
}

echo elgg_view("input/text", array("name" => "q", "value" => $vars["query"] , "class" => "ui-front"));
echo elgg_view("input/submit", array("value" => elgg_echo("submit"), "class" => "hidden"));

if (elgg_extract("type", $vars, false) === "user") {
    echo elgg_view("elasticsearch/search/user", $vars);
}