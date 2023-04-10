<?php
$arr = [
    "xxx"  =>"333"
    ,["aa"   =>"a1"]
    ,"qw"   =>[222,["as"=>444],null]
    ,"obj"  =>(object) ["bb"=>"b1"]
];

$obj = new stdClass();
$obj->tes = "HHHH";

function arrayToText($value, $textus = "", $tab = ''){
    $i = $tab ? $tab : null;
    $p = $i?"\n":"";
    $tab = "\t".$i;
    $t = $i?$tab:"";
    // $textus .= "\n";
    if ( !is_string($value)) {
        foreach ($value as $k1 => $v1) {
            if ( is_object($v1) or is_array($v1)) {
                $textus .=  $p.$k1. " =>> ".$t." [ ".arrayToText($v1,'',$tab) . "]".($i?"":"\n");
            }elseif( is_string($v1) or is_numeric($v1) or is_bool($v1) or is_null($v1) ) {
                $textus .=  ($i?"\n":"").$t . $k1 ." => " . ($v1?$v1:"null") .($i?"":"\n");
            } else {
                $textus .=  "\n [[" . json_encode($k1) ."]] ===> " . json_encode($v1);
            }
            
        }
    }else{
        // $textus .= $t;
        // $textus .= $value;
    }
    
    $textus .= ($i?"\n":"") . $t;
    return $textus;
}
print_r( arrayToText($arr,"_)(____\n") );
echo "\n\n";


die;
require_once('/home/.../vendor/autoload.php');
use Telegram\Bot\Api;

$tgToken = "...";
define('CHAT_ID',"123");

// $telegram = new Api( $tgToken );


$maskLastName = preg_replace("/(?!^).(?!$)/u", "*", "Попудренко");
$maskFirstName = preg_replace("/(?!^).(?!$)/u", "*", "Джозеппе");
$maskMiddleName = preg_replace("/(?!^).(?!$)/u", "*", "Верді");
// $maskLastName = preg_replace("/(?!^).(?!$)/", "*", "Asdasdasfg");
// $maskFirstName = preg_replace("/(?!^).(?!$)/", "*", "Fsdfgdfhg");
// $maskMiddleName = preg_replace("/(?!^).(?!$)/", "*", "VBCxcvss");
$exitForBot = [
    "status" => "--some--", "text" =>
        $maskLastName
        . " "
        . $maskFirstName
        . " "
        .  $maskMiddleName
        . "; "
        . "--Бла-бла--"
    ];
print_r(json_encode($exitForBot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

die;
// // $response = $telegram->sendMessage([
// //     'chat_id' => CHAT_ID, 
// //     'text' => '⚠️<b>Test</b> message!⚠️', 
// //     'parse_mode' => 'HTML', 
// //     'disable_notification' => true, 
// //     //'reply_to_message_id' => 131, 
// // ]);
// // $messageId = $response->getMessageId();
// // sleep(2);
// //print_r($telegram->getCommands());
// $response = $telegram->editMessageText([
//     'chat_id' => CHAT_ID, 
//     'message_id' => 135, 
//     'text' => '❇️<b>Test</b> message!❇️', 
//     'parse_mode' => 'HTML', 
//     //'reply_to_message_id' => 131, 
// ]);


// // $telegram->sendLocation([
// //   'chat_id' => CHAT_ID, 
// //   'latitude' => 37.7576793,
// // 	'longitude' => -122.5076402,
// // ]);


// $response = $telegram->getMe();

// $botId = $response->getId();
// $firstName = $response->getFirstName();
// $username = $response->getUsername();

// print_r(
// [
//     'botId'=>$botId,
//     'firstName'=>$firstName,
//     'username'=>$username,
//     //'messageId'=>$messageId,
// ]

// );


$tb = new TelegramBot\Api\BotApi($tgToken);
$botMessage = "⚠️Test <b>send</b>⚠️";
$botMessage2 = "❇️Test <b>send</b>❇️";
echo "\n";
// $tb->sendMessage(CHAT_ID, $botMessage, "html", true, null, null, true)->getMessageId();
$tb->editMessageText(CHAT_ID, $tb->sendMessage(CHAT_ID, $botMessage, "html", true, null, null, true)->getMessageId(), $botMessage2, "html");
print_r([
    //$tb->getMessageId(),
]);
echo "\n";
die;
// $text = "A strange string to pass, maybe with some ø, æ, å characters.";

// foreach (mb_list_encodings() as $chr) {
//     echo mb_convert_encoding($text, 'UTF-8', $chr) . " : " . $chr . "\n";
// }
