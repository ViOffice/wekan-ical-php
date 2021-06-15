<?php
// SPDX-FileCopyrightText: 2021 Weymeirsch und Langer GbR
// SPDX-Author: Jan Weymeirsch
//
// SPDX-License-Identifier: AGPL-3.0-only

// Load config files
list($pwd) = preg_replace('/\/[^\/]+$/', "/", get_included_files());
$conf_path = $pwd . "conf/common.php";
include($conf_path);

// Load wekan API
$wapi_path = $pwd . "libs/wekan_api.php";
include($wapi_path);

// Load ical builder
$ical_path = $pwd . "libs/create_ical.php";
include($ical_path);

// Get user-input
$id=$_GET["id"];

// connect to database
$sqlcon = new mysqli($sqlhost, $sqluser, $sqlpass, $sqlname);
if ($sqlcon->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

// retrieve token
$sqlque = "SELECT token, expire FROM " . $sqltabl . " WHERE ical=" . $id;
$sqlres = $sqlcon->query($sqlque);
while ($row = $sqlres->fetch_assoc()) {
    $token = $row['token'];
    $expire = $row['expire'];
}

// Prepare
$result = array();
$auth = "Authorization: Bearer " . $token;

// Get User Information
$user = wekan_api_call("https://" . $wekan_domain, "/api/user", $auth, NULL);

// Get user's boards
$boards = wekan_api_call("https://" . $wekan_domain, "/api/users/" . 
    $user['_id'] . "/boards", $auth, NULL);

foreach ($boards as $board) {

    // Get lists
    $lists = wekan_api_call("https://" . $wekan_domain, "/api/boards/" . 
        $board['_id'] . "/lists", $auth, NULL);

    foreach ($lists as $list) {

        // Get cards
        $cards = wekan_api_call("https://" . $wekan_domain, "/api/boards/" . 
            $board['_id'] . "/lists/" . $list['_id'] . "/cards", $auth, NULL);
        
        // Assign cards to swimlanes
        foreach ($cards as $card) {

            // Remove HTML from description
            if (array_key_exists("description", $card)) {
                $card['description'] = preg_replace('/<br>/', '\\n',
                    $card['description']);
                $card['description'] = preg_replace('/<li>/', '\\n- ',
                    $card['description']);
                $card['description'] = preg_replace('/<.*?>/', '',
                    $card['description']);
            }

            //Get card info
            $cardinfo = wekan_api_call("https://" . $wekan_domain, 
                "/api/boards/" . $board['_id'] . "/lists/" . $list['_id'] . 
                "/cards/" . $card['_id'], $auth, NULL);

            // Get Swimlane
            $lane = wekan_api_call("https://" . $wekan_domain, "/api/boards/" .
                $board['_id'] . "/swimlanes/" . $cardinfo['swimlaneId'], $auth,
                NULL);

            // Get Checklists
            $checklists = wekan_api_call("https://" . $wekan_domain,
                "/api/boards/" . $board['_id'] . "/cards/" . $card['_id'] . 
                "/checklists", $auth, NULL);

            // Gather checklists in their own arrays
            $cls = array();
            foreach ($checklists as $checklist) {

                // Get checklist items
                $clitems = wekan_api_call("https://" . $wekan_domain, 
                    "/api/boards/" . $board['_id'] . "/cards/" . $card['_id'] .
                    "/checklists/" . $checklist['_id'], $auth, NULL);

                // Gather items in array
                $items = array();
                foreach ($clitems['items'] as $clitem) {

                        if (array_key_exists("isFinished", $clitem)) {
                            if ($clitem['isFinished']) {
                                $item_status = "[X]";
                            } else {
                                $item_status = "[ ]";
                            }
                            $itemstr = $item_status . " " . $clitem['title'];
                            array_push($items, $itemstr);
                        }
                }
                
                // Add to checklist array
                array_push($cls,
                    array("title" => $clitems['title'],
                          "items" => $items));
            }

            // Create summary array (only if due-date exists)
            if (array_key_exists("dueAt", $card)) {
                array_push($result,
                    array("board_id" => $board['_id'],
                        "board_name" => $board['title'],
                        "list_id" => $list['_id'],
                        "list_name" => $list['title'],
                        "lane_id" => $lane['_id'],
                        "lane_name" => $lane['title'],
                        "card_id" => $card['_id'],
                        "card_name" => $card['title'],
                        "card_desc" => $card['description'],
                        "due" => preg_replace('/-|:|\.\d+/',"",$card['dueAt']),
                        "checklist" => $cls));
            }

        }
    }
}

// Create Due date for re-login
$relogin = array(
    array("id" => "01",
        "title" => "Refresh Calendar Sync",
        "description" => "Please Login to Wekan Calendar Setup again",
        "due" => $expire,
        "url" => "https://" . $wekan_ical_domain));

// Create ical file and return to user
ical_create("https://" . $wekan_domain, $user['_id'], $result, $relogin);

?>
