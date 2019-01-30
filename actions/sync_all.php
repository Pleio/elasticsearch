<?php
pleio_schedule_in_background("elasticsearch_console_sync_all", []);

system_message(elgg_echo("elasticsearch:sync:started_in_background"));

forward(REFERER);
