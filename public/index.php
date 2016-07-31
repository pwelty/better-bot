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
    $replyOption = new stdClass;
    $replyOption->content_type = "text";
    $replyOption->title = "Option 1";
    $replyOption->payload = "Option 1";
    $replyOptions[]=$replyOption;
    $replyOption->content_type = "text";
    $replyOption->title = "Option 2";
    $replyOption->payload = "Option 2";
    $replyOptions[]=$replyOption;
    sendQuickReply($senderId,"Pick something",$replyOptions);
    break;
}

function sendMessage($recipientId,$text) {
    $sendArray = array();
    $sendArray['recipient']['id']=$recipientId;
    $sendArray['message']['text']=$text;
    return postSomething($sendArray);
}

function getUserProfile($userId) {
  // "first_name": "Peter",
  // "last_name": "Chang",
  // "profile_pic": "https://fbcdn-profile-a.akamaihd.net/hprofile-ak-xpf1/v/t1.0-1/p200x200/13055603_10105219398495383_8237637584159975445_n.jpg?oh=1d241d4b6d4dac50eaf9bb73288ea192&oe=57AF5C03&__gda__=1470213755_ab17c8c8e3a0a447fed3f272fa2179ce",
  // "locale": "en_US",
  // "timezone": -7,
  // "gender": "male"
  $url = 'https://graph.facebook.com/v2.6/'.$userId.'?access_token='.getenv('PAGE_ACCESS_TOKEN');
  $response = file_get_contents($url);
  $person = json_decode($response);
  return $person;
}

function sendQuickReply($recipientId,$text,$quickReplies) {
  $sendArray = array();
  $sendArray['recipient']['id']=$recipientId;
  $sendArray['message']['text']=$text;
  $sendArray['message']['quick_replies']=$quickReplies;
  return postSomething($sendArray);
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
