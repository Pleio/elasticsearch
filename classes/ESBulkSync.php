<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

class ESBulkSync {
    public function __construct(ESInterface $interface) {
        $this->interface = $interface;
    }

    /*public function getObjects() {
        foreach ($guids as $guid) {
            echo $guid;
        }

        $subtypes = elasticsearch_get_subtypes();

        $options = array(
            'types' => array('user','group','object','site'),
            'subtypes' => $subtypes['object'],
            'limit' => false
        );
        return new ElggBatch('elgg_get_entities', $options, null, 500);
    }*/

    public function getAnnotations() {

    }

    public function sync() {
        $dblink = get_db_link('read');
        
        $result = execute_query('SELECT COUNT(`guid`) FROM `elgg_entities` WHERE `site_guid`=1', $dblink);
        $row = mysql_fetch_row($result);
        $total = (int) $row[0];

        $result = execute_query('SELECT `guid` FROM `elgg_entities` WHERE `site_guid`=1', $dblink);

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
                } catch (Exception $exception) {
                    var_dump($entities);
                }
                
                $guids = array();
            }

            $i += 1;
            if ($i % 500 == 0) {
                echo round($i / $total * 100, 2) . " | " . memory_get_usage() . PHP_EOL;
            }

        }

       exit();
    }

}