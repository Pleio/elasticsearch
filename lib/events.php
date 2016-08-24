<?php

function elasticsearch_create_event($event, $object_type, $object) {
    return ESInterface::get()->update($object);
}

function elasticsearch_update_event($event, $object_type, $object) {
    return ESInterface::get()->update($object);
}

function elasticsearch_delete_event($event, $object_type, $object) {
    return ESInterface::get()->delete($object);
}

function elasticsearch_enable_event($event, $object_type, $object) {
    return ESInterface::get()->enable($object);
}

function elasticsearch_disable_event($event, $object_type, $object) {
    return ESInterface::get()->disable($object);
}

function elasticsearch_update_relationship_event($event, $object_type, $object) {
    if ($object->guid_one) {
        $object = get_entity($object->guid_one);
        if ($object instanceof ElggUser) {
            return ESInterface::get()->update($user);
        }
    }
}