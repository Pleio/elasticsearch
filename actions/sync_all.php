<?php
global $CONFIG;
$CONFIG->memcache = false;

ini_set("memory_limit", "1024M");
set_time_limit(0);

$interface = ESInterface::get();
$bulksync = new ESBulkSync($interface);
$bulksync->sync();

system_message(elgg_echo("elasticsearch:all_synced"));