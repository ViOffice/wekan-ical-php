<?php
// SPDX-FileCopyrightText: 2021 Weymeirsch und Langer GbR
// SPDX-Author: Jan Weymeirsch
//
// SPDX-License-Identifier: AGPL-3.0-only

// Load config files
list($pwd) = preg_replace('/\/[^\/]+$/', "/", get_included_files());
$conf_path = $pwd . "conf/common.php";
include($conf_path);

// Get user-input
$username=$_POST["username"];
$password=$_POST["password"];
$error=$_GET['error'];

// if username and password are provided, try to login.
if ($username != "" && $password != "") {

    // initilize API call
    $channel = curl_init(); # initialize curl object
    curl_setopt($channel, CURLOPT_URL, $wekan_url . "/users/login"); # set url
    curl_setopt($channel, CURLOPT_RETURNTRANSFER, TRUE); # receive server response

    // construct payload (form data)
    $data = array(
        'username' => $username,
        'password' => $password);
    $payload = json_encode($data);

    // Attach encoded JSON string to the POST fields
    curl_setopt($channel, CURLOPT_POSTFIELDS, $payload);
    // Set the content type to application/json
    curl_setopt($channel, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Execute the POST request
    $response = curl_exec($channel);

    // Close connection
    curl_close($channel);

    // read json response
    $result = json_decode($response, true);

    if (array_key_exists("error", $result) || $result == NULL) {
        header("Location: " . $wekan_ical_url . "/?error=true");
    } else {

        // create ical-url
        $icalstring = hexdec( substr(sha1($username . " " . $password), 0, 15) );

        // convert time stamp to UNIX
        $expire = strtotime($result['tokenExpires']);

        // connect to database
        $sqlcon = new mysqli($sqlhost, $sqluser, $sqlpass, $sqlname);
        if ($sqlcon->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check whether There is already a login for this user
        $sqlque = "SELECT token FROM " . $sqltabl . "WHERE username=" .
            $username;

        // If it exists, update the information
        if ($sqlcon->query($sqlque) === TRUE) {
            if (count($sqlres->fetch_assoc()) > 0) {
                $sqlque = "UPDATE " . $sqltabl . " SET token='" .
                    $result['token'] . "', expire=" . $expire . "," .
                    "ical=" . $icalstring . " WHERE username='" .
                    $username . "')";
                if ($sqlcon->query($sqlque) === FALSE) {
                    header("Location: " . $wekan_ical_url . "/?error=true");
                }
            }
        } else {
            // write to database (invite-id, admin-id, room-id, date, time)
            $sqlque = "INSERT INTO " . $sqltabl . " (username, token, expire, ical)
                VALUES ('" . $username . "','" . $result['token'] . "'," . $expire . ","
                . $icalstring . ")";
            if ($sqlcon->query($sqlque) === FALSE) {
                header("Location: " . $wekan_ical_url . "/?error=true");
            }
        }
        // Print HTML
        print("<html><body><h1>Success!</h1>" .
            "<p>You successfully created a sync for your account.</p>" .
            "<ul><li>Username: " . $username .
            "</li><li>Calendar-URL: <a href='webcal://" . $wekan_ical_domain .
            "/cal.php?id=" . $icalstring . "'>" . $wekan_ical_url .
            "/cal.php?id=" . $icalstring . "</a></li><li>Expire: " .
            date('Y-m-d, H:i', $expire) . "</li></ul></body></html>");
    }
} else {
    if ($error == "true") {
        print("ERROR: Wrong Username & Password?\n");
    }
    print("<html><body>" .
        "<h1>" . $indh1 . "</h1><form action='' method='POST'>" .
        "<label for='username'><strong>Username</strong></label><br>" .
        "<input type='text' id='username' name='username' required='true'><br><br>" .
        "<label for='password'><strong>Password</strong></label><br>" .
        "<input type='password' id='password' name='password' required='true'>" .
        "<br><br><input class='button' type='submit' value='Login'></form>" .
        "</body></html>");
}
?>

