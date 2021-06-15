<?php
// SPDX-FileCopyrightText: 2021 Weymeirsch und Langer GbR
// SPDX-Author: Jan Weymeirsch
//
// SPDX-License-Identifier: AGPL-3.0-only

function ical_create($domain, $userid, $caldata, $extraevents = NULL) {

    // current time
    $ctime=date('His');
    $cdate=date('Ymd');

    // Define Filetype
    header("Content-Type: text/Calendar");
    header("Content-Disposition: inline; filename=calendar.ics");

    // Calendar Section
    echo "BEGIN:VCALENDAR\n";
    echo "VERSION:2.0\n";
    $tmp="PRODID:wekan//" . $domain . "\n";
    echo $tmp;
    echo "METHOD:PUBLISH\n";

    // Event Section for each card in stack
    foreach ($caldata as $card) {

        echo "BEGIN:VEVENT\n";
        $tmp="UID:" . $card['card_id'] . "@" . $domain . "\n";
        echo $tmp;
        // Headline (Summary)
        $tmp="SUMMARY:" . $card['board_name'] . " -> " . $card['lane_name'] .
            " -> " . $card['list_name'] . ": " . $card['card_name'] . "\n";
        echo $tmp;
        // Body (Description)
        $tmp="DESCRIPTION:Board: " . $card['board_name'] . "\\nSwimlane: " .
            $card['lane_name'] . "\\nList: " . $card['list_name'] .
            "\\nCard: " . $card['card_name'] . "\\n" . $card['card_desc'] .
            "\\n";
        // Add Checklist if available
        foreach ($card['checklist'] as $cl) {
            $tmp=$tmp . $cl['title'] . ":\\n" . implode("\\n", $cl['items']) . "\\n\\n";
        }
        $tmp=$tmp . "\n";
        echo $tmp;
        echo "CLASS:PUBLIC\n";
        // Due Date
        $tmp="DTSTART:" . $card['due'] . "\n";
        echo $tmp;
        $tmp="DTSTAMP:" . $cdate . "T" . $ctime . "\n";
        echo $tmp;
        // URL to card
        $tmp="URL:https://" . $domain . "/b/" . $card['board_id'] . "/x/" . $card['card_id'] . "\n";
        echo $tmp;
        echo "END:VEVENT\n";

    }

    if (!is_null($extraevents)) {
        foreach ($extraevents as $event) {
            echo "BEGIN:VEVENT\n";
            $tmp="UID:" . $event['id'] . "@" . $domain . "\n";
            echo $tmp;
            // Summary
            $tmp="SUMMARY:" . $event['title'] . "\n";
            echo $tmp;
            // Description
            $tmp="DESCRIPTION:" . $event['description'] . "\n";
            echo $tmp;
            // Due Date
            $tmp="DTSTART:" . date("Ymd", $event['due']) . "T" . 
                date("His", $event['due']) . "\n";
            echo $tmp;
            $tmp="DTSTAMP:" . $cdate . "T" . $ctime . "\n";
            echo $tmp;
            // URL
            $tmp="URL:" . $event['url'] . "\n";
            echo $tmp;
            echo "END:VEVENT\n";
        }
    }

    // Calendar Section
    echo "END:VCALENDAR\n";

}

?>
