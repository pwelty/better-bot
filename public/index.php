<?php

require('../vendor/autoload.php');

// echo "running";
// error_log("hello, this is a test!");
$x = print_r($_GET,true);
error_log($x);
$x = print_r($_POST,true);
error_log($x);
$post = file_get_contents('php://input');
error_log($post);

$hub_mode = $_GET['hub_mode'];
$hub_challenge = $_GET['hub_challenge'];
$hub_verify_token = $_GET['hub_verify_token'];

switch ($hub_mode) {
  case 'subscribe':
    error_log ("Subscribe");
    if ($hub_verify_token==getenv('VERIFY_TOKEN')) {
      echo($hub_challenge);
    }
    break;

}

?>
