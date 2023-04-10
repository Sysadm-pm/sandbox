<?php
//  require_once '/home/.../api-rtgk.class.php';
// require_once '/home/.../!test_scripts/trembita.class.php';
 require_once '/home/.../!test_scripts/send_fix.php';
 require_once '/home/.../HSM.class.php';

 const RTGK_URL="https://";
 const RTGK_BASIC="";
 const RTGK_XSIGNATURE="";

 const HSM_BARER="";
 const HSM_STORE="cihsm://";
 const HSM_STORE_SECRET="";


 function generate_uuid2()
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

 function isJson($string) {
    json_decode($string);
    if(json_last_error() !== JSON_ERROR_NONE){
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return json_last_error() === JSON_ERROR_NONE;
                return ' - Ошибок нет';
            break;
            case JSON_ERROR_DEPTH:
                return ' - Достигнута максимальная глубина стека';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                return ' - Некорректные разряды или несоответствие режимов';
            break;
            case JSON_ERROR_CTRL_CHAR:
                return ' - Некорректный управляющий символ';
            break;
            case JSON_ERROR_SYNTAX:
                return ' - Синтаксическая ошибка, некорректный JSON';
            break;
            case JSON_ERROR_UTF8:
                return ' - Некорректные символы UTF-8, возможно неверно закодирован';
            break;
            default:
                return ' - Неизвестная ошибка';
            break;
        }
    }
    return json_last_error() === JSON_ERROR_NONE;
}

 
 $requestID = generate_uuid2();
 $json = file_get_contents('/home/.../!test_scripts/source.json');
 if(!$json){
    print_r("wrong JSON");
    die;
 }
 $requestData = base64_encode($json);
 
 $hsm = new \HSM( $requestData, HSM_BARER, HSM_STORE, HSM_STORE_SECRET );
 $requestDataSign = $hsm->DSHash;

 $record = json_decode($json);
 $sourceRequestID = isset($record->SourceID)    ?   $record->SourceID   :   null;

 if(!$record->SourceID){
     print_r("wrong SourceID");
     die;
    }
 print_r([$requestID, $sourceRequestID, $requestData, $requestDataSign]);
echo "\n\n";
 // die;


//  $rtgk = new RtgkApi(
//      RTGK_BASIC,
//      RTGK_XSIGNATURE,
//      RTGK_URL,
//     );
    
$result = callPersonInfoService($requestID, $sourceRequestID, $requestData, $requestDataSign);
//  $trembita = new \Trembita("prod","InformPlaceResidence");
//  $result = $trembita->send($requestID, $sourceRequestID, $requestData, $requestDataSign);

echo "\n\n";
 var_dump( [
    $result
 ]
);
 
// print_r(
//     [
//         $json, $requestID, $sourceRequestID, $requestData, $requestDataSign
//     ]
// );
