<?php
define('SEARCH_DEFAULT', 0);
define('SEARCH_TAGS', 1);

class ESQuery {
    private $params = array();
    private $metadataAccessFilter;
    private $searchtype;
    private $type;
    private $subtype;
    private $access_array;

    public function __construct($index, $searchtype = SEARCH_DEFAULT) {
        $this->searchtype = $searchtype;

        $this->params['index'] = $index;
        $this->params['body'] = array();

        $site = elgg_get_site_entity();
        $this->params['body']['query']['bool']['must'][] = array(
            'term' => array('site_guid' => $site->guid)
        );

        $user = elgg_get_logged_in_user_guid();
        $ignore_access = elgg_check_access_overrides($user);
        if ($ignore_access != true && !elgg_is_admin_logged_in()) {
            $this->access_array = get_access_array();

            $this->params['body']['query']['bool']['must'][] = array(
                'terms' => array('access_id' => $this->access_array)
            );
        }

        //@todo: implement $sort and $order
        $this->params['body']['sort'] = array(
            'time_updated' => 'desc'
        );

        $this->params['body']['facets'] = array();
        $this->params['body']['facets']['type']['terms'] = array(
            'field' => '_type'
        );
        $this->params['body']['facets']['subtype']['terms'] = array(
            'field' => 'subtype'
        );
    }

    public function setSort($sort, $order = "desc") {
        $this->params['body']['sort'] = array(
            $sort => $order
        );
    }

    public function setOffset($offset) {
        $this->params['body']['from'] = $offset;
    }

    public function setLimit($limit) {
        $this->params['body']['size'] = $limit;
    }

    public function filterType($type) {
        $this->type = $type;
        $this->params['type'] = $type;
    }

    public function filterSubtypes($subtypes) {
        $this->subtypes = $subtypes;
        $this->params['body']['query']['bool']['must'][] = array(
            'terms' => array('subtype' => $subtypes)
        );
    }

    public function filterContainer($container_guid) {
        $this->params['body']['query']['bool']['must'][] = array(
            'term' => array('container_guid' => $container_guid)
        );
    }

    public function filterAccess($access_ids) {
        $this->params['body']['query']['bool']['must'][] = array(
            'terms' => array('access_id' => $access_ids)
        );
    }

    public function filterProfileFields($profile_fields) {
        foreach ($profile_fields as $name => $value) {
            $must = array(
                array('term' => array('metadata.name' => $name)),
                array('match' => array('metadata.value' => $value))
            );

            if ($this->access_array) {
                $must[] = array('terms' => array('metadata.access_id' => $this->access_array));
            }

            $this->params['body']['query']['bool']['must'][] = array(
                'nested' => array(
                    'path' => 'metadata',
                    'query' => array(
                        'bool' => array(
                            'must' => $must
                        )
                    )
                )
            );
        }
    }

    public function search($string) {
        switch ($this->searchtype) {
            case SEARCH_TAGS:
                $this->params['body']['query']['bool']['must'][] = array(
                    'term' => array('tags' => $string)
                );
                break;

            case SEARCH_DEFAULT:
            default:
                $this->params['body']['query']['bool']['minimum_should_match'] = 1;
                $this->params['body']['query']['bool']['should'][] = array(
                    'simple_query_string' => array(
                        'query' => $string,
                        'default_operator' => 'and',
                        'fields' => array(
                            'title',
                            'description',
                            'comments',
                            'value',
                            'username',
                            'name',
                            'email',
                            'tags'
                        )
                    )
                );

                if (!$this->type | $this->type == 'user') {
                    $must = array(
                        array('simple_query_string' => array(
                            'query' => $string,
                            'default_operator' => 'and',
                            'fields' => array(
                                'metadata.value'
                            )
                        ))
                    );

                    if ($this->access_array) {
                        $must[] = array('terms' => array('metadata.access_id' => $this->access_array));
                    }

                    $this->params['body']['query']['bool']['should'][] = array(
                        'nested' => array(
                            'path' => 'metadata',
                            'query' => array(
                                'bool' => array(
                                    'must' => $must
                                )
                            )
                        )
                    );
                }

                break;
        }

        return $this->params;
    }
}