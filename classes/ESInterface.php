<?php
use Elasticsearch\ClientBuilder;

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

        $this->client = Elasticsearch\ClientBuilder::fromConfig($CONFIG->elasticsearch);

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

        $params['body'] = array(
            'index' => array(
                'analysis' => array(
                    'analyzer' => array(
                        'edge_ngram_analyzer' => array(
                            'filter' => array(
                                'lowercase',
                                'asciifolding_filter',
                                'edge_ngram_filter'
                            ),
                            'type' => 'custom',
                            'tokenizer' => 'standard'
                        ),
                        'keyword_analyzer' => array(
                            'tokenizer' => 'keyword',
                            'filter' => 'lowercase'
                        )
                    ),
                    'filter' => array(
                        'asciifolding_filter' => array(
                            'type' => 'asciifolding',
                            'preserve_original' => true
                        ),
                        'edge_ngram_filter' => array(
                            'type' => 'edge_ngram',
                            'min_gram' => '1',
                            'max_gram' => '20'
                        )
                    ),
                )
            )
        );

        return $this->client->indices()->create($params);
    }

    public function putMapping() {
        $return = true;
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
                'tags' => array('type' => 'string', 'analyzer' => 'keyword_analyzer')
            )
        );

        $types = array('group', 'site');
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
                'guid' => array('type' => 'integer'),
                'owner_guid' => array('type' => 'integer'),
                'access_id' => array('type' => 'integer'),
                'site_guid' => array('type' => 'integer'),
                'subtype' => array('type' => 'integer'),
                'title' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'description' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'comments' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'container_guid' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'time_updated' => array('type' => 'integer'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed'),
                'tags' => array('type' => 'string', 'analyzer' => 'keyword_analyzer')
            )
        );

        $type = 'object';
        $return &= $this->client->indices()->putMapping(array(
            'index' => $this->index,
            'type' => $type,
            'body' => array(
                $type => $mapping
            )
        ));

        $mapping = array(
            'properties' => array(
                'guid' => array('type' => 'integer'),
                'owner_guid' => array('type' => 'integer'),
                'access_id' => array('type' => 'integer'),
                'site_guid' => array('type' => 'integer'),
                'subtype' => array('type' => 'integer'),
                'name' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'email' => array('type' => 'string', 'index' => 'not_analyzed'),
                'metadata' => array('type'=> 'nested', 'properties' => array(
                    'access_id' => array('type' => 'integer'),
                    'name' => array('type' => 'string', 'index' => 'not_analyzed'),
                    'value' => array('type' => 'string'))),
                'container_guid' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'time_updated' => array('type' => 'integer'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed'),
                'tags' => array('type' => 'string', 'analyzer' => 'keyword_analyzer')
            )
        );

        $return &= $this->client->indices()->putMapping(array(
            'index' => $this->index,
            'type' => 'user',
            'body' => array(
                'user' => $mapping
            )
        ));

        $mapping = array(
            'properties' => array(
                'id' => array('type' => 'integer'),
                'owner_guid' => array('type' => 'integer'),
                'access_id' => array('type' => 'integer'),
                'site_guid' => array('type' => 'integer'),
                'entity_guid' => array('type' => 'integer'),
                'subtype' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
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

    public function search($string, $search_type, $type, $subtypes = array(), $limit = 10, $offset = 0, $sort = "", $order = "", $container_guid = 0, $profile_fields = array(), $access_id = 0) {

        if ($search_type == 'tags') {
            $search_type = SEARCH_TAGS;
        } else {
            $search_type = SEARCH_DEFAULT;
        }

        $query = new ESQuery($this->index, $search_type);
        $query->setOffset($offset);
        $query->setLimit($limit);

        if ($type) {
            $query->filterType($type);
        }

        if ($sort && $sort !== "relevance") {
            if (!$order) {
                $order = "asc";
            }

            $query->setSort($sort, $order);
        }

        if ($subtypes) {
            $search_subtypes = array();
            if (is_array($subtypes)) {
                foreach ($subtypes as $subtype) {
                    $search_subtypes[] = get_subtype_id('object', $subtype);
                }
            } else {
                $search_subtypes[] = get_subtype_id('object', $subtypes);
            }

            $query->filterSubtypes($search_subtypes);
        }

        if ($container_guid) {
            $query->filterContainer($container_guid);
        }

        if ($access_id) {
            $query->filterAccess($access_id);
        }

        if ($profile_fields && count($profile_fields) > 0) {
            $query->filterProfileFields($profile_fields);
        }

        try {
            $results = $this->client->search($query->search($string));
        } catch (Exception $e) {
            elgg_log('Elasticsearch search exception ' . $e->getMessage(), 'ERROR');

            return array(
                'count' => 0,
                'count_per_type' => array(),
                'count_per_subtype' => array(),
                'hits' => array()
            );
        }

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
        foreach ($results['aggregations']['type']['buckets'] as $type) {
            $count_per_type[$type['key']] = $type['doc_count'];
        }

        $count_per_subtype = array();
        foreach ($results['aggregations']['subtype']['buckets'] as $subtype) {
            if ($subtype['key']) {
                $key = get_subtype_from_id($subtype['key']);
                $count_per_subtype[$key] = $subtype['doc_count'];
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

        try {
            $this->client->index($params);
        } catch (Exception $e) {
            elgg_log('Elasticsearch update exception ' . $e->getMessage(), 'ERROR');
        }

        return true; // always return true, so Elgg's processes are not disturbed.
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
        } catch (Exception $e) {}

        return true; // always return true, so Elgg's processes are not disturbed.
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
