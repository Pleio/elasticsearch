<?php

$english = array(
    'elasticsearch:results' => 'Results for %s',
    'elasticsearch:nr_results' => '%s results found for %s',
    'elasticsearch:bulk_sync' => 'Bulk sync',
    'elasticsearch:settings:title' => 'Elasticsearch settings',
    'elasticsearch:settings:management' => 'Management',
    'elasticsearch:reset_index' => 'Reset index',
    'elasticsearch:sync_all' => 'Sync all',
    'elasticsearch:all_synced' => 'All content is synced correctly.',
    'elasticsearch:sync:started_in_background' => 'Content synchronisation is started in the background.',
    'elasticsearch:settings:profile_fields' => 'Configure searchable profile fields',
    'elasticsearch:could_not_create_mapping' => 'Could not create mapping',
    'elasticsearch:could_not_reset_index' => 'Could not reset index',
    'elasticsearch:index_and_mapping_created' => 'Index and mapping succesfully created',

    'item:comment' => 'Comment',
    'item:group' => 'Group',

    'search_advanced:settings:profile_fields:field' => "Profile field",
    'search_advanced:settings:user_profile_fields:show_on_form' => "Show on search form",
    'search_advanced:settings:user_profile_fields:use_autocomplete' => "Search input via an autocomplete",
    'search_advanced:settings:user_profile_fields:info' => "Allow users to refine their search for users based on profile fields. Currently only text based fields are supported (text, location, url, etc).",
    'search_advanced:forms:search:user:autocomplete_info' => "Start typing and select from the list"
);

add_translation("en", $english);
