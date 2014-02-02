#!/usr/bin/env php
<?php

require_once '../config.php';

foreach ($objects as $object_name => $object_data) {
    foreach ($sensors as $sensor_name => $sensor_data) {
        $sensor_file = sha1($sensor_name . $object_name);
        $parameters = array(
            'url' => $object_data['url'],
            'max' => $object_data['crit'],
            'identity' => $identity,
            'time' => time(),
            'string' => $object_data['string']);

        $http_query = http_build_query($parameters);
        $key = file_get_contents('../keys/'.$identity.'.pem');
        $key_id = openssl_get_privatekey($key);
        openssl_sign($http_query, $signature, $key);
        openssl_free_key($key_id);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $parameters['signature'] = $signature;
        $http_query = http_build_query($parameters);
        $sensor_url = $sensor_data['url'] . '?' . $http_query;
        if (!$response = file_get_contents($sensor_url)) {
            throw new Exception('Can\'t open ' . $sensor_url);
        }
        if (!$json = json_decode($response)) {
            throw new Exception('Can\'t decode. Sensory Node Response: ' . $response);
        }
        if (isset($json->performance->time_total_millis)) {
            $perfdata = (int) $json->performance->time_total_millis;
        } else {
            $perfdata = 0;
        }
        file_put_contents('/tmp/perfboard_' . $sensor_file, $perfdata);
    }
}