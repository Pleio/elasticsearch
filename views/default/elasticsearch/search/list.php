<?php
/**
 * List a section of search results corresponding in a particular type/subtype
 * or search type (comments for example)
 *
 * @uses $vars['results'] Array of data related to search results including:
 *                          - 'entities' Array of entities to be displayed
 *                          - 'count'    Total number of results
 * @uses $vars['params']  Array of parameters including:
 *                          - 'offset'      Offset in search results
 *                          - 'limit'       Number of results per page
 */
 ?>

<ul class="elgg-list search-list">

    <?php foreach ($vars['results']['hits'] as $entity): ?>
        <?php if ($entity instanceof ElggAnnotation): ?>
            <li id="<?php echo "elgg-{$entity->type}-{$entity->id}"; ?>" class="elgg-item">
        <?php else: ?>
            <li id="<?php echo "elgg-{$entity->type}-{$entity->guid}"; ?>" class="elgg-item">
        <?php endif ?>

        <?php
        $url_options = array(
            "class" => "float-alt elgg-quiet",
            "href" => "search?q=" . $vars['params']['query'] . "&entity_subtype=" . $subtype . "&entity_type=" . $entity->type . "&search_type=entities",
        );

        if ($entity->type == "object") {
            $subtype = get_subtype_from_id($entity->subtype);
            $url_options["text"] = elgg_echo("item:" . $entity->type . ":" . $subtype);
        } elseif ($entity->type == "annotation") {
            $url_options["text"] = elgg_echo("item:comment");
        } else {
            $url_options["text"] = elgg_echo("item:" . $entity->type);
        }

        if ($vars['params']['container_guid']) {
            $url_options["href"] .= "&container_guid=" . $vars['params']['container_guid'];
        }

        echo elgg_view("output/url", $url_options);

        $view = elasticsearch_get_view($entity);
        echo elgg_view($view, array(
            'entity' => $entity
        ));
        ?>
        </li>
    <?php endforeach ?>
</ul>

<?php
    $options = array(
        'limit' => $vars['params']['limit'],
        'offset' => $vars['params']['offset'],
        'count' => $vars['results']['count']
    );

    echo elgg_view('navigation/pagination', $options);
?>