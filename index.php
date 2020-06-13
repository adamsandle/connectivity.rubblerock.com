<?php
header('Content-type: application/json');
function clientList($var){
    return strpos($var, "CLIENT_LIST") !== false;
}

function routeList($var){
    return strpos($var, "ROUTING_TABLE") !== false;
}

$output = shell_exec('expect vpn.sh');

$output = rtrim(str_replace("\t", ",", $output));

$lines = explode("\r\n", $output);

$clientRows = array_values(array_filter($lines, "clientList"));
$routeRows = array_values(array_filter($lines, "routeList"));

function getArray($items, $title){
    $array = [];
    $headers = explode(",", str_replace("HEADER," . $title . ",","",$items[0]));
    foreach (array_slice($items,1) as $line){
        $values = explode(",", str_replace($title . ",","",$line));
        $row = array_combine($headers, $values);
        array_push($array, $row);
    }
    return $array;
}

$clients = getArray($clientRows, "CLIENT_LIST");
$routes = getArray($routeRows, "ROUTING_TABLE");

$result = "";
$directConnectionIndex = array_search($_SERVER['REMOTE_ADDR'], array_column($clients, "Virtual Address"));
$routeConnectionIndex = array_search($_SERVER['REMOTE_ADDR'] . "C", array_column($routes, "Virtual Address"));
if (is_int($directConnectionIndex)){
    $result = $clients[$directConnectionIndex];
}
else if (is_int($routeConnectionIndex)){
    $commonName = $routes[$routeConnectionIndex]["Common Name"];
    $gatewayConnectionIndex = array_search($commonName, array_column($clients, "Common Name"));
    $result = $clients[$gatewayConnectionIndex];
}

$result["Client IP"] = $_SERVER['REMOTE_ADDR'];
$result["Client Hostname"] = gethostbyaddr($_SERVER['REMOTE_ADDR']);

echo json_encode($result, JSON_PRETTY_PRINT);
?>
