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
        $this->filter = new ESFilter();

        $params = array('index' => self::$index);
        //$this->client->indices()->create($params);

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

        $params['body']['query']['bool']['must'] = array();

        $type = get_input('entity_type');
        if ($type) {
            $params['body']['query']['bool']['must'][] = array(
                'term' => array('type' => $type)
            );
        }

        $subtype = get_input('entity_subtype');
        if ($subtype) {
            $params['body']['query']['bool']['must'][] = array(
                'term' => array('subtype' => get_subtype_id($type, $subtype))
            );
        }

        $params['body']['query']['bool']['must'][] = array(
            'query_string' => array('query' => $query)
        );

        $params['body']['query']['bool']['must'][] = array(
            'terms' => array('access_id' => get_access_array())
        );

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
        if (!$this->filter($object)) {
            return true;
        }

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
        if (!$this->filter($object)) {
            return true;
        }

        $params = array();
        $params['index'] = self::$index;
        $params['type'] = $object->type;
        $params['id'] = $object->guid;

        try {
            $this->client->delete($params);
        } catch (Exception $exception) {

        }

        return true;
    }

    public function enable($object) {
        if (!$this->filter($object)) {
            return true;
        }

        return $this->update($object);
    }

    public function disable($object) {
        if (!$this->filter($object)) {
            return true;
        }

        return $this->delete($object);
    }

    public function bulk(array $objects) {
        $params = array();
        $params['body'] = array();

        foreach ($objects as $object) {
            if (!$this->filter($object)) {
                return true;
            }

            $params['body'][] =  array(
                'index' => array(
                    '_id' => $object->guid,
                    '_index' => self::$index,
                    '_type' => $object->type
                )
            );

            $values = array();
            foreach ($object->getExportableValues() as $key) {
                $values[$key] = $object->$key;
            }

            $values['access_id'] = $object->access_id;

            if ($values['description']) {
                $values['description'] = elgg_strip_tags($values['description']);
            }

            $params['body'][] = $values;
        }

        return $this->client->bulk($params);
    }

}