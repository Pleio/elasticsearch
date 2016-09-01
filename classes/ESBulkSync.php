<?php

class ESBulkSync {

    public function __construct(ESInterface $interface) {
        $this->interface = $interface;
    }

    public function sync() {
        echo "Syncing objects..." . PHP_EOL;
        $this->syncObjects();
        echo "Syncing annotations..." . PHP_EOL;
        $this->syncAnnotations();
    }

    public function syncObjects() {
        $dblink = get_db_link('read');
        $site = elgg_get_site_entity();

        $result = execute_query('SELECT COUNT(`guid`) FROM elgg_entities', $dblink);

        $row = mysqli_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `guid` FROM elgg_entities ORDER BY guid', $dblink);

        $i = 1;
        $guids = array();

        while ($row = mysqli_fetch_row($result)) {
            $guids[] = $row[0];

            if (count($guids) == 50) {
                $this->processItems('entities', $guids);
                $guids = array();
            }

            $i += 1;
            if ($i % 500 == 0) {
                echo round($i / $total * 100, 2) . "%\r";
            }
        }

        if (count($guids) > 0) {
            $this->processItems('entities', $guids);
        }
    }

    public function syncAnnotations() {
        $dblink = get_db_link('read');
        $site = elgg_get_site_entity();

        $result = execute_query('SELECT COUNT(`id`) FROM elgg_annotations', $dblink);

        $row = mysqli_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `id` FROM elgg_annotations ORDER BY id', $dblink);

        while ($row = mysqli_fetch_row($result)) {
            $ids[] = $row[0];

            if (count($ids) == 50) {
                $this->processItems('annotations', $ids);
                $ids = array();
            }

            $i += 1;
            if ($i % 500 == 0) {
                echo round($i / $total * 100, 2) . "%\r";
            }
        }

        if (count($ids) > 0) {
            $this->processItems('annotations', $ids);
        }
    }

    public function processItems($type, $guids) {
        if ($type == 'entities') {
            $items = elgg_get_entities(array(
                'guids' => $guids,
                'limit' => false,
                'site_guids' => false,
                'callback' => 'elasticsearch_entity_row_to_std'
            ));
        } elseif ($type == 'annotations') {
            $items = elgg_get_annotations(array(
                'annotations_ids' => $guids,
                'limit' => false,
                'site_guids' => false
            ));
        } else {
            throw new Exception('Invalid type.');
        }

        try {
            $this->interface->bulk($items);
        } catch (Exception $exception) {}
    }
}