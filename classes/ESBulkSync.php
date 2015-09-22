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

        $row = mysql_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `guid` FROM elgg_entities ORDER BY guid', $dblink);

        $i = 1;
        $guids = array();

        while ($row = mysql_fetch_row($result)) {
            $guids[] = $row[0];

            if (count($guids) == 50) {
                $entities = elgg_get_entities(array(
                    'guids' => $guids,
                    'limit' => false,
                    'site_guids' => false,
                    'callback' => 'elasticsearch_entity_row_to_std'
                ));

                try {
                    $this->interface->bulk($entities);
                } catch (Exception $exception) {}

                $guids = array();
            }

            $i += 1;
            if ($i % 500 == 0) {
                echo round($i / $total * 100, 2) . "%\r";
            }

        }
    }

    public function syncAnnotations() {
        $dblink = get_db_link('read');
        $site = elgg_get_site_entity();

        $result = execute_query('SELECT COUNT(`id`) FROM elgg_annotations', $dblink);

        $row = mysql_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `id` FROM elgg_annotations ORDER BY id', $dblink);

        while ($row = mysql_fetch_row($result)) {
            $ids[] = $row[0];

            if (count($ids) == 50) {
                $annotations = elgg_get_annotations(array(
                    'annotations_ids' => $ids,
                    'limit' => false,
                    'site_guids' => false
                ));

                try {
                    $this->interface->bulk($annotations);
                } catch (Exception $exception) {}

                $ids = array();
            }

            $i += 1;
            if ($i % 500 == 0) {
                echo round($i / $total * 100, 2) . "%\r";
            }

        }
    }
}