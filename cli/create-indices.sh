curl -XPOST 'http://localhost:9200/pleio'

curl -XPUT 'http://localhost:9200/pleio/_mapping/user' -d '
{
    "user": {
        "properties": {
            "guid": {"type": "integer"},
            "owner_guid": {"type": "integer"},
            "access_id": {"type": "integer"},
            "site_guid": {"type": "integer"},
            "subtype": {"type": "integer"},
            "container_guid": {"type": "integer"},
            "time_created": {"type": "integer"},
            "time_updated": {"type": "integer"},
            "type": {"type": "string", "index": "not_analyzed"}
        }
    }
}'

curl -XPUT 'http://localhost:9200/pleio/_mapping/group' -d '
{
    "group": {
        "properties": {
            "guid": {"type": "integer"},
            "owner_guid": {"type": "integer"},
            "access_id": {"type": "integer"},
            "site_guid": {"type": "integer"},
            "subtype": {"type": "integer"},
            "container_guid": {"type": "integer"},
            "time_created": {"type": "integer"},
            "time_updated": {"type": "integer"},
            "type": {"type": "string", "index": "not_analyzed"}
        }
    }
}'

curl -XPUT 'http://localhost:9200/pleio/_mapping/object' -d '
{
    "object": {
        "properties": {
            "guid": {"type": "integer"},
            "owner_guid": {"type": "integer"},
            "access_id": {"type": "integer"},
            "site_guid": {"type": "integer"},
            "subtype": {"type": "integer"},
            "container_guid": {"type": "integer"},
            "time_created": {"type": "integer"},
            "time_updated": {"type": "integer"},
            "type": {"type": "string", "index": "not_analyzed"}
        }
    }
}'

curl -XPUT 'http://localhost:9200/pleio/_mapping/site' -d '
{
    "site": {
        "properties": {
            "guid": {"type": "integer"},
            "owner_guid": {"type": "integer"},
            "access_id": {"type": "integer"},
            "site_guid": {"type": "integer"},
            "subtype": {"type": "integer"},
            "container_guid": {"type": "integer"},
            "time_created": {"type": "integer"},
            "time_updated": {"type": "integer"},
            "type": {"type": "string", "index": "not_analyzed"}
        }
    }
}'