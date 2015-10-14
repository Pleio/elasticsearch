<?php
/**
 * Default search view for a comment
 *
 * @uses $vars['entity']
 */

$annotation = $vars['entity'];
$entity = $annotation->getEntity();

$owner = $entity->getOwnerEntity();
$icon = elgg_view_entity_icon($owner, 'tiny');

if ($entity->getType() == 'object') {
    $title = $entity->title;
} else {
    $title = $entity->name;
}

if (!$title) {
    $title = elgg_echo('item:' . $entity->getType() . ':' . $entity->getSubtype());
}

if (!$title) {
    $title = elgg_echo('item:' . $entity->getType());
}

$title = elgg_echo('search:comment_on', array($title));

$url = $entity->getURL() . '#comment_' . $annotation->id;
$title = "<a href=\"$url\">$title</a>";



$data = $entity->description;

$time = elgg_view_friendly_time($entity->time_created);

$body = "<p class=\"mbn\">$title</p>" . elgg_get_excerpt($annotation->value, 100);
$body .= "<p class=\"elgg-subtext\">" . $time . "</p>";

echo elgg_view_image_block($icon, $body);