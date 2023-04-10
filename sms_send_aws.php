<?php
//ver 1.0
require_once('/home/.../!test_scripts/vendor/autoload.php');

const S3_KEY = "";
const S3_SECRET = "";
const S3_REGION = "";
// const S3_REGION = "us-east-1";


use Aws\Sns\SnsClient; // Импортируем необходимые классы

var_dump("Send START");

$client = new SnsClient(array(
	'region' => S3_REGION,
    'version' => 'latest',
    'credentials' => array(
        'key' => S3_KEY,
        'secret' => S3_SECRET,
    )
));

// $message = 'Hello, World2!'; // Текст сообщения
$text = 'Привіт, Всесвіт!'; // Текст сообщения
$phoneNumber = '+'; // Номер телефона получателя
// $phoneNumber = '+'; // Номер телефона получателя
// $phoneNumber = '+'; // Номер телефона получателя


// Set the Sender ID for your message
$sender_id = '';

// Create a message object
$message = [
    'Message' => $text,
    'PhoneNumber' => $phoneNumber,
];

// Set the Sender ID for the message
$message['MessageAttributes'] = [
    'AWS.SNS.SMS.SenderID' => [
        'DataType' => 'String',
        'StringValue' => $sender_id,
    ],
];

try {
	$result = $client->publish($message);
} catch (AwsException $e) {
    // output error message if fails
    var_dump($e->getMessage());
} 

echo $result['MessageId']; // Выводим идентификатор сообщения
echo "\n";

var_dump("OK\r");