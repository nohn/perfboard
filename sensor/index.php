<?php

// Ubuntu
$MTR = '/usr/bin/mtr';
$CHECK_HTTP = '/usr/lib/nagios/plugins/check_http';
// Voyage
// $MTR = '/usr/local/sbin/mtr';

$return = array();

function standard_deviation($std) {
    $total = 0;
    while (list($key, $val) = each($std)) {
        $total+= $val;
    }
    reset($std);
    $mean = $total / count($std);
    while (list($key, $val) = each($std)) {
        $sum+= pow(($val - $mean), 2);
    }
    $num = count($std) - 1;
    if ($num > 0) {
        $var = sqrt($sum / $num);
    } else {
        $var = 0;
    }
    return $var;
}

$performance = array();

$url = parse_url($_GET['url']);
$time_start_dns = microtime(true);
$ip = gethostbyname($url['host']);
$time_end_dns = microtime(true);
$time_dns = round(($time_end_dns - $time_start_dns) * 1000, 6);
$performance['time_dns_millis'] = $time_dns;

$cert = file_get_contents('../keys/' . $_GET['identity'] . '.pub');
$pubkeyid = openssl_get_publickey($cert);

$parameters = array(
    'url' => $_GET['url'],
    'max' => $_GET['max'],
    'identity' => $_GET['identity'],
    'time' => $_GET['time'],
    'string' => $_GET['string']);
$http_query = http_build_query($parameters);

$signature = base64_decode(str_pad(strtr($_GET['signature'], '-_', '+/'), strlen($_GET['signature']) % 4, '=', STR_PAD_RIGHT));

if (openssl_verify($http_query, $signature, $pubkeyid) != 1) {
    die('can\'t verify signature');
}

if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    die('invalid input');
}
if (!isset($_GET['count'])) {
    $total_ping_count = 5;
} else {
    $total_ping_count = (int) $_GET['count'];
}
if ($total_ping_count > 20) {
    die('count must not be greater than 20');
}

if (isset($_GET['max']) && $_GET['max'] != '') {
    $maxmillis = (float) $_GET['max'];
} else {
    $maxmillis = 1000;
}
if (isset($_GET['trace']) && $_GET['trace'] != '') {
    $trace = $_GET['trace'];
} else {
    $trace = 'onerror';
}

$check_output = array();
if ($url['scheme'] == 'https') {
    $ssl = '--ssl';
}

$path = $url['path'];

if (isset($url['query']) && ($url['query'] != '')) {
    $path .= '?' . $url['query'];
}

if (isset($string)) {
    $checkstring = '-s ' . $string;
}

exec($CHECK_HTTP . ' -E -A "Mozilla/5.0 (compatible; SensoryNode 0.1)" ' . $checkstring . ' ' . $ssl . ' -I ' . $ip . ' -H ' . $url['host'] . ' -u ' . $path, $check_output);

if (empty($check_output)) {
    die('monitoring request failed');
}
$perfdata_temp = explode('|', $check_output[0]);
$perfdata_temp = $perfdata_temp[1];
$perfdata_temp = explode(' ', $perfdata_temp);
foreach ($perfdata_temp as $perfvalue) {
    $perfdata_temp2 = explode(';', $perfvalue);
    $perfdata_temp2 = explode('=', $perfdata_temp2[0]);
    if ($perfdata_temp2[0] == 'time') {
        $performance_temp3 = ($perfdata_temp2[1] * 1000) + $time_dns;
    } else if ($perfdata_temp2[0] == 'size') {
        $performance_temp4 = $perfdata_temp2[1] * 1;
    } else {
        $performance[$perfdata_temp2[0] . '_millis'] = $perfdata_temp2[1] * 1000;
    }
}
$performance['time_total_millis'] = $performance_temp3;
$performance['size_bytes'] = $performance_temp4;

$mtr_output = array();
$result = array();
$return['performance'] = $performance;

if (($trace == 'always') || (($trace == 'onerror') && $performance['time_total_millis'] >= $maxmillis)) {
    exec($MTR . ' --raw -n ' . $ip . ' -c ' . $total_ping_count, $mtr_output);
    $maxseen = 0;
    foreach ($mtr_output as $line) {
        if ($maxseen < $rows[1])
            $maxseen = $rows[1];
        $rows = explode(' ', $line);
        if ($rows[0] == 'h') {
            $result[$rows[1]]['ip'] = $rows[2];
        } else if ($rows[0] == 'p') {
            $result[$rows[1]]['pings'][] = $rows[2] / 1000;
        }
    }
    for ($i = 0; $i <= $maxseen; $i++) {
        if ($result[$i] == null) {
            $hop = $i + 1;
            $result[$i]['ip'] = null;
            continue;
        }
        $result[$i]['stats']['ping_best'] = 9999999999999;
        $result[$i]['stats']['ping_worst'] = false;
        $ping_count = 0;
        $ping_sum = 0;
        foreach ($result[$i]['pings'] as $ping) {
            $ping_count++;
            $ping_sum+= $ping;
            if ($result[$i]['stats']['ping_best'] > $ping)
                $result[$i]['stats']['ping_best'] = round($ping / 1000, 2);
            if ($result[$i]['stats']['ping_worst'] < $ping)
                $result[$i]['stats']['ping_worst'] = round($ping / 1000, 2);
        }
        $result[$i]['stats']['ping_average'] = round($ping_sum / ($ping_count), 2);
        $result[$i]['stats']['ping_count'] = $ping_count;
        $result[$i]['stats']['ping_stddev'] = round(standard_deviation($result[$i]['pings']), 2);
        $result[$i]['stats']['ping_loss'] = round((1 - ($ping_count / $total_ping_count)) * 100, 2);
    }
    ksort($result);
    $num_hops = count($result);
    for ($i = $maxseen; $i >= 0; $i--) {
        if ($result[$i]['ip'] == $result[$i - 1]['ip']) {
            unset($result[$i]);
        }
    }
    if ($result[$maxseen - 1]['ip'] != $ip) {
        $result[$maxseen]['ip'] = null;
    }
    $return['mtr'] = $result;
}

echo json_encode($return);
