<?php

function elasticsearch_create_event($event, $object_type, $object) {
    if (in_array($object->getSubtype(), array('answer', 'comment'))) {
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


function elasticsearch_delete_event($event, $object_type, $object) {
    if (in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
        ESSchedule::get()->schedule("update", $object);
    } else {
        ESSchedule::get()->schedule("delete", $object);
    }
}

function elasticsearch_enable_event($event, $object_type, $object) {
    if (in_array($object->getSubtype(), array('answer', 'comment'))) {
        $object = $object->getContainerEntity();
    }

    ESSchedule::get()->schedule("update", $object);
}

function elasticsearch_disable_event($event, $object_type, $object) {
    if (in_array($object->getSubtype(), array('answer', 'comment'))) {
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