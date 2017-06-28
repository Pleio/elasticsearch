<?php

function elasticsearch_create_event($event, $object_type, $object) {
    if ($object instanceof ElggObject && in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
    }

    ESSchedule::get()->schedule("update", $object);
}

function elasticsearch_update_event($event, $object_type, $object) {
    if (in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
    }

    ESSchedule::get()->schedule("update", $object);
}

function elasticsearch_update_file_event($file_guid) {
    $config = elgg_get_config("tika_server");
    if (!$config) {
        return;
    }

    $ia = elgg_set_ignore_access(true);

    $file = get_entity($file_guid);
    if ($file) {
        $filename = $file->getFilenameOnFilestore();
    }

    elgg_set_ignore_access($ia);

    if (!$filename) {
        return;
    }

    try {
        $client = \Vaites\ApacheTika\Client::make($config[0], $config[1]);
        $interface = ESInterface::get();
        $interface->updateFileContents($file, $client->getText($filename));
    } catch (Exception $e) {
        elgg_log("Could not get file contents " . $file->guid . " " . $e->getMessage(), "ERROR");
    }
}

function elasticsearch_delete_event($event, $object_type, $object) {
    if ($object instanceof ElggObject && in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
        ESSchedule::get()->schedule("update", $object);
    } else {
        ESSchedule::get()->schedule("delete", $object);
    }
}

function elasticsearch_enable_event($event, $object_type, $object) {
    if ($object instanceof ElggObject && in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
    }

    ESSchedule::get()->schedule("update", $object);
}

function elasticsearch_disable_event($event, $object_type, $object) {
    if ($object instanceof ElggObject && in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
        ESSchedule::get()->schedule("update", $object);
    } else {
        ESSchedule::get()->schedule("delete", $object);
    }
}

function elasticsearch_update_relationship_event($event, $object_type, $object) {
    if ($object->guid_one) {
        $object = get_entity($object->guid_one);
        if ($object instanceof ElggUser) {
            ESSchedule::get()->schedule("update", $object);
        }
    }
}

function elasticsearch_system_shutdown($event, $event_type, $object) {
    ESSchedule::get()->execute();
}