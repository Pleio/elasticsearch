<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

class ESBulkSync {

    public function __construct(ESInterface $interface) {
        $this->interface = $interface;
    }

    public function sync() {
        $this->syncObjects();
        $this->syncAnnotations();
    }

    public function syncObjects() {
        $dblink = get_db_link('read');
        $site = elgg_get_site_entity();

        $result = execute_query('SELECT COUNT(`guid`) FROM `elgg_entities` WHERE `site_guid`=' . $site->guid, $dblink);

        $row = mysql_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `guid` FROM `elgg_entities` WHERE `site_guid`=' . $site->guid, $dblink);

        $i = 1;
        $guids = array();

        while ($row = mysql_fetch_row($result)) {
            $guids[] = $row[0];

            if (count($guids) == 50) {
                $entities = elgg_get_entities(array(
                    'guids' => $guids,
                    'limit' => false
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
        // @todo: implement
    }
}