<?php
require_once('/home/.../db.class.php');
require_once '/home/.../vendor/autoload.php';

$tgToken = "..."; //  "user": {"id": ,"is_bot": true,"first_name": "","username": ""}
$chatId = "-123";

try {
    $bot = new \TelegramBot\Api\BotApi($tgToken);
} catch (\Throwable $th) {
    error_log(date('Y-m-d H:i:s')  . " ERROR:\n"
        . "Error create TG bot object "
        . ";\n - Error massage = " . $th->getMessage()
        . ";\n\n", 3, "/home/.../dmz_check.log");
}


try {
    $db = new \DBext("-",  "-", "-", "-");
} catch (\Throwable $th) {
    print_r("No connection to RTGK-DB!\n\n");
    print_r($th);
    die;
}

session_id( $chatId );
session_start();

$sql = "SELECT 
count(*)
,(SELECT 
count(*)
FROM public.trembita_dms_notifications
where
notified = false
and error is not null 
and
\"locked\" = false
and
created >= 
CURRENT_DATE-1) count_error
,CURRENT_DATE
FROM public.trembita_dms_notifications
where
notified = false
and
\"locked\" = false
and
created >= 
CURRENT_DATE-1
";

$data = $db->query($sql);
if($data[0]['count'] >= 50 && $data[0]['count'] <= 199){
    $statusHeader = "🟨 [Увага! Черга на відправлення перевищує 50! ]\n";
    $statusError = "low";
}elseif ($data[0]['count_error'] >= 50 && $data[0]['count_error'] <= 199) {
    $statusHeader = "✴️ [Увага! Черга на відправлення перевищує 50 і містить помилки! ]\n";
    $statusError = "low";
}elseif ($data[0]['count'] >= 200 && $data[0]['count'] <= 500) {
    $statusHeader = "🆘 [Увага! Черга на відправлення перевищує 50! ]\n";
    $statusError = "middle";
}elseif ($data[0]['count_error'] >= 200 && $data[0]['count_error'] <= 500) {
    $statusHeader = "⛔️ [Увага! Черга на відправлення перевищує 50 і містить помилки! ]\n";
    $statusError = "middle";
}elseif ($data[0]['count'] >= 501) {
    $statusHeader = "‼️🆘 [Увага! Черга на відправлення перевищує 500! ]\n";
    $statusError = "hight";
}elseif ($data[0]['count_error'] >= 501) {
    $statusHeader = "‼️⛔️ [Увага! Черга на відправлення перевищує 500 і містить помилки! ]\n";
    $statusError = "hight";
}else {
    $statusHeader = "✅ [Черга на відправлення не перевищує 50 і не містить помилки!]\n";
    $statusError = "ok";
}
$status = "Загальна черга : {$data[0]['count']}\nЧерга помилок : {$data[0]['count_error']}\nДата: {$data[0]['date']}";
if($statusError != "ok"){
    if(isset($_SESSION["message_id"])){
        $bot->deleteMessage($chatId, $_SESSION["message_id"]);
        unset($_SESSION['message_id']);
    }
    $messageId = $bot->sendMessage($chatId, $statusHeader.$status, "html", true, null, null, false)->getMessageId();
    $_SESSION["last_status"]=$statusError;
    $_SESSION["message_id"]=$messageId;
}else{
    if($_SESSION["last_status"] != "ok"){
        $bot->sendMessage($chatId, $statusHeader.$status, "html", true, null, null, false)->getMessageId();
    }
    $_SESSION["last_status"]=$statusError;
    unset($_SESSION['message_id']);
}
session_write_close();
error_log(date('Y-m-d H:i:s').";\n\n", 3, "/home/.../dmz_check.log");
die;
