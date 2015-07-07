<?php
set_time_limit(0);

if (php_sapi_name() !== 'cli') {
  throw new Exception('This script must be run from the CLI.');
}

// Configure with "main site". Needed so subsite_manager can identify our instance.

// Production
$_SERVER["HTTP_HOST"] = "www.pleio.dev";
$_SERVER["HTTPS"] = false;

// Development
//$_SERVER["HTTP_HOST"] = "pleio.localhost.nl";

require_once(dirname(dirname(dirname(__FILE__))) . "/../engine/start.php");

// disable memcache support
$CONFIG->memcache = false;


$ia = elgg_set_ignore_access(true);

$interface = ESInterface::get();
$bulksync = new ESBulkSync($interface);
$bulksync->sync();

elgg_set_ignore_access($ia);