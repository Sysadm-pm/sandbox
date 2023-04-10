<?php
require_once '/home/.../send_CertRequest.php';
require_once '/home/.../HSM.class.php';

const HSM_BARER = "...";
const HSM_STORE = "cihsm://";
const HSM_STORE_SECRET = "";

$requestID = generate_uuid();

$data = "{
            PersFam: '',
            PersIm1: '',
            PersIm2: '',
            ReqReason: '01',
            ReqReasonDate: '',
            ReqAuthor: ' Д',
            ReqEmployee: ' Г. С.',
            ReqType: 'actual',
            PersBornDate: '',
            DocType: 'ПГУ',
            DocSeria: '',
            DocNomer: ''
          }";

$baseData = base64_encode($data);

error_log(
    date('Y-m-d H:i:s P') . "<CertRequest> [" . $requestID . "]\n" . " Data =\n" . $data . "\n",
    3,
    "/home/.../dms_request_check.log"
);
error_log(
    date('Y-m-d H:i:s P') . "<CertRequest> [" . $requestID . "]\n" . " Base64 Data =\n" . $baseData . "\n",
    3,
    "/home/.../dms_request_check.log"
);

$hsm = new \HSM($baseData, HSM_BARER, HSM_STORE, HSM_STORE_SECRET);

if (empty($hsm)) {
    print_r("DS not set.");
    exit();
}

$dataSign = $hsm->DSHash;

error_log(
    date('Y-m-d H:i:s P') . "<CertRequest> [" . $requestID . "]\n" . " dataSign =\n" . $dataSign . "\n",
    3,
    "/home/.../dms_request_check.log"
);

$resp = callPersonInfoService($requestID, $baseData, $dataSign);
print_r($resp);
error_log(
    date('Y-m-d H:i:s P') . "<CertRequest> [" . $requestID . "]\n" . " Responce result =\n" . json_encode($resp) . "\n\n",
    3,
    "/home/.../dms_request_check.log"
);

