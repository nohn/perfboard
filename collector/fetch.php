#!/usr/bin/env php
<?php

require_once '../config.php';

$key = file_get_contents('../keys/'.$identity.'.pem');

if (extension_loaded('openssl')) {
    $key_id = openssl_get_privatekey($key);
} else {
    require_once 'Crypt/RSA.php';
    $rsa = new Crypt_RSA();
    $key = file_get_contents('../keys/'.$identity.'.pem');
    $rsa->loadKey($key);
    $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
}

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
        if (extension_loaded('openssl')) {
            openssl_sign($http_query, $signature, $key);
        } else {
            $signature = $rsa->sign($http_query);
        }
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $parameters['signature'] = $signature;
        $http_query = http_build_query($parameters);
        $sensor_url = $sensor_data['url'] . '?' . $http_query;
	$perfdata_tmp = array();
	for ($i=0; $i < $samples; $i++) {
            try {
                if (!$response = file_get_contents($sensor_url)) {
                    throw new Exception('Can\'t open ' . $sensor_url);
                }
                if (!$json = json_decode($response)) {
                    throw new Exception('Can\'t decode. Sensory Node Response: ' . $response);
                }
                if (isset($json->performance->time_total_millis)) {
                    $perfdata_tmp[] = (int) $json->performance->time_total_millis;
                } else {
                    $perfdata_tmp[] = 0;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

	// get the median
        rsort($perfdata_tmp);
        $middle = round(count($perfdata_tmp) / 2);
        $perfdata = $perfdata_tmp[$middle-1];
	unset($perfdata_tmp);

        file_put_contents('/tmp/perfboard_' . $sensor_file, $perfdata);
    }
}
