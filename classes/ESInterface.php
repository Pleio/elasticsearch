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
        global $CONFIG;
        $this->client = new Elasticsearch\Client($CONFIG->elasticsearch);
        $this->filter = new ESFilter();
    }

    private function __clone() {}
    private function __wakeup() {}

    public function create_index() {
        $params = array('index' => self::$index);
        if (!$this->client->indices()->get($params)) {
            return $this->client->indices()->create($params);
        } else {
            return false;
        }
    }

    public function delete_index() {
        $params = array('index' => self::$index);
        return $this->client->indices()->delete($params);
    }

    public function search($query, $types = array(), $subtypes = array(), $limit = 10, $offset = 0, $sort = "", $order = "") {
        // @todo: implement $sort and $order

        $params = array();
        $params['index'] = self::$index;

        $params['body']['query']['bool']['must'] = array();
        $params['body']['size'] = $limit;
        $params['body']['from'] = $offset;


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

        $params['body']['facets'] = array();
        $params['body']['facets']['type']['terms'] = array(
            'field' => '_type'
        );
        $params['body']['facets']['subtype']['terms'] = array(
            'field' => 'subtype'
        );

        $results = $this->client->search($params);

        $hits = array();
        foreach ($results['hits']['hits'] as $hit) {
            if ($hit['_type'] != 'annotation') {
                $object = get_entity($hit['_id']);
            } else {
                $object = elgg_get_annotation_from_id($hit['_id']);
            }

            if ($object) {
                $hits[] = $object;
            }
        }

        $count_per_type = array();
        foreach ($results['facets']['type']['terms'] as $type) {
            $count_per_type[$type['term']] = $type['count'];
        }

        $count_per_subtype = array();
        foreach ($results['facets']['subtype']['terms'] as $subtype) {
            if ($subtype['term']) {
                $key = get_subtype_from_id($subtype['term']);
                $count_per_subtype[$key] = $subtype['count'];
            }
        }

        return array(
            'count' => $results['hits']['total'],
            'count_per_type' => $count_per_type,
            'count_per_subtype' => $count_per_subtype,
            'hits' => $hits
        );
    }

    public function update($object) {
        if (!$this->filter->apply($object)) {
            return true;
        }

        $params = array();
        $params['index'] = self::$index;
        $params['type'] = $object->type;
        $params['id'] = $object->guid;

        $params['body'] = array();
        $params['body']['access_id'] = $object->access_id;

        foreach ($object->getExportableValues() as $key) {
            $params['body'][$key] = $object->$key;
        }

        return $this->client->index($params);
    }

    public function delete($object) {
        if (!$this->filter->apply($object)) {
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
        if (!$this->filter->apply($object)) {
            return true;
        }

        return $this->update($object);
    }

    public function disable($object) {
        if (!$this->filter->apply($object)) {
            return true;
        }

        return $this->delete($object);
    }

    public function bulk(array $objects) {
        $params = array();
        $params['body'] = array();

        foreach ($objects as $object) {
            if (!$this->filter->apply($object)) {
                continue;
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