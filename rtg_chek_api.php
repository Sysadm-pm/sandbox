<?php
 require_once '/home/.../api-rtgk.class.php';
 require_once '/home/.../trembita.class.php';
 require_once '/home/.../HSM.class.php';

 const RTGK_URL="https://";
 const RTGK_BASIC="";
 const RTGK_XSIGNATURE="";

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


 
 $requestID = generate_uuid();
 $sourceRequestID = trim($dms->requestid);

 $trembita = new \Trembita("prod","InfMsgResult");
 var_dump($trembita->send((string) $result->requestID, $result->sourceRequestID, $result->requestData, $result->requestDataSign));
 
 $camel = new RtgkApi(
     RTGK_BASIC,
     RTGK_XSIGNATURE,
     RTGK_URL,
    );
    //$data = json_decode($camel->getStatement("123"), false);
    //$data = $camel->registerToCitizen(123);
    $data = json_decode($camel->getRegisterRecord(7131859, "id"))[0];
    //$data = $camel->getFullTxtInfo(123, "id");
$json = json_encode(
    $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ;
print_r( $data->districtId->shortName );echo "\n";
exit();
$variable = ["8ad59882-a63c-4d70-b3ce-bfedc9da121b","51305357-12d8-46a8-81e1-225db484f2dc"];
foreach ($variable as $value) {
    //$dataFor = json_decode($camel->getRegisterRecord($value), false);
    $dataFor = $camel->getFullTxtInfo($value);
    $jsonFor = json_encode(
        $dataFor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ;
    print_r( $dataFor );
    echo "\n";
}
