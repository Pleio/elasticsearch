<?php

class ESInterface {

    private static $instance;
    private static $index;

    public static function get() {
        if (null === static::$instance) {
            static::$instance = new ESInterface();
        }

        return static::$instance;
    }

    protected function __construct() {
        global $CONFIG;

        if (!isset($CONFIG->elasticsearch)) {
            throw new ConfigurationException("No Elasticsearch configuration is provided.");
        }
        $this->client = new Elasticsearch\Client($CONFIG->elasticsearch);

        if (!isset($CONFIG->elasticsearch_index)) {
            throw new ConfigurationException("No Elasticsearch index is configured.");
        }
        $this->index = $CONFIG->elasticsearch_index;

        $this->filter = new ESFilter();
    }

    private function __clone() {}
    private function __wakeup() {}

    public function resetIndex() {
        $params = array('index' => $this->index);

        try {
            $this->client->indices()->get($params);
            $this->client->indices()->delete($params);
        } catch (Exception $e) {}

        return $this->client->indices()->create($params);
    }

    public function putMapping() {
        $mapping = array(
            'properties' => array(
                'guid' => array('type' => 'integer'),
                'owner_guid' => array('type' => 'integer'),
                'access_id' => array('type' => 'integer'),
                'site_guid' => array('type' => 'integer'),
                'subtype' => array('type' => 'integer'),
                'container_guid' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'time_updated' => array('type' => 'integer'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed'),
                'tags' => array('type' => 'string', 'index' => 'not_analyzed')
            )
        );

        $return = true;
        $types = array('user', 'group', 'object', 'site');
        foreach ($types as $type) {
            $return &= $this->client->indices()->putMapping(array(
                'index' => $this->index,
                'type' => $type,
                'body' => array(
                    $type => $mapping
                )
            ));
        }

        $mapping = array(
            'properties' => array(
                'id' => array('type' => 'integer'),
                'owner_guid' => array('type' => 'integer'),
                'access_id' => array('type' => 'integer'),
                'site_guid' => array('type' => 'integer'),
                'entity_guid' => array('type' => 'integer'),
                'subtype' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'name' => array('type' => 'string', 'index' => 'not_analyzed'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed')
            )
        );

        $return &= $this->client->indices()->putMapping(array(
            'index' => $this->index,
            'type' => 'annotation',
            'body' => array(
                'annotation' => $mapping
            )
        ));

        return $return;
    }

    public function search($query, $types = array(), $subtypes = array(), $limit = 10, $offset = 0, $sort = "", $order = "") {
        $params = array();
        $params['index'] = $this->index;

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

        $site = elgg_get_site_entity();
        $params['body']['query']['bool']['must'][] = array(
            'term' => array('site_guid' => $site->guid)
        );

        $user = elgg_get_logged_in_user_guid();
        $ignore_access = elgg_check_access_overrides($user);
        if ($ignore_access != true) {
            $params['body']['query']['bool']['must'][] = array(
                'terms' => array('access_id' => get_access_array())
            );
        }

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
            if ($hit['_type'] == 'annotation') {
                $object = elgg_get_annotation_from_id($hit['_id']);
            } else {
                $object = get_entity($hit['_id']);
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
        $object = $this->filter->apply($object);
        if (!$object) {
            return true;
        }

        if ($object['type'] == "annotation") {
            $id = $object['id'];
        } else {
            $id = $object['guid'];
        }

        $params = array();
        $params['index'] = $this->index;
        $params['type'] = $object['type'];
        $params['id'] = $id;
        $params['body'] = $object;

        return $this->client->index($params);
    }

    public function delete($object) {
        $object = $this->filter->apply($object);
        if (!$object) {
            return true;
        }

        if ($object['type'] == "annotation") {
            $id = $object['id'];
        } else {
            $id = $object['guid'];
        }

        $params = array();
        $params['index'] = $this->index;
        $params['type'] = $object['type'];
        $params['id'] = $id;

        try {
            $this->client->delete($params);
        } catch (Exception $exception) {

        }

        return true;
    }

    public function enable($object) {
        $object = $this->filter->apply($object);
        if (!$object) {
            return true;
        }

        return $this->update($object);
    }

    public function disable($object) {
        $object = $this->filter->apply($object);
        if (!$object) {
            return true;
        }

        return $this->delete($object);
    }

    public function bulk(array $objects) {
        $params = array();
        $params['body'] = array();

        foreach ($objects as $object) {

            $object = $this->filter->apply($object);
            if (!$object) {
                continue;
            }

            if ($object['type'] == "annotation") {
                $id = $object['id'];
            } else {
                $id = $object['guid'];
            }

            $params['body'][] =  array(
                'index' => array(
                    '_id' => $id,
                    '_index' => $this->index,
                    '_type' => $object['type']
                )
            );

            $params['body'][] = $object;
        }

        return $this->client->bulk($params);
    }

}