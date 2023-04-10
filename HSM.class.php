<?php

class HSM
{
    private $data;
    private $uuid;
    private $bearer;
    private $url;
    private $keyStore;
    private $keyPassword;
    public $DSHash;
    public $count;

    public function __construct($data, $bearer, $keyStore, $keyPassword, $debug = 0)
    {
        if (!empty($debug)) {
            echo "h1...\n";
        }
        $this->data         = $data;
        $this->bearer       = $bearer;
        $this->url          = "http://-/ccs/api/v1/";
        if (!empty($debug)) {
            echo "h2...\n";
        }
        $uuid               = json_decode(Self::curlUse("ticket", "POST"), true);
        //var_dump(Self::curlUse("ticket", "POST"));
        if (!empty($debug)) {
            echo "h3...\n";
        }
        $this->uuid         = $uuid["ticketUuid"];
        $this->keyStore     = $keyStore;
        $this->keyPassword  = $keyPassword;
        Self::sendData();
        if (!empty($debug)) {
            echo "h4...\n";
        }
        Self::putOption();
        if (!empty($debug)) {
            echo "h5...\n";
        }
        Self::putKeyStore();
        if (!empty($debug)) {
            echo "h6...\n";
        }
        Self::createDS();
        $this->count        = 0;
        if (!empty($debug)) {
            echo "h7...\n";
        }
        Self::makeDS();
        if (!empty($debug)) {
            echo "h8...\n";
        }
        $this->DSHash       = Self::getDSHash();
        if (!empty($debug)) {
            echo "h9...\n";
        }
        Self::deleteSession();
        if (!empty($debug)) {
            echo "h10...\n";
        }
    }
    public function __destruct()
    {
        Self::deleteSession();
    }

    public function getUuid($data)
    {
        $this->data = $data;
    }

    public function curlUse($url, $method, $data = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        // $streamVerboseHandle = fopen('php://temp', 'w+');
        // curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);
        $authorization = "Authorization: Bearer " . $this->bearer;
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            $authorization
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // if ($result === FALSE) {
        //     printf("cUrl error (#%d): %s<br>\n",
        //            curl_errno($ch),
        //            htmlspecialchars(curl_error($ch)))
        //            ;
        // }
        
        // rewind($streamVerboseHandle);
        // $verboseLog = stream_get_contents($streamVerboseHandle);
        
        // echo "cUrl verbose information:\n", 
        //      "<pre>", htmlspecialchars($verboseLog), "</pre>\n";
        
        if($status_code === 401){
            throw new Exception("Помилка авторизації CAAS - ". $status_code .".\nМожливо невалідний Bearer.\n", true);
        }else{
            //throw new Exception("Помилка сервера - ". $status_code .".\n");
        }
        // if (curl_errno($ch) || $status_code !== 200) {
        //     if($status_code !== 200){
        //     }else{
        //         throw new Exception('Цифровий підпис не отримано.');
        //     //    return curl_error($ch);
        //         echo 'Error:' . curl_error($ch);
        //     }
        // }
        curl_close($ch);
        //var_dump($result."\n");
        //$return = json_decode($result, true);
        //throw new Exception('Цифровий підпис не отримано.');
        return $result;
    }

    private function sendData()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid . "/data",
            "POST",
            '{"base64Data": "' . $this->data . '"}'
        );
        //var_dump($result . "\n");
        return $result;
    }

    private function putOption()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid . "/option",
            "PUT",
            '{"signatureType":"detached","cadesType":"CAdESXLong"}'
        );
        //var_dump($result . "\n");
        return $result;
    }

    private function putKeyStore()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid . "/keyStore",
            "PUT",
            '{"keyStoreUri":"' . $this->keyStore . '"}'
        );
        // var_dump($result . "\n");
        return $result;
    }

    private function createDS()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid . "/ds/creator",
            "POST",
            '{"keyStorePassword":"' . $this->keyPassword . '"}'
        );
        //var_dump($result . "\n");
        return $result;
    }

    private function makeDS()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid . "/ds/creator",
            "GET"
        );
        //var_dump($this->count." _ ".$this->uuid . " _ " . $result);
        if ($result == '{"message":"Операція \"Створення електронного підпису\" знаходиться в стадії виконання."}' and $this->count <= 10) {

            $this->count++;
            sleep(5);
            Self::makeDS();
        } elseif ($this->count >= 10) {
            Self::deleteSession();
            Self::__construct($this->data, $this->bearer, $this->keyStore, $this->keyPassword);
        } else {

            return $result;
        }
    }

    private function getDSHash()
    {

        $result = json_decode(Self::curlUse(
            "ticket/" . $this->uuid . "/ds/base64Data",
            "GET"
        ), true);
        //var_dump($result);
        //echo "\nGet DS result\n";        
        return isset($result["base64Data"]) ? $result["base64Data"] : null;
    }

    private function deleteSession()
    {

        $result = Self::curlUse(
            "ticket/" . $this->uuid,
            "DELETE "
        );
        return $result;
    }
}