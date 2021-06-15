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

// Load external Libraries
$tpqr_path = $pwd . "libs/3rdparty/php-qrcode/vendor/autoload.php";
include($tpqr_path);
use chillerlan\QRCode\{QRCode, QROptions};

// Get user-input
$username=$_POST["username"];
$password=$_POST["password"];
$error=$_GET['error'];

// if username and password are provided, try to login.
if ($username != "" && $password != "") {

    // construct payload (form data)
    $data = array(
        'username' => $username,
        'password' => $password);
    $payload = json_encode($data);

    // Make API call
    $result = wekan_api_call("https://" . $wekan_domain, "/users/login", NULL, $payload);

    // If response includes error, reload the page with error-message
    if (array_key_exists("error", $result) || $result == NULL) {
        header("Location: https://" . $wekan_ical_domain . "/?error=true");
    } else {

        // create ical-url (first 16 digits of hexdec'ed sha1 from user + pw)
        $icalstring = hexdec(substr(sha1($username . " " . $password), 0, 15));

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
                    header("Location: https://" . $wekan_ical_domain . "/?error=true");
                }
            }
        } else {
            // write to database (invite-id, admin-id, room-id, date, time)
            $sqlque = "INSERT INTO " . $sqltabl . " (username, token, expire, ical)
                VALUES ('" . $username . "','" . $result['token'] . "'," . $expire . ","
                . $icalstring . ")";
            if ($sqlcon->query($sqlque) === FALSE) {
                header("Location: https://" . $wekan_ical_domain . "/?error=true");
            }
        }
        // Print HTML

        $sub_url = $wekan_ical_domain . "/cal.php?id=" . $icalstring;
        print("<html><head>" .
            "<title>Wekan Calendar Setup</title>" .
            "<link rel='icon' href='https://" . $wekan_domain . "/favicon.ico' " .
            "type='image/x-icon' sizes='16x16 32x32' />" .
            "<meta name='viewport' content='width=device-width, initial-scale=1'>" .
            "<style>" .
            ".container{width:100%;max-width:800px;margin:auto auto auto auto;" .
            ".center{margin-left:auto;margin-right:auto;text-align:center;}" .
            "border: 3px solid #f1f1f1;}" .
            "*{text-align:center;}" .
            "ul,li{text-align:left;}" .
            "a{color:#04AA6D;}" .
            "input[type=submit]{background-color:#04AA6D;color:#fff;padding:14px 20px;" .
            "margin: 8px 0;border:none;width:100%;}".
            "input[type=submit]:hover{opacity:0.8;}" .
            ".qrcode{height:auto;width:100%;max-width:250px;}" .
            "</style></head>" .
            "<body><div class='container'>" .
            "<html><body><h1>Success!</h1>" .
            "<p>You successfully created a sync for your account.</p>" .
            "<h3>Username</h3><p>" . $username . "</p><br>" .
            "<h3>Download Calendar File</h3>" . 
            "<a href='https://" . $sub_url . "'>" . 
            "<input type='submit' value='Download'></a><br>" .
            "<h3>Subscribe to calendar</h3>" .
            "<a href=webcal://" . $sub_url . "'>" .
            "<input type='submit' value='Download'></a><br>" .
            "<h3>Expire</h3><p>" . date('Y-m-d, H:i', $expire) . "</p><br>" . 
            "<p>Subscribe via QR-code:<p><img src='" . 
            (new QRCode)->render("webcal://" . $sub_url) .
            "' alt='QR-Code' class='qrcode center'/>" .
            "</div></body></html>");
    }
} else {
    if ($error == "true") {
        print("ERROR: Wrong Username & Password?\n");
    }
    print("<html><head>" .
        "<title>Wekan Calendar Setup</title>" .
        "<link rel='icon' href='https://" . $wekan_domain . "/favicon.ico' " .
        "type='image/x-icon' sizes='16x16 32x32' />" .
        "<meta name='viewport' content='width=device-width, initial-scale=1'>" .
        "<style>" .
        ".container{width:100%;max-width:800px;margin:auto auto auto auto;}" .
        "*{text-align:center;}" .
        "input[type=text],input[type=password]{width:100%;padding:12px 20px;" .
        "margin:8px 0;display:inline-block;border:1px solid #ccc;" .
        "box-sizing:border-box;}" .
        "input[type=submit]{background-color:#04AA6D;color:#fff;padding:14px 20px;" .
        "margin: 8px 0;border:none;width:100%;}" .
        "input[type=submit]:hover{opacity:0.8;}" .
        "</style></head>" .
        "<body><div class='container'>" .
        "<h1>Wekan Calendar Setup</h1><form action='' method='POST'>" .
        "<label for='username'><strong>Username</strong></label><br>" .
        "<input type='text' id='username' name='username' required='true'><br><br>" .
        "<label for='password'><strong>Password</strong></label><br>" .
        "<input type='password' id='password' name='password' required='true'>" .
        "<br><br><input type='submit' value='Login'></form>" .
        "</div></body></html>");
}
?>

