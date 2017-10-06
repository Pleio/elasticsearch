<?php

$interface = ESInterface::get();

if ($interface->resetIndex()) {
    if ($interface->putMapping()) {
        system_message(elgg_echo("elasticsearch:index_and_mapping_created"));
    } else {
        register_error(elgg_echo("elasticsearch:could_not_create_mapping"));
        forward(REFERER);
    }
} else {
    register_error(elgg_echo("elasticsearch:could_not_reset_index"));
    forward(REFERER);
}
