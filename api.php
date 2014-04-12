<?php

//API CONFIGURATION BELOW
//No Configs
//POLR API - by http://github.com/cydrobolt/polr
//@Author: Cydrobolt
$reqargs['nosession'] = true;
require_once('req.php'); //Fetch Config
require_once('dnsbl.php'); //Load Google SafeBrowsing Script

$protocol = '://';
if (!strstr($_REQUEST['url'], $protocol)) {
    $urlr = "http" . $protocol . $_POST['urlr']; //add http:// if :// not there
}

$dnsbl = new dnsbl(); //create a gsb object
if (is_string($_REQUEST['apikey']) && is_string($_REQUEST['action']) && is_string($_REQUEST['url'])) {
    $apikey = $mysqli->real_escape_string($_REQUEST['apikey']); //Sanitize input
    $action = $mysqli->real_escape_string($_REQUEST['action']);
    $url_api = $mysqli->real_escape_string($_REQUEST['url']);
} else {
    header("HTTP/1.0 400 Bad Request");
    die("Error: No value specified, or wrong data type.");
}

//checking API key:
$query = "SELECT valid FROM api WHERE apikey='$apikey'";
$result = $mysqli->query($query) or showerror();
$validrow = mysqli_fetch_assoc($result);
//check if valid
if (!$validrow['valid']) {
    $api_key_valid = 0;
} else {
    $api_key_valid = 1;
}

if (!$api_key_valid) {
    header("HTTP/1.0 401 Forbidden"); //Access denied - invalid key
    die('<h1>401 Forbidden</h1>');
}

if (!filter_var($url_api, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) && $action != "lookup") {
    header("HTTP/1.0 400 Bad Request");
    echo "Error: URL is not valid"; //URL not well formatted, but allow if action is lookup
    die();
}
//Check if URL given is malware/phishing

$isbl = $dnsbl->isbl($urlr);
if ($isbl === "malware" || $isbl === "phishing") {
    header("HTTP/1.0 401 Forbidden");
    echo "Polr does not shorten potentially malicious URLs"; //If link tests positive to possible malware/phish, then block
    die();
}

function lookup($urltl) {
    global $mysqli;
    $val = $mysqli->real_escape_string($urltl);
    $query = "SELECT rurl FROM redirinfo WHERE baseval='{$val}'";
    $result = $mysqli->query($query) or die("QUERY ERROR");
    $row = mysqli_fetch_assoc($result);
    return $row['rurl'];
}

function shorten($urlr) {
    global $mysqli;
    global $wsa;
    global $apikey;
    $query1 = "SELECT rid FROM redirinfo WHERE rurl='{$urlr}'";
    $result = $mysqli->query($query1);
    $row = mysqli_fetch_assoc($result);
    $existing = $row['rid'];
    if (!$existing) {
        $query1 = "SELECT MAX(rid) AS rid FROM redirinfo;";
        $result = $mysqli->query($query1);
        $row = mysqli_fetch_assoc($result);
        $ridr = $row['rid'];
        $baseval = base_convert($ridr + 1, 10, 36);
        $query2 = "INSERT INTO redirinfo (baseval,rurl,user) VALUES ('{$baseval}','APIKEY-{$apikey}','{$urlr}');";
        $result2r = $mysqli->query($query2) or showerror();
        return "http://{$wsa}/{$baseval}";
    } else {
        $query1 = "SELECT baseval FROM redirinfo WHERE rurl='{$urlr}'";
        $result = $mysqli->query($query1);
        $row = mysqli_fetch_assoc($result);
        $baseval = $row['baseval'];
        return "http://{$wsa}/{$baseval}";
    }
}

//api action start

if ($action == "shorten") {
    echo shorten($url_api);
    die();
} else if ($action == "lookup") {
    $looked_up_url = lookup($url_api);
    if (!$looked_up_url) {
        header("HTTP/1.0 404 Not Found");
        die("<h1>404 Not Found</h1>");
    } else {
        echo $looked_up_url;
    }
    die();
} else {
    die("Invalid Action");
}

