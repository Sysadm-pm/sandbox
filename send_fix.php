<?php
// require_once '/home/.../vendor/autoload.php';
// Заборонити кешування wsdl-файла
ini_set("soap.wsdl_cache_enabled", 0);
ini_set('default_socket_timeout', 5000);

// // Параметри UXP TEST
// const UXP_SERVER_ADDRESS = "http://";
// const XROAD_INSTANCE = "-";
// const MEMBER_CLASS = "";
// const MEMBER_CODE = "";
// const SUBSYSTEM_CODE = "";
// const SERVICE_CODE = "";
// const USERID_CODE = ""; //<===== 
// const KMDA_MEMBER_CODE = "";
// const KMDA_SUBSYSTEM_CODE = "";


// Параметри UXP
const UXP_SERVER_ADDRESS = "http://";
const XROAD_INSTANCE = "";
const MEMBER_CLASS = "";
const MEMBER_CODE = "";
const SUBSYSTEM_CODE = "";
const SERVICE_CODE = "";
const USERID_CODE = ""; //<===== 
const KMDA_MEMBER_CODE = "";
const KMDA_SUBSYSTEM_CODE = "";

// Посилання для отримання WSDL Web-service від UXP сервера

$wsdlurl = UXP_SERVER_ADDRESS . "/wsdl?xRoadInstance=" . XROAD_INSTANCE
    . "&memberClass=" . MEMBER_CLASS . "&memberCode=" . MEMBER_CODE
    . "&subsystemCode=" . SUBSYSTEM_CODE . "&serviceCode=" . SERVICE_CODE;

// Структура параметрів даних запиту

class ParamsInputResult
{
    public $requestID;
    public $requestTime;
    public $sourceRequestID;
    public $requestData;
    public $requestDataSign;
}

// Функція для генерації ID запиту до UXP сервера
function generate_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

//$botMessage = "";

// Клас SoapClient
class TrembitaSoapClient extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}

// Функція для тестування запиту до Web service
function callPersonInfoService($requestID, $sourceRequestID, $requestData, $requestDataSign, $status = '')
{

    //return "Функцію відправки тимчасово відімкненно.";
    // Посилання на xsd для запитів до UXP сервера
    $nsxroad = "http://x-road.eu/xsd/xroad.xsd";
    $nsident = "http://x-road.eu/xsd/identifiers";
    global $wsdlurl;
    global $fullPathToWsdl;
    $wsdl    = $wsdlurl;
    $wsdl    = $fullPathToWsdl;

    // Створюємо SoapClient
    $soapclient = new TrembitaSoapClient(
        $wsdl,
        array(
            'location' => UXP_SERVER_ADDRESS,
            'uri' => UXP_SERVER_ADDRESS,
            'trace' => true,
            'connection_timeout' => 5000,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'keep_alive' => false,
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_1,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'allow_self_signed' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]),
        )
    );

    // Формуємо заголовок запиту
    $xml = '<ns2:service ns3:objectType="SERVICE" xmlns:ns3="' . $nsident . '">'
        . "<ns3:xRoadInstance>" . XROAD_INSTANCE . "</ns3:xRoadInstance>"
        . "<ns3:memberClass>" . MEMBER_CLASS . "</ns3:memberClass>"
        . "<ns3:memberCode>" . MEMBER_CODE . "</ns3:memberCode>"
        . "<ns3:subsystemCode>" . SUBSYSTEM_CODE . "</ns3:subsystemCode>"
        . "<ns3:serviceCode>" . SERVICE_CODE . "</ns3:serviceCode>"
        . "</ns2:service>";

    $servicesoapvar = new SoapVar($xml, XSD_ANYXML);

    $xml = '<ns2:client ns3:objectType="SUBSYSTEM" xmlns:ns3="' . $nsident . '">'
        . "<ns3:xRoadInstance>" . XROAD_INSTANCE . "</ns3:xRoadInstance>"
        . "<ns3:memberClass>" . MEMBER_CLASS . "</ns3:memberClass>"
        . "<ns3:memberCode>" . KMDA_MEMBER_CODE . "</ns3:memberCode>"
        . "<ns3:subsystemCode>" . KMDA_SUBSYSTEM_CODE . "</ns3:subsystemCode>"
        . "</ns2:client>";

    $clientsoapvar = new SoapVar($xml, XSD_ANYXML);

    $clientHeader = new SoapHeader($nsxroad, 'client', $clientsoapvar);
    $serviceHeader = new SoapHeader($nsxroad, 'service', $servicesoapvar);
    $tUuid = generate_uuid();
    $idHeader = new SoapHeader($nsxroad, 'id', $tUuid);
    $protocolVersionHeader = new SoapHeader($nsxroad, 'protocolVersion', '4.0');
    $userIdHeader = new SoapHeader($nsxroad, 'userId', USERID_CODE);

    // Встановлюємо заголовок
    $soapclient->__setSoapHeaders(array(
        $clientHeader, $serviceHeader, $idHeader,
        $protocolVersionHeader, $userIdHeader
    ));

    // Заповнюємо вхідні дані
    //echo "WSDL geted.Headers complite.\n";
    $objDateTime                = new DateTime('NOW');
    $param0 = new SoapParam(
        $requestID,
        "requestID"
    );
    $param1 = new SoapParam(
        $objDateTime->format('c'),
        "requestTime"
    );
    $param2 = new SoapParam(
        $sourceRequestID,
        "sourceRequestID"
    );
    $param3 = new SoapParam(
        $requestData,
        "requestData"
    );
    $param4 = new SoapParam(
        $requestDataSign,
        "requestDataSign"
    );

    // Робимо запит до Web service
    try {
        $res = $soapclient->InformPlaceResidence($param0, $param1, $param2, $param3, $param4);
        echo "\n";
        print_r( $soapclient->__getLastRequest() );
        echo "\n\n";
        print_r( $soapclient->__getLastResponse() );
        echo "\n";
        //$res = ["status" => "10", "statusText" => "statusText",];
    } catch (SoapFault $fault) {
        // Виводимо помилку від сервера у результат
        // $bot->sendMessage(
        //     $chatId,
        //     "❌[" . $requestID . "][" . $sourceRequestID . "]Помилка выдправки:\n[" . json_encode($fault, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "]\n"
        // );
        return "\n[" . $fault->faultcode . ":" . $fault->faultstring . "]\n";
    }

    error_log(
        date('Y-m-d H:i:s P') . " [" . $requestID . "][" . $sourceRequestID . "]\n" . " LastRequest=\n" . $soapclient->__getLastRequest() . "\n",
        3,
        "/home/.../!test_scripts/send_dms_raw.log"
    );
    error_log(
        date('Y-m-d H:i:s P') . " [" . $requestID . "][" . $sourceRequestID . "]\n" . " LastResponse=\n" . $soapclient->__getLastResponse() . "\n\n",
        3,
        "/home/.../!test_scripts/send_dms_raw.log"
    );

    // try {
    //     $bot->sendMessage($chatId, $botMessage);
    // } catch (Exception $fault) {
    //     throw new Exception('Деление на ноль.');
    //     // Виводимо помилку від сервера у результат
    //     error_log(
    //         date('Y-m-d H:i:s P') . " [" . $requestID . "]" . json_encode($fault) . "\n\n",
    //         3,
    //         "/home/.../sendDms.log"
    //     );
    //     // $bot->sendMessage(
    //     //     $chatId,
    //     //     "\n[" . json_encode($fault) . "]\n"
    //     // );
    // }

    return $res;
}