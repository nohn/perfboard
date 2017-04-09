#!/usr/bin/env php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * collector/fetch.php
 *
 * This file is part of perfboard.
 *
 * perfboard is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * perfboard is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with perfboard.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @category  Nagios
 * @package   perfboard
 * @author    Sebastian Nohn <sebastian@nohn.net>
 * @copyright 2013-2014 Sebastian Nohn <sebastian@nohn.net>
 * @license   http://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link      https://github.com/nohn/perfboard
 */
require_once '../config.php';

date_default_timezone_set($timezone);

$key = file_get_contents('../keys/' . $identity . '.pem');

if (extension_loaded('openssl')) {
    $key_id = openssl_get_privatekey($key);
} else {
    require_once 'Crypt/RSA.php';
    $rsa = new Crypt_RSA();
    $key = file_get_contents('../keys/' . $identity . '.pem');
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
        for ($i = 0; $i < $samples; $i++) {
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
        $perfdata = $perfdata_tmp[$middle - 1];
        unset($perfdata_tmp);

        file_put_contents('/tmp/perfboard_' . $sensor_file, $perfdata);
        if ($logging_enabled) {
            file_put_contents($logging_path.'perfboard-'.date('Y-m-d').'.log', date('Y-m-d H:i:s e').';'.$sensor_name.';'.$object_name.';'.$perfdata."\n", FILE_APPEND);
        }
    }
}
