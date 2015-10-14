<?php
/**
 * Default view for an entity returned in a search
 *
 * Display largely controlled by a set of overrideable volatile data:
 *   - search_icon (defaults to entity icon)
 *   - search_matched_title
 *   - search_matched_description
 *   - search_matched_extra
 *   - search_url (defaults to entity->getURL())
 *   - search_time (defaults to entity->time_updated or entity->time_created)
 *
 * @uses $vars['entity'] Entity returned in a search
 */

$entity = $vars['entity'];

// display the entity's owner by default if available.
// @todo allow an option to switch to displaying the entity's icon instead.
$type = $entity->getType();
if ($type == 'user' || $type == 'group') {
    $icon = elgg_view_entity_icon($entity, 'tiny');
} elseif ($owner = $entity->getOwnerEntity()) {
    $icon = elgg_view_entity_icon($owner, 'tiny');
} else {
    // display a generic icon if no owner, though there will probably be
    // other problems if the owner can't be found.
    $icon = elgg_view_entity_icon($entity, 'tiny');
}

if ($container = $entity->getContainerEntity()) {
    $title = $container->title;
}

if ($entity->description) {
    $description = elgg_get_excerpt($entity->description);
}

if ($entity->getURL()) {
    $url = $entity->getURL();
}

$title = "<a href=\"$url\">$title</a>";

$tc = $entity->time_created;
$tu = $entity->time_updated;
$time = elgg_view_friendly_time(($tu > $tc) ? $tu : $tc);

$body = "<p class=\"mbn\">$title</p>$description";
if ($extra_info) {
    $body .= "<p class=\"elgg-subtext\">$extra_info</p>";
}
$body .= "<p class=\"elgg-subtext\">$time</p>";

echo elgg_view_image_block($icon, $body);
