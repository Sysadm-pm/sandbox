<?php
require_once('/home/.../api-rtgk.class.php');
require_once('/home/.../db.class.php');
class pracessedDms //v2
{
    protected $arrayDataSource;
    protected $arrayDataResult;
    protected $arrayTrembita;
    protected $jsonData;
    protected $db;
    protected $rtgkReciver;
    protected $citizen_id;
    protected $statement_id;
    protected $register_record_id;
    
    
    public function __construct($jsonData = null, $arrayTrembita = null, $db = null)
    {
        if($db){
            $this->db = $db;
        }else{

            try {
                $this->db = new \DBext("-",  "-", "-", "-");
            } catch (Exception $e) {
                error_log(
                    date('Y-m-d H:i:s') . " Error connection to PB (220v.10A)" . $e->getMessage() . "\n",
                    3,
                    "/home/.../processed.log"
                );
                throw new Exception($e);
            }
        }

        if (isset($jsonData)) {
            $this->jsonData = mb_convert_encoding($jsonData, 'UTF-8');
            try {
                $this->arrayDataSource = json_decode($jsonData, true);
            } catch (Exception $e) {
                error_log(
                    date('Y-m-d H:i:s') . " JSON parse Error=" . $e->getMessage() . "\n",
                    3,
                    "/home/.../processed.log"
                );
                throw new Exception($e);
            }
            $this->arrayDataSource = array_map('trim',  $this->arrayDataSource);
            $this->arrayDataSource = array_map(function($value) { return str_replace("'", "’", $value); }, $this->arrayDataSource);
        }
        if (isset($arrayTrembita)) {
            $this->arrayTrembita = $arrayTrembita;
        }

        if (!is_object($this->db)) {
            error_log(date('Y-m-d H:i:s') . " DB Error = No connection to database.\n\n", 3, "/home/.../processed.log");
        }

        try {
            $this->rtgkReciver = new RtgkApi(
            "-",
            "-",
            //"https://"
            "http://"
        );
        } catch (Exception $e) {
            error_log(
                date('Y-m-d H:i:s') . "Creation resoce object rtgkReciver Error=" . $e->getMessage() . "\n",
                3,
                "/home/.../processed.log"
            );
            throw new Exception($e);
        }


    }


    public function getCitizen($personArray)
    {
        $citizen = json_decode($this->rtgkReciver->getPerson(
            $personArray["foaf_familyName"]??"",
            $personArray["foaf_givenName"]??"",
            $personArray["person_patronymicName"]??"",
            $personArray["schema_birthDate"]??""
        ), true);
        if (empty($citizen)) {
            $citizen = json_decode($this->rtgkReciver->getPerson(
                str_replace("’", "'", $personArray["foaf_familyName"]??""),
                str_replace("’", "'", $personArray["foaf_givenName"]??""),
                str_replace("’", "'", $personArray["person_patronymicName"]??""),
                $personArray["schema_birthDate"]??""
            ), true);
        }
        


        return $citizen ?? null;
    }

    public function getNormaliseData($rawData)
    {
        
    }
    
    public function getAdress($adrArray)
    {
        if(!$adrArray) {return null;}
        //var_dump($adrArray);die;      
        $adrArray["cbc_Country"] = $adrArray["cbc_Country"]??"";  
        $adrArray["cbc_District"] =$adrArray["cbc_District"]??"";  
        $adrArray["cbc_Region"] = $adrArray["cbc_Region"]??"";
        $adrArray["cbc_CityName"] = $adrArray["cbc_CityName"]??""; 
        $adrArray["cbc_CityName"] = str_replace(['м.', 'місто', 'Місто'], "", $adrArray["cbc_CityName"]);
        $adrArray["CityType"] = $adrArray["CityType"]??"";
        $adrArray["CityType"] = mb_strtolower($adrArray["CityType"]); 
        $adrArray["City_District"] = $adrArray["City_District"]??"";  
        $adrArray["cbc_StreetName"] = $adrArray["cbc_StreetName"]??""; 
        $adrArray["cbc_BuildingNumber"] = $adrArray["cbc_BuildingNumber"]??""; 
        $adrArray["Apartment"] =     $adrArray["Apartment"]??"";   
        $adrArray["BuildingPart"] = $adrArray["BuildingPart"]??""; 
        
        if($this->strСontains($adrArray["City_District"],['р-н', 'район', 'р-н.', 'рн.'])){
            $adrArray["City_District"] = str_replace(['р-н', 'район', 'р-н.', 'рн.'], "", $adrArray["City_District"]);
        }
        $adrArray["City_District"] = trim($adrArray["City_District"]);

        $streetTypeArray = [
            "вулиця",
            "вул.",
            "провулок",
            "пров.",
            "лінія",
            "лін.",
            "площа",
            "пл.",
            "майдан",
            "проспект",
            "просп.",
            "пр.",
            "бульвар",
            "бульв.",
            "бул.",
            "узвіз",
            "узв.",
            "уз.",
            "проїзд",
            "шосе",
            "дорога",
            "дор.",
            "алея",
            "набережна",
            "наб.",
            "тупик",
            
            "Військова частина",
            "в/ч", 
            "військове містечко",
            "в/м", 
            "лісництво" ,
            "л-во", 
            "міст", 
            "направлення",
            "направ.", 
            "роз'їзд", 
            "сквер", 
            "станція",
            "ст.", 
            "урочище", 
            "хутір"
        ];
        $streetTypeArrayFull = [
            "вулиця",
            "вулиця",
            "провулок",
            "провулок",
            "лінія",
            "лінія",
            "площа",
            "площа",
            "майдан",
            "проспект",
            "проспект",
            "проспект",
            "бульвар",
            "бульвар",
            "бульвар",
            "узвіз",
            "узвіз",
            "узвіз",
            "проїзд",
            "шосе",
            "дорога",
            "дорога",
            "алея",
            "набережна",
            "набережна",
            "тупик",
            
            "Військова частина",
            "Військова частина", 
            "військове містечко",
            "військове містечко", 
            "лісництво" ,
            "лісництво", 
            "міст", 
            "направлення",
            "направ.", 
            "роз'їзд", 
            "сквер", 
            "станція",
            "станція", 
            "урочище", 
            "хутір"
        ];
        // var_dump($adrArray["cbc_StreetName"]);
        if($keyOfType = $this->strСontains($adrArray["cbc_StreetName"]??"",$streetTypeArray) || $this->strСontains($adrArray["cbc_StreetName"]??"",$streetTypeArray) === 0 ){
            $street = str_replace($streetTypeArray, "", $adrArray["cbc_StreetName"]);
            $street = trim($street);
            $streetType = $streetTypeArrayFull[$keyOfType];
        }else{
            $street = $adrArray["cbc_StreetName"]??"";
            $street = trim($street);
            $streetType = $street ? "вулиця" : "";
        }
        $street = str_replace($streetTypeArray, "", $adrArray["cbc_StreetName"]);
        $street = trim($street);
        // var_dump($keyOfType);
        // var_dump($street);
        // die;
        if($this->strСontains($adrArray["cbc_District"],['р-н', 'район', 'р-н.', 'рн.']) || $this->strСontains($adrArray["cbc_District"],['р-н', 'район', 'р-н.', 'рн.']) === 0){
            $district = str_replace(['р-н', 'район', 'р-н.', 'рн.'], "", $adrArray["cbc_District"]);
        }else{
            $district = $adrArray["cbc_District"];
        }
        $district = trim($district);
        $districtType = "р-н.";
        

        if($this->strСontains($adrArray["cbc_Region"],['обл.', 'область'])|| $this->strСontains($adrArray["cbc_Region"],['обл.', 'область']) === 0 ){
            $region = str_replace(['обл.', 'область'], "", $adrArray["cbc_Region"]);
        }else{
            $region = $adrArray["cbc_Region"];
        }
        $region = trim($region);
        $regionType = "обл.";

        $adressResult = json_decode($this->rtgkReciver->getAdsess(
            $adrArray["cbc_Country"],       //Country
            $adrArray["cbc_District"],      //District
            $region,                        //Region
            $adrArray["cbc_CityName"],      //City
            $adrArray["CityType"],          //City type
            $adrArray["City_District"],     //City district
            $street,                        //Street
            $streetType,                    //Street type
            $adrArray["cbc_BuildingNumber"],//Building number
            $adrArray["Apartment"]          //Apartment
        ), true);

        foreach ($adressResult as $key => $value) {
            $adressResult[$key] = $adressResult[$key] ? "'".$adressResult[$key]."'" : "null";
        }

        $adressOriginal = ($adrArray["cbc_Country"] ? $adrArray["cbc_Country"] :"")
        . ($region || $district || $adrArray["cbc_CityName"] || $adrArray["City_District"] ||$street ? ", ":"")
        . ($region      ? str_replace("'", "’", $region)    ." ". $regionType   .", " : "")
        . ($district    ? str_replace("'", "’", $district)  ." ". $districtType     .", " : "")
        . ($adrArray["cbc_CityName"]?$adrArray["CityType"] . " " . str_replace("'", "’", $adrArray["cbc_CityName"]) . ", " :"")
        . ($adrArray["City_District"]?str_replace("'", "’", $adrArray["City_District"]) . ", ":"")
        . ($street ? $streetType . " ":"")
        . ($street ? str_replace("'", "’", $street) . ", " : "")
        . ($adrArray["cbc_BuildingNumber"] ? "буд. " . $adrArray["cbc_BuildingNumber"]:"")
        . ($adrArray["BuildingPart"]    ? ", корпус "   . $adrArray["BuildingPart"] . ", " : "")
        . ($adrArray["Apartment"]       ? ", кв."       . $adrArray["Apartment"] : "");
        $adressOriginal = trim($adressOriginal);
     
        unset($adressResult["areaId"]);
        unset($adressResult["regionId"]);
        return ["adressUuids"=>$adressResult,"adressOriginal"=>$adressOriginal?:"null"];
        
    }
    
    public function getRelationsheep()
    {
    }

    public function getCombineData($data = null, $requestid_dms = null, $inType=null)
    {
        if($inType){
            $this->arrayTrembita["requestDataType"] = $inType;
        }
        $requestid_dms = $requestid_dms ?? '';
        if (isset($data)) {
            $this->jsonData = mb_convert_encoding($data, 'UTF-8');
            try {
                $this->arrayDataSource = json_decode($data, true);
            } catch (Exception $e) {
                error_log(
                    date('Y-m-d H:i:s') . " JSON parse Error=" . $e->getMessage() . "\n",
                    3,
                    "/home/.../processed.log"
                );
                throw new Exception($e);
            }

            if (!function_exists('trimArray')) {
                function trimArray($string){
                    if( is_array($string) ){
                        return array_map('trimArray',$string);
                    }else{
                        return trim($string);
                    }
                }
               
            }

            $this->arrayDataSource = array_map('trimArray',$this->arrayDataSource);
            //var_dump($this->arrayDataSource);die;
            $this->arrayDataSource = array_map(function($value) { return str_replace("'", "’", $value); }, $this->arrayDataSource);
        }
        
                $filtered = array_filter($this->arrayDataSource, function($v) {
                    return preg_match('#TypeService\d#', $v) && (int)$this->arrayDataSource[$v] == 1 ;
                  }, ARRAY_FILTER_USE_KEY);
        
                if(count($filtered)>1){
        
                    error_log(
                        date('Y-m-d H:i:s') . " TypeService Error= Too many TypeServices in request:\n" . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
                        3,
                        "/home/.../processed.log"
                    );
        
                    throw new Exception(date('Y-m-d H:i:s') . " TypeService Error=\n" . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        
                }elseif(count($filtered)<1){
        
                    error_log(
                        date('Y-m-d H:i:s') . " TypeService Error=\n" . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
                        3,
                        "/home/.../processed.log"
                    );
        
                    throw new Exception(date('Y-m-d H:i:s') . " TypeService Error= No TypeService in request!\n");
        
                }else{
        
                    $type = $this->arrayTrembita["requestDataType"]??"ER" . "-" . preg_replace('/\D/', '', array_keys($filtered)[0]);
        
                }
        
                $this->arrayDataResult['id']                        = $this->arrayDataSource["requestID"]??""; 
                $this->arrayDataResult['type']                      = $type??null;
                $this->arrayDataResult['data']                      = $this->jsonData??null;
                $this->arrayDataResult["normalized_data"]           = "null"; 
        
        
                $this->arrayDataResult["x_road_instance"]           = $this->arrayTrembita["xRoadInstance"] ?? "null";
                $this->arrayDataResult["member_class"]              = $this->arrayTrembita["memberClass"]   ?? "null";
                $this->arrayDataResult["member_code"]               = $this->arrayTrembita["memberCode"]    ?? "null";
                $this->arrayDataResult["subsystem_code"]            = $this->arrayTrembita["subsystemCode"] ?? "null";
                $this->arrayDataResult["requestID"]                 = $this->arrayTrembita["requestID"]     ?? "null";
                
                $this->arrayDataResult["requestID"] ?? $requestid_dms ?? "";
        
                $this->arrayDataResult['citizen_id'] = $this->getCitizen($this->arrayDataSource["applicantInfo"]??"")?:'null';
                if ($this->arrayDataResult['citizen_id'] !=="null") {
        
                    $this->arrayDataResult["citizen_id"]                =  "'" . $this->arrayDataResult["citizen_id"] . "'";
        
                    $register = json_decode($this->rtgkReciver->getRegisterRecord($this->arrayDataResult["citizen_id"]), true);
                    $register = $register?:null;
                    if (!empty($register)) {
                        $this->arrayDataResult["register_record_id"]        = is_numeric($register[0]["id"]) ? "'" . $register[0]["id"] . "'" : 'null';
                        $this->arrayDataResult["statement_id"]              = is_numeric($register[0]["statementId"]) ? "'" . $register[0]["statementId"] . "'" : 'null';
                    } else {
                        $this->arrayDataResult["register_record_id"]        = 'null';
                        $this->arrayDataResult["statement_id"]              = 'null';
                    }
                }else{
                    $this->arrayDataResult["register_record_id"]        = 'null';
                    $this->arrayDataResult["statement_id"]              = 'null';
                }
        
                $this->arrayDataResult['is_mother_address']         = $this->arrayDataResult['is_mother_address']??"true"; 
                
                $this->arrayDataResult['mother_id'] = $this->getCitizen($this->arrayDataSource["motherInfo"]??null)?:'null';
                $motherAdress = $this->getAdress($this->arrayDataSource["motherInfo"]??"");
                $this->arrayDataResult["mother_original_address"]   = $motherAdress["adressOriginal"]??"";
                $motherArrayToCombine =   [
                    "mother_residence_id" =>"null",
                    "mother_building_id" =>"null",
                    "mother_street_id" =>"null",
                    "mother_district_id" =>"null",
                    "mother_locality_id" =>"null",
                    "mother_country_id" =>"null",
                ];
                if($motherAdress["adressUuids"]["countryId"]??null){
                    $this->arrayDataResult = array_merge($this->arrayDataResult,array_combine(array_keys($motherArrayToCombine),$motherAdress["adressUuids"]));
                }else{
                    $this->arrayDataResult = array_merge($this->arrayDataResult,$motherArrayToCombine);
                    //var_dump($this->arrayDataResult);die;
                }
                $this->arrayDataResult['father_id'] = $this->getCitizen($this->arrayDataSource["fatherInfo"]??null)?:'null';
                $fatherAdress = $this->getAdress($this->arrayDataSource["fatherInfo"]??"");
                $this->arrayDataResult["father_original_address"]   = $fatherAdress["adressOriginal"]??"";
                $fatherArrayToCombine = [
                    'father_residence_id' => "null",
                    'father_building_id' => "null", 
                    'father_street_id' => "null",   
                    'father_district_id' => "null", 
                    'father_locality_id' => "null", 
                    'father_country_id' => "null",  
                ];

                if($fatherAdress["adressUuids"]["countryId"]??null){
                    $this->arrayDataResult = array_merge($this->arrayDataResult,array_combine(array_keys($fatherArrayToCombine),$fatherAdress["adressUuids"]));
                }else{
                    $this->arrayDataResult = array_merge($this->arrayDataResult,$fatherArrayToCombine);
                }
                $outAdress = $this->getAdress($this->arrayDataSource["RegistrationAddress"]??"");
                $this->arrayDataResult["out_original_address"]   = $outAdress["adressOriginal"]??"";
                $outArrayToCombine =  [
                    "out_country_id" =>"null",
                    "out_locality_id" =>"null", 
                    "out_district_id" =>"null", 
                    "out_street_id" =>"null",  
                    "out_building_id" =>"null", 
                    "out_residence_id" =>"null",
                ];
                if($outAdress["adressUuids"]["countryId"]??null){
                    $this->arrayDataResult = array_merge($this->arrayDataResult,array_combine(array_keys($outArrayToCombine),$outAdress["adressUuids"]));
                }else{
                    $this->arrayDataResult = array_merge($this->arrayDataResult,$outArrayToCombine);
                }
                $Adress = $this->getAdress($this->arrayDataSource["NewRegistrationAddress"]);
                $this->arrayDataResult["original_address"]   = $Adress["adressOriginal"];
                $this->arrayDataResult = array_merge($this->arrayDataResult,array_combine(array_keys(
                    [
                        'country_id' => "null",  
                        'locality_id' => "null", 
                        'district_id' => "null", 
                        'street_id' => "null",   
                        'building_id' => "null", 
                        'residence_id' => "null",
                        
                    ]),$Adress["adressUuids"]));
                if($this->arrayDataResult['type'] == "E14-4" || $this->arrayDataResult['type'] == "E14-10"){
                    $this->arrayDataResult['last_name']                 = $this->arrayDataSource["childInfo"]["foaf_familyName"]??"";
                    $this->arrayDataResult['first_name']                = $this->arrayDataSource["childInfo"]["foaf_givenName"]??"";
                    $this->arrayDataResult['middle_name']               = $this->arrayDataSource["childInfo"]["person_patronymicName"]??"";
                    $this->arrayDataResult['date_of_birth']             = $this->arrayDataSource["childInfo"]["schema_birthDate"]??""; 
                
                }else{
                    $this->arrayDataResult['last_name']                 = $this->arrayDataSource["applicantInfo"]["foaf_familyName"]??"";
                    $this->arrayDataResult['first_name']                = $this->arrayDataSource["applicantInfo"]["foaf_givenName"]??"";
                    $this->arrayDataResult['middle_name']               = $this->arrayDataSource["applicantInfo"]["person_patronymicName"]??"";
                    $this->arrayDataResult['date_of_birth']             = $this->arrayDataSource["applicantInfo"]["schema_birthDate"]??""; 
                }  

                //var_dump($this->arrayDataResult);die;
                return $this->arrayDataResult;
    }


    public function dbInput()
    {


        //$this->arrayDataResult['data']  =  preg_replace('/\\\"/',"\"", $this->arrayDataResult['data']) ;

        // try {
        //     $db = new DB("-",  "-", "-", "-");
        // } catch (Exception $e) {
        //     error_log(
        //         date('Y-m-d H:i:s') . " Error connection to PB (220v.10A)" . $e->getMessage() . "\n",
        //         3,
        //         "/home/.../processed.log"
        //     );
        //     throw new Exception($e);
        // }
        // if (!is_object($db)) {
        //     error_log(date('Y-m-d H:i:s') . " DB Error = No connection to database.\n\n", 3, "/home/.../processed.log");
        // }

        $sql  =   "INSERT INTO public.dms_receiver
                    (
                        id
                    , \"type\"
                    , \"data\"
                    , x_road_instance
                    , member_class
                    , member_code
                    , subsystem_code
                    , requestid_dms
                    , citizen_id
                    , statement_id
                    , register_record_id
                    , is_mother_address
                    , mother_id
                    , mother_original_address
                    , mother_residence_id
                    , mother_building_id
                    , mother_street_id
                    , mother_district_id
                    , mother_locality_id
                    , mother_country_id
                    , father_id
                    , father_original_address
                    , father_residence_id
                    , father_building_id
                    , father_street_id
                    , father_district_id
                    , father_locality_id
                    , father_country_id
                    , out_statement_id
                    , out_register_record_id
                    , out_original_address
                    , out_residence_id
                    , out_building_id
                    , out_street_id
                    , out_district_id
                    , out_locality_id
                    , out_country_id
                    , original_address
                    , residence_id
                    , building_id
                    , street_id
                    , district_id
                    , locality_id
                    , country_id
                    , last_name
                    , first_name
                    , middle_name
                    , date_of_birth
                    )
                    VALUES(
                       '{$this->arrayDataResult['id']}'
                        ,'{$this->arrayDataResult['type']}'
                        ,'{$this->arrayDataResult['data']}'
                        ,{$this->arrayDataResult['x_road_instance']}
                        ,{$this->arrayDataResult['member_class']}
                        ,{$this->arrayDataResult['member_code']}
                        ,{$this->arrayDataResult['subsystem_code']}
                        ,{$this->arrayDataResult["requestID"]}
                        ,{$this->arrayDataResult['citizen_id']} 
                        ,{$this->arrayDataResult['statement_id']}
                        ,{$this->arrayDataResult['register_record_id']}
                        ,{$this->arrayDataResult['is_mother_address']} 
                        ,{$this->arrayDataResult['mother_id']} 
                        ,'" . pg_escape_string($this->arrayDataResult['mother_original_address'])."'
                        ,{$this->arrayDataResult['mother_residence_id']}
                        ,{$this->arrayDataResult['mother_building_id']}
                        ,{$this->arrayDataResult['mother_street_id']} 
                        ,{$this->arrayDataResult['mother_district_id']} 
                        ,{$this->arrayDataResult['mother_locality_id']} 
                        ,{$this->arrayDataResult['mother_country_id']} 
                        ,{$this->arrayDataResult['father_id']} 
                        ,'" . pg_escape_string($this->arrayDataResult['father_original_address'])."'
                        ,{$this->arrayDataResult['father_residence_id']}
                        ,{$this->arrayDataResult['father_building_id']} 
                        ,{$this->arrayDataResult['father_street_id']} 
                        ,{$this->arrayDataResult['father_district_id']} 
                        ,{$this->arrayDataResult['father_locality_id']} 
                        ,{$this->arrayDataResult['father_country_id']} 
                        ,{$this->arrayDataResult['out_statement_id']} 
                        ,{$this->arrayDataResult['out_register_record_id']} 
                        ,'" . pg_escape_string($this->arrayDataResult['out_original_address'])."'
                        ,{$this->arrayDataResult['out_residence_id']} 
                        ,{$this->arrayDataResult['out_building_id']} 
                        ,{$this->arrayDataResult['out_street_id']} 
                        ,{$this->arrayDataResult['out_district_id']} 
                        ,{$this->arrayDataResult['out_locality_id']} 
                        ,{$this->arrayDataResult['out_country_id']} 
                        ,'" . pg_escape_string($this->arrayDataResult['original_address'])."' 
                        ,{$this->arrayDataResult['residence_id']} 
                        ,{$this->arrayDataResult['building_id']} 
                        ,{$this->arrayDataResult['street_id']}
                        ,{$this->arrayDataResult['district_id']} 
                        ,{$this->arrayDataResult['locality_id']}
                        ,{$this->arrayDataResult['country_id']}
                        ,'" . pg_escape_string($this->arrayDataResult['last_name'])."' 
                        ,'" . pg_escape_string($this->arrayDataResult['first_name'])."' 
                        ,'" . pg_escape_string($this->arrayDataResult['middle_name'])."'
                        ,to_date('{$this->arrayDataResult['date_of_birth']}'::TEXT, 'DD-MM-YYYY') 
                        )
                    ";
        // var_dump("\n\n"
        //     . $sql .
        //     "\n\n");
        // die;
        try {
            $insert_res = $this->db->query($sql);
            //$insert_res=true;
        } catch (Exception $e) {
            //error_log(date('Y-m-d H:m:s P')  . "[" . $this->arrayDataResult['id'] . "] DB insert Error=" . $e . "\n\n", 3, "/home/.../processed.log");
            throw new Exception("\n[Error writting data in DB] :  \n" . $e . "\n" . $sql . "\n");
            // print_r($e->getMessage());
            // print_r("\n".$sql."\n");
        }

        if (!isset($insert_res)) {
            error_log(date('Y-m-d H:m:s P')  . "[" . $this->arrayDataResult['id'] . "] \nRequest Data= -none- \n\n", 3, "/home/.../processed.log");
            //throw new SoapFault("503", "Cant insert data in database. " . date('Y-m-d H:m:s P'));
        } else {

            //$row = pg_fetch_row($result);
            //$user_id = $row[0];
            //return $row[0];
            return true;
        }
    }

    public function dbUpdate($id = null)
    {

        $citizen_id = $this->citizen_id ? "'" . $this->citizen_id . "'" : $this->arrayDataResult['citizen_id'];
        $statement_id = $this->statement_id ?  "'" . $this->statement_id . "'" : $this->arrayDataResult['statement_id'];
        $register_record_id =  $this->register_record_id ?  "'" . $this->register_record_id . "'" : $this->arrayDataResult['register_record_id'];
        $sql  =   "UPDATE public.dms_receiver
                        SET 
                        --id='{$this->arrayDataResult['id']}'
                        --, \"type\"='{$this->arrayDataResult['type']}'
                        --, \"data\"='{$this->arrayDataResult['data']}'
                         citizen_id={$citizen_id}
                        , statement_id={$statement_id}
                        , register_record_id={$register_record_id}
                        --, is_mother_address={$this->arrayDataResult['is_mother_address']} 
                        , mother_id={$this->arrayDataResult['mother_id']} 
                        , mother_original_address='{$this->arrayDataResult['mother_original_address']}' 
                        , mother_residence_id={$this->arrayDataResult['mother_residence_id']}
                        , mother_building_id={$this->arrayDataResult['mother_building_id']}
                        , mother_street_id={$this->arrayDataResult['mother_street_id']} 
                        , mother_district_id={$this->arrayDataResult['mother_district_id']} 
                        , mother_locality_id={$this->arrayDataResult['mother_locality_id']}
                        , mother_country_id={$this->arrayDataResult['mother_country_id']} 
                        , father_id={$this->arrayDataResult['father_id']} 
                        , father_original_address='{$this->arrayDataResult['father_original_address']}'
                        , father_residence_id={$this->arrayDataResult['father_residence_id']}
                        , father_building_id={$this->arrayDataResult['father_building_id']} 
                        , father_street_id={$this->arrayDataResult['father_street_id']} 
                        , father_district_id={$this->arrayDataResult['father_district_id']} 
                        , father_locality_id={$this->arrayDataResult['father_locality_id']} 
                        , father_country_id={$this->arrayDataResult['father_country_id']} 
                        , out_original_address='" . pg_escape_string($this->arrayDataResult['out_original_address']) . "'
                        , out_residence_id={$this->arrayDataResult['out_residence_id']} 
                        , out_building_id={$this->arrayDataResult['out_building_id']}
                        , out_street_id={$this->arrayDataResult['out_street_id']}
                        , out_district_id={$this->arrayDataResult['out_district_id']}
                        , out_locality_id={$this->arrayDataResult['out_locality_id']}
                        , out_country_id={$this->arrayDataResult['out_country_id']} 
                        , original_address='".pg_escape_string($this->arrayDataResult['original_address'])."' 
                        , residence_id={$this->arrayDataResult['residence_id']}
                        , building_id={$this->arrayDataResult['building_id']}
                        , street_id={$this->arrayDataResult['street_id']}
                        , district_id={$this->arrayDataResult['district_id']}
                        , locality_id={$this->arrayDataResult['locality_id']}
                        , country_id={$this->arrayDataResult['country_id']}
                        , last_name ='".pg_escape_string($this->arrayDataResult['last_name'])."'
                        , first_name ='".pg_escape_string($this->arrayDataResult['first_name'])."'
                        , middle_name ='".pg_escape_string($this->arrayDataResult['middle_name'])."'
                        , date_of_birth = to_date('{$this->arrayDataResult['date_of_birth']}'::TEXT, 'DD-MM-YYYY')
                        --, updated=now()
                        --, \"version\"=+1
                        WHERE s_id={$id};                 
                    ";
        //print_r($sql); //die;

        try {
            $insert_res = $this->db->query($sql);
            //$insert_res=true;
        } catch (Exception $e) {
            error_log(date('Y-m-d H:m:s P')  . "[" . $this->arrayDataResult['id'] . "] DB insert Error=" . $e . "\n" . $sql . "\n\n", 3, "/home/.../processed.log");
            throw new Exception("\n[Error writting data in DB] :  \n" . $e . "\n" . $sql . "\n");
        }

        if (!isset($insert_res)) {
            error_log(date('Y-m-d H:m:s P')  . "[" . $this->arrayDataResult['id'] . "] \nRequest Data= -none- \n\n", 3, "/home/.../processed.log");
            //throw new SoapFault("503", "Cant insert data in database. " . date('Y-m-d H:m:s P'));
        } else {

            //$row = pg_fetch_row($result);
            //$user_id = $row[0];
            //return $row[0];
            return true;
        }
    }
    public function  updateById($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM public.dms_receiver where s_id={$id} ORDER BY s_id desc LIMIT 1";
            $camel = $this->db->query($sql);
            if ($camel) {
                $this->citizen_id = isset($camel[0]['citizen_id']) ? $camel[0]['citizen_id'] : null;
                $this->statement_id = isset($camel[0]['statement_id']) ? $camel[0]['statement_id'] : null;
                $this->register_record_id = isset($camel[0]['register_record_id']) ? $camel[0]['register_record_id'] : null;
                $this->arrayDataResult = $this->getCombineData($camel[0]["data"], null , $camel[0]["type"]);
                //print_r($this->arrayDataResult);
                $result = $this->dbUpdate($id);
                // print_r($this->arrayDataResult);
                //print_r("\n\n" . $this->arrayDataResult . "\n\n");
                //die;
                if ($result) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public function strСontains($haystack = "", $needle = "")
    {
        if(is_array($needle)){
            foreach ($needle as $key => $value) {
                $value = mb_strtolower($value);
                if('' === $value || false !== strpos($haystack, $value)){
                    return $key;
                }
                //return '' === $value || false !== strpos($haystack, $value);
            }
            return null;
        }else{
            $haystack = mb_strtolower($haystack);
            return '' === $needle || false !== strpos($haystack, $needle);
        }
    }

}
