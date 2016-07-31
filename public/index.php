<?php

require('../vendor/autoload.php');

// echo "running";
// error_log("hello, this is a test!");
$x = print_r($_GET,true);
error_log($x);

$hub_mode = $_GET['hub.mode'];
$hub_challenge = $_GET['hub.challenge'];
$hub_verify_token = $_GET['hub.verify_token'];

switch ($hub_mode) {
  case 'subscribe':
    error_log ("Subscribe");
    if ($hub_verify_token==getenv('VERIFY_TOKEN')) {
      echo($hub_challenge);
    }
    break;

}

?>
