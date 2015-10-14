<?php

$q = sanitise_string(get_input("q"));
$limit = max((int) get_input("limit", 5), 1);
$page_owner_guid = (int) get_input("page_owner_guid");

$result = array();
if (!empty($q)) {

    $users = ESInterface::get()->search($q, 'user', false, $limit, 0, false, false);
    $groups = ESInterface::get()->search($q, 'group', false, $limit, 0, false, false);

    $users = $users['hits'];
    $groups = $groups['hits'];

    $result = array();
    if ($users) {
        $result[] = array("type" => "placeholder", "content" => "<label>" . elgg_echo("item:user") . "</label>");
        foreach ($users as $user) {
            $result[] = array("type" => "user", "value" => $user->name, "href" => $user->getURL(), "content" => elgg_view("search/autocomplete/user", array("entity" => $user)));
        }
    }

    if ($groups) {
        $result[] = array("type" => "placeholder", "content" => "<label>" . elgg_echo("item:group") . "</label>");
        foreach ($groups as $group) {
            $result[] = array("type" => "group", "value" => $group->name, "href" => $group->getURL(), "content" => elgg_view("search/autocomplete/group", array("entity" => $group)));
        }
    }
}

header("Content-Type: application/json");
echo json_encode(array_values($result));

exit();
