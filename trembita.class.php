<?php
ini_set("soap.wsdl_cache_enabled", 0);
ini_set('default_socket_timeout', 5000);
class TrembitaSoapClient extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}
class Trembita {
    public $UXP_SERVER_ADDRESS;
    public $XROAD_INSTANCE;
    public $MEMBER_CLASS;
    public $MEMBER_CODE;
    public $SUBSYSTEM_CODE;
    public $SERVICE_CODE;
    public $USERID_CODE;
    public $KMDA_MEMBER_CODE;
    public $KMDA_SUBSYSTEM_CODE;
    public $wsdlurl;
    public $soapclient;

    public function __construct($env="test",$service){
        $this->MEMBER_CODE = "";
        $this->MEMBER_CLASS = "";
        $this->USERID_CODE = "";
        if($env === "test"){
            $this->UXP_SERVER_ADDRESS = "http://";
            $this->XROAD_INSTANCE = "";
            $this->SUBSYSTEM_CODE = "";
            $this->SERVICE_CODE = $service;
            $this->KMDA_MEMBER_CODE = "";
            $this->KMDA_SUBSYSTEM_CODE = "";
        }elseif($env === "prod") {
            $this->UXP_SERVER_ADDRESS = "http://";
            $this->XROAD_INSTANCE = "";
            $this->SUBSYSTEM_CODE = "";
            $this->SERVICE_CODE = $service;
            $this->KMDA_MEMBER_CODE = "";
            $this->KMDA_SUBSYSTEM_CODE = "";
        }else{
            return "Не вірно вказано параметр \"env\".";
        }
        $this->wsdlurl = $this->UXP_SERVER_ADDRESS . "/wsdl?xRoadInstance=" . $this->XROAD_INSTANCE
        . "&memberClass=" . $this->MEMBER_CLASS . "&memberCode=" . $this->MEMBER_CODE
        . "&subsystemCode=" . $this->SUBSYSTEM_CODE . "&serviceCode=" . $this->SERVICE_CODE;
        if($this->headerBuild()){
            return "Формування заголовків відбулось з помилкою!";
        }
    }

    public static function generate_uuid()
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

    public function headerBuild(){
        $nsxroad = "http://x-road.eu/xsd/xroad.xsd";
        $nsident = "http://x-road.eu/xsd/identifiers";

        $this->soapclient = new TrembitaSoapClient(
            $this->wsdlurl,
            array(
                'location' =>  $this->UXP_SERVER_ADDRESS,
                'uri' =>  $this->UXP_SERVER_ADDRESS,
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

        $xml = '<ns2:service ns3:objectType="SERVICE" xmlns:ns3="' . $nsident . '">'
            . "<ns3:xRoadInstance>" . $this->XROAD_INSTANCE . "</ns3:xRoadInstance>"
            . "<ns3:memberClass>" . $this->MEMBER_CLASS . "</ns3:memberClass>"
            . "<ns3:memberCode>" . $this->MEMBER_CODE . "</ns3:memberCode>"
            . "<ns3:subsystemCode>" . $this->SUBSYSTEM_CODE . "</ns3:subsystemCode>"
            . "<ns3:serviceCode>" . $this->SERVICE_CODE . "</ns3:serviceCode>"
            . "</ns2:service>";

        $servicesoapvar = new SoapVar($xml, XSD_ANYXML);

        $xml = '<ns2:client ns3:objectType="SUBSYSTEM" xmlns:ns3="' . $nsident . '">'
            . "<ns3:xRoadInstance>" . $this->XROAD_INSTANCE . "</ns3:xRoadInstance>"
            . "<ns3:memberClass>" . $this->MEMBER_CLASS . "</ns3:memberClass>"
            . "<ns3:memberCode>" . $this->KMDA_MEMBER_CODE . "</ns3:memberCode>"
            . "<ns3:subsystemCode>" . $this->KMDA_SUBSYSTEM_CODE . "</ns3:subsystemCode>"
            . "</ns2:client>";

        $clientsoapvar = new SoapVar($xml, XSD_ANYXML);

        $clientHeader = new SoapHeader($nsxroad, 'client', $clientsoapvar);
        $serviceHeader = new SoapHeader($nsxroad, 'service', $servicesoapvar);
        $tUuid = self::generate_uuid();
        $idHeader = new SoapHeader($nsxroad, 'id', $tUuid);
        $protocolVersionHeader = new SoapHeader($nsxroad, 'protocolVersion', '4.0');
        $userIdHeader = new SoapHeader($nsxroad, 'userId', $this->USERID_CODE);

        $this->soapclient->__setSoapHeaders(array(
            $clientHeader, $serviceHeader, $idHeader,
            $protocolVersionHeader, $userIdHeader
        ));
        return true;
    }
    public function send($requestID, $sourceRequestID, $requestData, $requestDataSign){
        $param1 = new SoapParam($requestID,       "requestID");
        $param2 = new SoapParam( date('c'),       "requestTime"); 
        $param3 = new SoapParam($sourceRequestID, "sourceRequestID"); 
        $param4 = new SoapParam($requestData,     "requestData"); 
        $param5 = new SoapParam($requestDataSign, "requestDataSign");
        try {
            $result = $this->soapclient->InformPlaceResidence($param1,$param2,$param3,$param4,$param5);
            return $result;
            //$this->SERVICE_CODE{$this->SERVICE_CODE}
        } catch (SoapFault $fault) {
        echo "\n";
        print_r( $this->soapclient->__getLastRequest() );
        echo "\n";
        print_r( $this->soapclient->__getLastResponse() );
            return [
                "status"            => "error",
                $fault->faultcode   => $fault->faultstring,
                "fault"             => $fault         
            ] ;
        }
    }

}



