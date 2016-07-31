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
    $senderId = $messaging->sender->id;
    $person = getUserProfile($senderId);


    if (isset($messaging->message)) {

      $message = $messaging->message;
      if (isset($message->quick_reply)) {
        $payload = $message->quick_reply->payload;
        $replyText = $payload.' was clicked, '.$person->first_name;
        sendTextMessage($senderId,$replyText);
      } else {
        if (strtolower($message->text)=='help') {
          $button1 = new stdClass;
          $button1->type = 'web_url';
          $button1->url = 'http://www.livingyourbetter.com/';
          $button1->title = 'Visit the LYB website';
          $button2 = new stdClass;
          $button2->type = 'postback';
          $button2->title = 'What\'s Beachbody?';
          $button2->payload = 'BB_Q';
          $buttons = array($button1,$button2);
          sendButtonTemplate($senderId,'So you need some help? No problem! What can I do for you?',$buttons);
        } else {
          $replyText = $message->text.' received, '.$person->first_name;
          sendTextMessage($senderId,$replyText);
          $replyOption1 = new stdClass;
          $replyOption1->content_type = "text";
          $replyOption1->title = "Option 1";
          $replyOption1->payload = "Option 1";
          $replyOptions[]=$replyOption1;
          $replyOption2 = new stdClass;
          $replyOption2->content_type = "text";
          $replyOption2->title = "Option 2";
          $replyOption2->payload = "Option 2";
          $replyOptions[]=$replyOption2;
          sendQuickReply($senderId,"Pick something",$replyOptions);
        }
      }

    } elseif (isset($messaging->postback)) {

        $postback = $messaging->postback;
        $payload = $postback->payload;
        $replyText = $payload.' was selected, '.$person->first_name;
        sendTextMessage($senderId,$replyText);

    } else {
      // I don't know what else there is to do!
    }

    break;
}

function sendButtonTemplate($recipientId,$text,$buttons) {
  $message = new stdClass;
  $message->attachment->type='template';
  $message->attachment->payload->template_type = 'button';
  $message->attachment->payload->text = $text;
  $message->attachment->payload->buttons = $buttons;
  sendMessage($recipientId,$message);
}
function sendTextMessage($recipientId,$text) {
  $message = new stdClass;
  $message->text = $text;
  sendMessage($recipientId,$message);
}

function sendMessage($recipientId,$message) {
  error_log("sender id = ".$recipientId);
  error_log("message = ".json_encode($text));
  $sendArray = array();
  $sendArray['recipient']['id']=$recipientId;
  $sendArray['message'] = $message;
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
  $message = new stdClass;
  $message->text = $text;
  $message->quick_replies = $quickReplies;
  sendMessage($recipientId,$message);
  // $sendArray = array();
  // $sendArray['recipient']['id']=$recipientId;
  // $sendArray['message']['text']=$text;
  // $sendArray['message']['quick_replies']=$quickReplies;
  // return postSomething($sendArray);
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
