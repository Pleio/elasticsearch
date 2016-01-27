<?php

function elasticsearch_get_subtypes() {
    $types = get_registered_entity_types();
    $types['object'] = array_merge($types['object'], elgg_trigger_plugin_hook('search_types', 'get_types', $params, array()));
    return $types;
}

function elasticsearch_get_view($object) {


    if ($object->type == "annotation") {
        $subtype = $object->name;
    } else {
        $subtype = get_subtype_from_id($object->subtype);
    }

    if (elgg_view_exists('search/' . $object->type . '/' . $subtype)) {
        return 'search/' . $object->type . '/' . $subtype;
    } else {
        if (elgg_view_exists('search/' . $object->type)) {
            return 'search/' . $object->type;
        }
    }

    return 'search/entity';
}

function elasticsearch_console_sync_all() {
    global $CONFIG;
    $CONFIG->memcache = false;

    ini_set('memory_limit', '1024M');
    set_time_limit(0);

    $ia = elgg_set_ignore_access(true);

    $interface = ESInterface::get();
    $bulksync = new ESBulkSync($interface);
    $bulksync->sync();

    elgg_set_ignore_access($ia);
}

function elasticsearch_console_index_reset() {
    $interface = ESInterface::get();

    if ($interface->resetIndex()) {
        if ($interface->putMapping()) {
            echo "Index and mapping created succesfully.";
        } else {
            echo "Could not create mapping.";
        }
    } else {
        echo "Could not create index.";
    }
}

/**
 * Create a standard object from a given entity row.
 *
 * @param stdClass $row The row of the entry in the entities table.
 *
 * @return ElggEntity|false
 * @link http://docs.elgg.org/DataModel/Entities
 * @see get_entity_as_row()
 * @see add_subtype()
 * @see get_entity()
 * @access private
 *
 * @throws ClassException|InstallationException
 */
function elasticsearch_entity_row_to_std($row) {
    if (!($row instanceof stdClass)) {
        return $row;
    }

    if ((!isset($row->guid)) || (!isset($row->subtype))) {
        return $row;
    }

    $new_entity = false;

    // Create a memcache cache if we can
    static $newentity_cache;
    if ((!$newentity_cache) && (is_memcache_available())) {
        $newentity_cache = new ElggMemcache('new_entity_cache');
    }
    if ($newentity_cache) {
        $new_entity = $newentity_cache->load($row->guid);
    }
    if ($new_entity) {
        return $new_entity;
    }


    try {
        // load class for entity if one is registered
        $classname = get_subtype_class_from_id($row->subtype);
        if ($classname != "") {
            if (class_exists($classname)) {
                $new_entity = new $classname($row);
                if (!($new_entity instanceof ElggEntity)) {
                    $msg = elgg_echo('ClassException:ClassnameNotClass', array($classname, 'ElggEntity'));
                    throw new ClassException($msg);
                }
            }
        }

        if (!$new_entity) {
            switch ($row->type) {
                case 'object' :
                    $new_entity = new ElggObject($row);
                    break;
                case 'user' :
                    $new_entity = new ElggUser($row);
                    break;
                case 'group' :
                    $new_entity = new ElggGroup($row);
                    break;
                case 'site' :
                    $new_entity = new ElggSite($row);
                    break;
                default:
                    $msg = elgg_echo('InstallationException:TypeNotSupported', array($row->type));
                    throw new InstallationException($msg);
            }
        }
    } catch (IncompleteEntityException $e) {
        return false;
    }

    return $new_entity;
}
