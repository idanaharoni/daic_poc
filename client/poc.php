<?php


if (!isset($argv[1])) die("Company domain name is required!\n");
if (!isset($argv[2])) die("Account number is required!\n");
require __DIR__.'/daic.php';

$response = DAIC::validate($argv[1], $argv[2]);
if (isset($response['response'])) echo $response['response']."\n";
else if (isset($response['error'])) echo "ERROR: ".$response['error']."\n";

?>