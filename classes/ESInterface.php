<?php

class ESInterface {

    private static $instance;
    private static $index = 'pleio';

    public static function get() {
        if (null === static::$instance) {
            static::$instance = new ESInterface();
        }

        return static::$instance;
    }

    protected function __construct() {
        $this->client = new Elasticsearch\Client();

        $params = array('index' => self::$index);
        if (!$this->client->indices()->get($params)) {
            $this->client->indices()->create($params);
        }
    }

    private function __clone() {}
    private function __wakeup() {}

    public function delete_index() {
        $params = array('index' => self::$index);
        return $this->client->indices()->delete($params);
    }

    public function search($query, $types = array(), $subtypes = array(), $limit = 10, $offset = 0) {
        $params = array();
        $params['index'] = self::$index;

        if (count($types > 0)) {
            $params['type'] = $types;
        }

        $params['body']['query']['query_string']['query'] = $query;

        $results = $this->client->search($params);

        $hits = array();
        foreach ($results['hits']['hits'] as $hit) {
            if ($hit['_type'] != 'annotation') {
                $hits[] = get_entity($hit['_id']);
            } else {
                $hits[] = elgg_get_annotation_from_id($hit['_id']);
            }
        }

        return array(
            'total' => $results['hits']['total'],
            'limit' => $limit,
            'offset' => $offset,
            'hits' => $hits
        );
    }

    public function update($object) {
        $params = array();
        
        $params['index'] = self::$index;
        $params['type'] = $object->type;
        $params['id'] = $object->guid;

        $params['body'] = array();

        foreach ($object->getExportableValues() as $key) {
            $params['body'][$key] = $object->$key;
        }

        return $this->client->index($params);
    }

    public function delete($object) {
        $params = array();
        $params['index'] = self::$index;
        $params['type'] = $object->type;
        $params['id'] = $object->guid;

        return $this->client->delete($params);
    }

    public function enable($object) {
        return $this->update($object);
    }

    public function disable($object) {
        return $this->delete($object);
    }
}