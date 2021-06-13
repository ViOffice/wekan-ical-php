<?php
// SPDX-FileCopyrightText: 2021 Weymeirsch und Langer GbR
// SPDX-Author: Jan Weymeirsch
//
// SPDX-License-Identifier: AGPL-3.0-only

function wekan_api_call($url, $path, $auth = NULL, $payload = NULL) {

    // initilise connection
    $channel = curl_init();

    // set channel options
    curl_setopt($channel, CURLOPT_URL, $url . $path);
    curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
    if (!is_null($auth)) {
        curl_setopt($channel, CURLOPT_HTTPHEADER,
            array('Content-Type: application/json' , $auth ));
    } else {
        curl_setopt($channel, CURLOPT_HTTPHEADER,
            array('Content-Type: application/json'));
    }
    if (!is_null($payload)) {
        curl_setopt($channel, CURLOPT_POSTFIELDS, $payload);
    }

    // retrieve response
    $response = curl_exec($channel);

    // close connection
    curl_close($channel);

    // return results
    return json_decode($response, true);
}

?>
