<?php
// SPDX-FileCopyrightText: 2021 Weymeirsch und Langer GbR
// SPDX-Author: Jan Weymeirsch
//
// SPDX-License-Identifier: AGPL-3.0-only

// Load config files
list($pwd) = preg_replace('/\/[^\/]+$/', "/", get_included_files());
$conf_path = $pwd . "conf/common.php";
include($conf_path);

function wekan_api_call($url, $path, $auth) {
    $channel = curl_init();

    curl_setopt($channel, CURLOPT_URL, $url . $path);
    curl_setopt($channel, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($channel, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $auth ));

    $response = curl_exec($channel);

    curl_close($channel);

    return json_decode($response, true);
}

function ical_create($url, $userid, $caldata) {

// current time
$ctime=date('His');
$cdate=date('Ymd');

// Define Filetype
header("Content-Type: text/Calendar");
header("Content-Disposition: inline; filename=calendar.ics");

// Calendar Section
echo "BEGIN:VCALENDAR\n";
echo "VERSION:2.0\n";
$tmp="PRODID:WeKan//" . $url . "\n";
echo $tmp;
echo "METHOD:PUBLISH\n";

foreach ($caldata as $card) {

    // Event Section
    echo "BEGIN:VEVENT\n";
    $tmp="UID:" . $userid . "@" . $url . "\n";
    echo $tmp;
    $tmp="SUMMARY:" . $card['board_name'] . " -> " . $card['lane_name'] . " -> " . $card['list_name'] . ": " . $card['card_name'] . "\n";
    echo $tmp;
    $tmp="DESCRIPTION:Board: " . $card['board_name'] . "\\nSwimlane: " . $card['lane_name'] . "\\nList: " . $card['list_name'] . "\\nCard: " . $card['card_name'] . "\\n" . $card['card_desc'] . "\\n";
    foreach ($card['checklist'] as $cl) {
        $tmp=$tmp . $cl['title'] . ":\\n" . implode("\\n", $cl['items']) . "\\n\\n";
    }
    $tmp=$tmp . "\n";
    echo $tmp;
    echo "CLASS:PUBLIC\n";
    $tmp="DTSTART:" . $card['due'] . "\n";
    echo $tmp;
    $tmp="DTSTAMP:" . $cdate . "T" . $ctime . "\n";
    echo $tmp;
    $tmp="URL:" . $url . "/b/" . $card['board_id'] . "/x/" . $card['card_id'] . "\n";
    echo "END:VEVENT\n";

}

// Calendar Section
echo "END:VCALENDAR\n";
}

// Get user-input
$id=$_GET["id"];

// connect to database
$sqlcon = new mysqli($sqlhost, $sqluser, $sqlpass, $sqlname);
if ($sqlcon->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

$sqlque = "SELECT token FROM " . $sqltabl . " WHERE ical=" . $id;
$sqlres = $sqlcon->query($sqlque);
while ($row = $sqlres->fetch_assoc()) {
    $token = $row['token'];
}

// Prepare
$result = array();
$auth = "Authorization: Bearer " . $token;

// Get User Information
$user = wekan_api_call($wekan_url, "/api/user", $auth);

// Get user's boards
$boards = wekan_api_call($wekan_url, "/api/users/" . $user['_id'] . "/boards", $auth);

foreach ($boards as $board) {

    // Get lists
    $lists = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/lists", $auth);

    foreach ($lists as $list) {

        // Get cards
        $cards = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/lists/" . $list['_id'] . "/cards", $auth);
        
        // Assign cards to swimlanes
        foreach ($cards as $card) {

            // Remove HTML from description
            if (array_key_exists("description", $card)) {
                $card['description'] = preg_replace('/<br>/', '\\n', $card['description']);
                $card['description'] = preg_replace('/<li>/', '\\n- ', $card['description']);
                $card['description'] = preg_replace('/<.*?>/', '', $card['description']);
            } else {
                $card['description'] = "";
            }

            //Get card info
            $cardinfo = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/lists/" . $list['_id'] . "/cards/" . $card['_id'], $auth);

            // Get Swimlane
            $lane = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/swimlanes/" . $cardinfo['swimlaneId'], $auth);

            // Get Checklists
            $checklists = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/cards/" . $card['_id'] . "/checklists", $auth);

            $cls = array();
            foreach ($checklists as $checklist) {
                
                $clitems = wekan_api_call($wekan_url, "/api/boards/" . $board['_id'] . "/cards/" . $card['_id'] . "/checklists/" . $checklist['_id'], $auth);

                $items = array();
                foreach ($clitems['items'] as $clitem) {

                        if (array_key_exists("isFinished", $clitem)) {
                            if ($clitem['isFinished']) {
                                $item_status = "[X]";
                            } else {
                                $item_status = "[ ]";
                            }
                            $itemstring = "  " . $item_status . " " . $clitem['title'];
                            array_push($items, $itemstring);
                        }
                }

                array_push($cls,
                    array("title" => $clitems['title'],
                          "items" => $items));
            }

            // Create summary array
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
                        "due" => preg_replace('/-|:|\.\d+/', "", $card['dueAt']),
                        "checklist" => $cls));
            }

        }
    }
}

ical_create($wekan_url, $user['_id'], $result);

?>
