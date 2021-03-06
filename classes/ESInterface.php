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

        if(isset($CONFIG->amqp_host)) {
            try {
                $this->celery = new \Celery(
                    $CONFIG->amqp_host,
                    $CONFIG->amqp_user,
                    $CONFIG->amqp_pass,
                    $CONFIG->amqp_vhost
                );
            } catch (Exception $e) {
                elgg_log('Elasticsearch celery exception ' . $e->getMessage(), 'ERROR');
            }
        }
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
                            'max_gram' => '30'
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
                'tags' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard')
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
                'title' => array('boost' => 2, 'type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'description' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'file_contents' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'comments' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard'),
                'container_guid' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'time_updated' => array('type' => 'integer'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed'),
                'tags' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard')
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
                'name' => array('boost' => 5, 'type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard', 'fields' => array('raw' => array('type' => 'string', 'index' => 'not_analyzed'))),
                'email' => array('type' => 'string', 'index' => 'not_analyzed'),
                'metadata' => array('type'=> 'nested', 'properties' => array(
                    'access_id' => array('type' => 'integer'),
                    'name' => array('type' => 'string', 'index' => 'not_analyzed'),
                    'value' => array('type' => 'string'))),
                'container_guid' => array('type' => 'integer'),
                'time_created' => array('type' => 'integer'),
                'time_updated' => array('type' => 'integer'),
                'type' => array('type' => 'string', 'index' => 'not_analyzed'),
                'tags' => array('type' => 'string', 'analyzer' => 'edge_ngram_analyzer', 'search_analyzer' => 'standard')
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

    public function search($string, $search_type, $type, $subtypes = array(), $limit = 10, $offset = 0, $sort = "relevance", $order = "asc", $container_guid = 0, $profile_fields = array(), $access_id = 0) {

        if ($search_type == 'tags') {
            $search_type = SEARCH_TAGS;
        } else {
            $search_type = SEARCH_DEFAULT;
        }

        $query = new ESQuery($this->index, $search_type);
        $query->setOffset($offset);
        $query->setLimit($limit);

        $search_type = $type;

        if ($type) {
            $query->filterType($type);
        }

        if ($sort !== "relevance" && $sort && $order) {
            $query->setSort($sort, $order);
        }

        if ($subtypes) {
            $search_subtypes = array();
            if (is_array($subtypes)) {
                foreach ($subtypes as $subtype) {
                    if ($subtype === 'user' || $subtype === 'group') {
                        if (!in_array(0, $search_subtypes)) {
                            $search_subtypes[] = 0;
                        }
                    } else {
                        $subtype_id = get_subtype_id('object', $subtype);
                        if ($subtype_id && !in_array($subtype_id, $search_subtypes)) {
                            $search_subtypes[] = $subtype_id;
                        }
                    }
                }
            } else {
                $subtype_id = get_subtype_id('object', $subtypes);
                if ($subtype_id) {
                    $search_subtypes[] = $subtype_id;
                }
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

        $count_per_metadata_name = array();

        if ($search_type === 'user') {
            foreach ($results['aggregations']['nesting']['names']['buckets'] as $name) {
                $key = $name['key'];
                $values = array();
                foreach ($name['values']['buckets'] as $value){
                    $values[] = array('key' => $value['key'], 'count' => $value['doc_count']);
                }
                $count_per_metadata_name[$key] = $values;
            }
        }
        return array(
            'count' => $results['hits']['total'],
            'count_per_type' => $count_per_type,
            'count_per_subtype' => $count_per_subtype,
            'count_per_metadata_name' => $count_per_metadata_name,
            'hits' => $hits
        );
    }

    public function update($input_object) {
        $object = $this->filter->apply($input_object);
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
            if ($this->celery) {

                if ($input_object instanceof ElggFile) {

                    $ia = elgg_set_ignore_access(true);
                    $filename = $input_object->getFilenameOnFilestore();
                    elgg_set_ignore_access($ia);

                    if ($filename) {
                        $params['body']['file'] = $filename;
                    }
                }

                $this->celery->PostTask('elasticsearch.update', $params);

            } else {
                $this->client->index($params);
            }

        } catch (Exception $e) {
            elgg_log("Elasticsearch update exception " . $e->getMessage(), "ERROR");
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
            if ($this->celery) {
                $this->celery->PostTask('elasticsearch.delete', $params);
            } else {
                $this->client->delete($params);
            }
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

        foreach ($objects as $input_object) {
            $object = $this->filter->apply($input_object);
            if (!$object) {
                continue;
            }

            if ($object['type'] == "annotation") {
                $id = $object['id'];
            } else {
                $id = $object['guid'];
            }


            if ($this->celery && $input_object instanceof ElggFile) {
                $ia = elgg_set_ignore_access(true);
                $filename = $input_object->getFilenameOnFilestore();
                elgg_set_ignore_access($ia);

                if ($filename) {
                    $object['file'] = $filename;
                }
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

        try {
            if ($this->celery) {
                $this->celery->PostTask('elasticsearch.bulk', $params);
            } else {
                $this->client->bulk($params);
            }
        } catch (Exception $e) {
            elgg_log("Elasticsearch bulk exception " . $e->getMessage(), "ERROR");
        }

    }

}
