<?php

echo elgg_view('output/url', array(
    'text' => elgg_echo('elasticsearch:bulk_sync'),
    'href' => 'action/elasticsearch/sync',
    'is_action' => true,
    'class' => 'elgg-button elgg-button-action'
));

echo "<br><br><br>";