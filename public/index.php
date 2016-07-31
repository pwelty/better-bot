<?php

require('../vendor/autoload.php');

// echo "running";
// error_log("hello, this is a test!");
// $x = print_r($_POST,true);
// error_log($x);

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $x = print_r($_GET,true);
    error_log($x);
    $hub_mode = $_GET['hub_mode'];
    switch ($hub_mode) {
      case 'subscribe':
        $hub_challenge = $_GET['hub_challenge'];
        $hub_verify_token = $_GET['hub_verify_token'];
        error_log ("Subscribe");
        if ($hub_verify_token==getenv('VERIFY_TOKEN')) {
          echo($hub_challenge);
        }
        break;
    }
    break;
  case 'POST':
    $post = file_get_contents('php://input');
    error_log('--'.$post.'--');
    $postObj = json_decode($post);
    // error_log(print_r($postObj,true));
    // exit;
    $entries = $postObj->entry;
    $entry = $entries[0];
    $messagings = $entry->messaging;
    $messaging = $messagings[0];
    $message = $messaging->message;
    $senderId = $messaging->sender->id;
    error_log("sender id = ".$senderId);
    error_log("message text = ".$message->text);
    // exit;
    //$recipientId = $messaging->recipient->id;
    $person = getUserProfile($senderId);
    $replyText = $message->text.' received, '.$person->first_name;
    sendMessage($senderId,$replyText);
    break;
}

function sendMessage($recipientId,$text) {
    $sendArray = array();
    $sendArray['recipient']['id']=$recipientId;
    $sendArray['message']['text']=$text;
    return postSomething($sendArray);
}

function getUserProfile($userId) {
  // https://graph.facebook.com/v2.6/<USER_ID>?access_token=PAGE_ACCESS_TOKEN
  $url = 'https://graph.facebook.com/v2.6/'.$userId.'?access_token='.getenv('PAGE_ACCESS_TOKEN');
  $response = file_get_contents($url);
  $person = json_decode($response);
  return $person;
}

function postSomething($messageData) {
  $token = getenv('PAGE_ACCESS_TOKEN');
  $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$token;
  error_log("url=".$url);

  $postData = json_encode($messageData);
  error_log("json=".$postData);

  // Set headers
  $headers = array();
  $headers['Content-Type'] = 'application/json; charset=UTF8';
  $headers['X-Accept'] = 'application/json';
  $realHeaders = array();
  foreach($headers as $k=>$v){
    $realHeaders[] = $k.": ".$v;
  }

  $options = array(
    CURLOPT_RETURNTRANSFER => true,     // return web page
    // CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "UTF8",       //
    CURLOPT_USERAGENT      => "Better Bot", // who am i
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    CURLOPT_POST 		   	   => 1,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_HTTPHEADER	   => $realHeaders,
    CURLINFO_HEADER_OUT	   => true,
  );
  $curlHandle = curl_init($url);
  curl_setopt_array($curlHandle, $options);
  $response = curl_exec($curlHandle);
  if (curl_error($curlHandle)) {
    error_log('error:' . curl_error($curlHandle));
  }
  error_log('fb response = '.$response);
  return $response;
}

?>
