<?php
require_once('/home/.../api-rtgk.class.php');
require_once('/home/.../db.class.php');
class pracessedDms
{
    protected $arrayDataSource;
    protected $arrayDataResult;
    protected $arrayTrembita;
    protected $jsonData;
    protected $db;
    protected $citizen_id;
    protected $statement_id;
    protected $register_record_id;


    public function __construct($jsonData = null, $arrayTrembita = null)
    {
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
        }
        if (isset($arrayTrembita)) {
            $this->arrayTrembita = $arrayTrembita;
        }

        try {
            $this->db = new \DBext("-", "-", "-", "-");
        } catch (Exception $e) {
            error_log(
                date('Y-m-d H:i:s') . " Error connection to PB (220v.10A)" . $e->getMessage() . "\n",
                3,
                "/home/.../processed.log"
            );
            throw new Exception($e);
        }
        if (!is_object($this->db)) {
            error_log(date('Y-m-d H:i:s') . " DB Error = No connection to database.\n\n", 3, "/home/.../processed.log");
        }
    }

    public function NormalaiseData($data = null, $requestid_dms = null)
    {
        $requestid_dms = isset($requestid_dms) ? $requestid_dms : '';
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
        }

        $camel = new RtgkApi(
            "qwerty",
            "qwerty",
            "http://"
        );
        $searchType = [
            "TypeService4" => isset($this->arrayDataSource["TypeService4"]) ? $this->arrayDataSource["TypeService4"] : 0,
            "TypeService10" => isset($this->arrayDataSource["TypeService10"]) ? $this->arrayDataSource["TypeService10"] : 0,
            "TypeService11" => isset($this->arrayDataSource["TypeService11"]) ? $this->arrayDataSource["TypeService11"] : 0,
        ];
        array_search("1", $searchType);


        $this->arrayDataResult["id"]                        = $this->arrayDataSource["requestID"]; //$this->arrayDataSource["requestID"];
        $this->arrayDataResult["type"]                      = "ER-" . preg_replace('/\D/', '', array_search("1", $searchType)); //$this->arrayTrembita["requestdatatype"];
        $this->arrayDataResult["data"]                      = $this->jsonData; //$this->arrayDataSource["type"];
        $this->arrayDataResult["normalized_data"]           = "null"; //$this->arrayDataSource["type"];
        // var_dump(
        //     [
        //         $this->arrayDataResult
        //     ]
        // );die;
        $this->arrayDataResult["x_road_instance"]           = isset($this->arrayTrembita["xRoadInstance"]) ? "'" . $this->arrayTrembita["xRoadInstance"] . "'" : "null";
        $this->arrayDataResult["member_class"]              = isset($this->arrayTrembita["memberClass"]) ? "'" . $this->arrayTrembita["memberClass"] . "'" : "null";
        $this->arrayDataResult["member_code"]               = isset($this->arrayTrembita["memberCode"]) ? "'" . $this->arrayTrembita["memberCode"] . "'" : "null";
        $this->arrayDataResult["subsystem_code"]            = isset($this->arrayTrembita["subsystemCode"]) ? "'" . $this->arrayTrembita["subsystemCode"] . "'" : "null";
        $this->arrayDataResult["requestID"]                 = isset($this->arrayTrembita["requestID"]) ? "'" . $this->arrayTrembita["requestID"] . "'::numeric" : "null";
        if($this->arrayDataResult["requestID"] == "null"){ $this->arrayDataResult["requestID"] = $requestid_dms;}

        //EKG- card id
        $citizen = json_decode($camel->getPerson(
            $this->arrayDataSource["applicantInfo"]["foaf_familyName"],
            $this->arrayDataSource["applicantInfo"]["foaf_givenName"],
            $this->arrayDataSource["applicantInfo"]["person_patronymicName"],
            $this->arrayDataSource["applicantInfo"]["schema_birthDate"]
        ), true);
        if (empty($citizen)) {
            $citizen = json_decode($camel->getPerson(
                str_replace("'", "’", $this->arrayDataSource["applicantInfo"]["foaf_familyName"]),
                str_replace("'", "’", $this->arrayDataSource["applicantInfo"]["foaf_givenName"]),
                str_replace("'", "’", $this->arrayDataSource["applicantInfo"]["person_patronymicName"]),
                $this->arrayDataSource["applicantInfo"]["schema_birthDate"]
            ), true);
        }
        if (!empty($citizen)) {

            $this->arrayDataResult["citizen_id"]                = !empty($citizen[0]["citizenId"]) ? "'" . $citizen[0]["citizenId"] . "'" : 'null';
            //print_r($this->arrayDataResult["citizen_id"] . "\n\n");
            //$statement = json_decode($camel->getStatement($citizen[0]["citizenId"]), true);

            $register = json_decode($camel->getRegisterRecord($citizen[0]["citizenId"]), true);
            if (!empty($register)) {
                $this->arrayDataResult["register_record_id"]        = is_numeric($register[0]["id"]) ? "'" . $register[0]["id"] . "'" : 'null';;
                $this->arrayDataResult["statement_id"]              = is_numeric($register[0]["statementId"]) ? "'" . $register[0]["statementId"] . "'" : 'null';
            } else {
                //$this->arrayDataResult["citizen_id"]                = 'null';
                $this->arrayDataResult["register_record_id"]        = 'null';
                $this->arrayDataResult["statement_id"]              = 'null';
            }
            //var_dump($this->arrayDataResult["register_record_id"] );die;
            //var_dump($this->arrayDataResult["statement_id"]);die;
        } else {
            $this->arrayDataResult["citizen_id"]                = 'null';
            $this->arrayDataResult["register_record_id"]        = 'null';
            $this->arrayDataResult["statement_id"]              = 'null';
        }

        $this->arrayDataResult["is_mother_address"]         = 'null';

        if (isset($this->arrayDataSource["motherInfo"])) {
            $mother = $camel->getPerson(
                $this->arrayDataSource["motherInfo"]["foaf_familyName"],
                $this->arrayDataSource["motherInfo"]["foaf_givenName"],
                $this->arrayDataSource["motherInfo"]["person_patronymicName"],
                $this->arrayDataSource["motherInfo"]["schema_birthDate"]?$this->arrayDataSource["motherInfo"]["schema_birthDate"]:""
            );
        } else {
            $mother[0]["id"] = 'null';
        }

        $this->arrayDataResult["mother_id"]                 = !empty($mother[0]["citizenId"]) ? "'" . $mother[0]["citizenId"] . "'" : 'null';;
        $this->arrayDataResult["mother_original_address"]   = 'null';

        $this->arrayDataResult["mother_residence_id"]       = 'null';
        $this->arrayDataResult["mother_building_id"]        = 'null';
        $this->arrayDataResult["mother_street_id"]          = 'null';
        $this->arrayDataResult["mother_district_id"]        = 'null';
        $this->arrayDataResult["mother_locality_id"]        = 'null';
        $this->arrayDataResult["mother_country_id"]         = 'null';



        if (isset($this->arrayDataSource["fatherInfo"])) {
            if (!empty($this->arrayDataSource["fatherInfo"])) {
                $this->arrayDataSource["fatherInfo"]["foaf_familyName"]         = isset($this->arrayDataSource["fatherInfo"]["foaf_familyName"]) ? $this->arrayDataSource["fatherInfo"]["foaf_familyName"] : "null";
                $this->arrayDataSource["fatherInfo"]["foaf_givenName"]          = isset($this->arrayDataSource["fatherInfo"]["foaf_givenName"]) ? $this->arrayDataSource["fatherInfo"]["foaf_givenName"] : "null";
                $this->arrayDataSource["fatherInfo"]["person_patronymicName"]   = isset($this->arrayDataSource["fatherInfo"]["person_patronymicName"]) ? $this->arrayDataSource["fatherInfo"]["person_patronymicName"] : "null";
                $this->arrayDataSource["fatherInfo"]["schema_birthDate"]        = isset($this->arrayDataSource["fatherInfo"]["schema_birthDate"]) ? $this->arrayDataSource["fatherInfo"]["schema_birthDate"] : "null";

                $father = $camel->getPerson(
                    $this->arrayDataSource["fatherInfo"]["foaf_familyName"],
                    $this->arrayDataSource["fatherInfo"]["foaf_givenName"],
                    $this->arrayDataSource["fatherInfo"]["person_patronymicName"],
                    $this->arrayDataSource["fatherInfo"]["schema_birthDate"]
                );
                if ($father == "[]") {
                    unset($father);
                    $father[0]["citizenId"] = "null";
                } elseif (is_array($father)) {
                    if (!isset($father[0]["citizenId"])) {
                        $father[0]["citizenId"] = "null";
                    }
                }
            }
        } else {
            $father[0]["citizenId"] = 'null';
        }

        $this->arrayDataResult["father_id"]                 = $father[0]["citizenId"];
        $this->arrayDataResult["father_original_address"]   = 'null';

        $this->arrayDataResult["father_residence_id"]       = 'null';
        $this->arrayDataResult["father_building_id"]        = 'null';
        $this->arrayDataResult["father_street_id"]          = 'null';
        $this->arrayDataResult["father_district_id"]        = 'null';
        $this->arrayDataResult["father_locality_id"]        = 'null';
        $this->arrayDataResult["father_country_id"]         = 'null';


        $arrStreetOut = explode(".", $this->arrayDataSource["RegistrationAddress"]["cbc_StreetName"]);
        if (count($arrStreetOut) > 1) {
            $streetTypeOut = $arrStreetOut[0] . ".";
            $streetOut = trim($arrStreetOut[1]);
        } else {
            $streetTypeOut = "вул.";
            $streetOut = trim($arrStreetOut[0]);
        }
        $this->arrayDataSource["RegistrationAddress"]["cbc_CityName"] = isset($this->arrayDataSource["RegistrationAddress"]["cbc_CityName"]) ? $this->arrayDataSource["RegistrationAddress"]["cbc_CityName"] : "";
        $arrCityOut = explode(".", $this->arrayDataSource["RegistrationAddress"]["cbc_CityName"]);
        if (count($arrCityOut) > 1) {
            $cityOut = trim($arrCityOut[1]);
        } else {
            $cityOut = trim($arrCityOut[0]);
        }
        $this->arrayDataSource["RegistrationAddress"]["cbc_Region"] = isset($this->arrayDataSource["RegistrationAddress"]["cbc_Region"]) ? $this->arrayDataSource["RegistrationAddress"]["cbc_Region"] : "";
        $arrRegion = explode(" ", $this->arrayDataSource["RegistrationAddress"]["cbc_Region"]);
        if (count($arrRegion) > 1) {
            $region = trim($arrRegion[1]);
        } else {
            $region = trim($arrRegion[0]);
        }




        $this->arrayDataResult["out_statement_id"]          = 'null';
        $this->arrayDataResult["out_register_record_id"]    = 'null';

        $cbc_Country = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_Country"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_Country"] : "";
        $this->arrayDataSource["RegistrationAddress"]["cbc_District"] = isset($this->arrayDataSource["RegistrationAddress"]["cbc_District"]) ? $this->arrayDataSource["RegistrationAddress"]["cbc_District"] : "";
        $cbc_District = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_District"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_District"] : "";
        $region = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_Region"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_Region"] : "";
        $cbc_CityType = isset($this->arrayDataSource["RegistrationAddress"]["CityType"]) ? trim($this->arrayDataSource["RegistrationAddress"]["CityType"]) : "";
        $cbc_CityName = isset($this->arrayDataSource["RegistrationAddress"]["cbc_CityName"]) ? $this->arrayDataSource["RegistrationAddress"]["cbc_CityName"] : "";
        $City_District = isset($this->arrayDataSource["RegistrationAddress"]["City_District"]) ? trim($this->arrayDataSource["RegistrationAddress"]["City_District"]) : "";
        $City_District = !empty(trim($City_District)) ? trim($City_District) : "";
        $streetTypeOut = !empty($streetTypeOut) ? $streetTypeOut : "";
        $streetOut = !empty($streetOut) ? $streetOut : "";
        $BuildingPart = !empty($this->arrayDataSource["RegistrationAddress"]["BuildingPart"]) ?  $this->arrayDataSource["RegistrationAddress"]["BuildingPart"] : "";
        $cbc_BuildingNumber = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_BuildingNumber"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_BuildingNumber"]  : "";
        $Apartment = isset($this->arrayDataSource["RegistrationAddress"]["Apartment"]) ? $this->arrayDataSource["RegistrationAddress"]["Apartment"] : ""; //dissable "null" value

        $ora = json_decode($camel->getAdsess(
            $cbc_Country,
            $cbc_District,
            $region,
            $cityOut,
            $cbc_CityType,
            $City_District,
            $streetOut,
            $streetTypeOut,
            $cbc_BuildingNumber,
            $Apartment
        ), true);
        // print_r([
        //     $cbc_Country,
        //     $cbc_District,
        //     $region,
        //     $cityOut,
        //     $cbc_CityType,
        //     $City_District,
        //     $streetOut,
        //     $streetTypeOut,
        //     $cbc_BuildingNumber,
        //     $Apartment
        // ]);

        // $original_cbc_Country = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_Country"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_Country"] . ", " : "";
        // $original_cbc_District = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_District"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_District"] . ", " : "";
        // $original_region = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_Region"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_Region"] . ", " : "";
        // $original_cbc_CityName = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_CityName"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_CityName"] . ", " : "";
        // $original_City_District = !empty(trim($City_District)) ? trim($City_District) . ", " : "";
        // $original_streetTypeOut = !empty($streetTypeOut) ? $streetTypeOut . " " : "";
        // $original_streetOut = !empty($streetOut) ? $streetOut . ", " : "";
        // $original_cbc_BuildingNumber = !empty(trim($this->arrayDataSource["RegistrationAddress"]["cbc_BuildingNumber"])) ? $this->arrayDataSource["RegistrationAddress"]["cbc_BuildingNumber"] . " " : "";
        // $original_BuildingPart = !empty($this->arrayDataSource["RegistrationAddress"]["BuildingPart"]) ?  $this->arrayDataSource["RegistrationAddress"]["BuildingPart"] : "";
        // $original_Apartment = isset($this->arrayDataSource["RegistrationAddress"]["Apartment"]) ? ", кв. " . $this->arrayDataSource["RegistrationAddress"]["Apartment"] : "";



        $original_cbc_Country           =         $cbc_Country ? $cbc_Country . ", " : "";
        $original_cbc_District          =         trim($cbc_District) ? $cbc_District . ", " : "";
        $original_region                =         $region ? $region . ", " : "";
        $original_cbc_CityName          =         $cbc_CityName ? $cbc_CityName . ", " : "";
        $original_City_District         =         $City_District ? $City_District . ", " : "";
        $original_streetTypeOut         =         $streetTypeOut ? $streetTypeOut . " " : "";
        $original_streetOut             =         $streetOut ? $streetOut . ", " : "";
        $original_cbc_BuildingNumber    =         $cbc_BuildingNumber ? $cbc_BuildingNumber . " " : " ";
        $original_BuildingPart          =         $BuildingPart ? ", корпус " . $BuildingPart . " " : " ";
        $original_Apartment             =         isset($this->arrayDataSource["RegistrationAddress"]["Apartment"]) ? ", кв." . $this->arrayDataSource["RegistrationAddress"]["Apartment"] : "";


        $this->arrayDataResult["out_original_address"]      =   pg_escape_string(
            $original_cbc_Country
                . $original_cbc_District
                . $original_region
                . $original_cbc_CityName
                . $original_City_District
                . $original_streetTypeOut
                . $original_streetOut
                . $original_cbc_BuildingNumber
                . $original_BuildingPart
                . $original_Apartment
        );

        $this->arrayDataResult["out_residence_id"]          = isset($ora["residenceId"]) ? "'" . $ora["residenceId"] . "'" : "null";
        $this->arrayDataResult["out_building_id"]           = isset($ora["buildingId"]) ? "'" . $ora["buildingId"] . "'" : "null";
        $this->arrayDataResult["out_street_id"]             = isset($ora["streetId"]) ? "'" . $ora["streetId"] . "'" : "null";
        $this->arrayDataResult["out_district_id"]           = isset($ora["districtId"]) ? "'" . $ora["districtId"] . "'" : "null";

        $localityOut =  json_decode($camel->getLocality($cityOut, $cbc_CityType), true);

        $this->arrayDataResult["out_locality_id"]           = isset($localityOut[0]["id"]) ? "'" . $localityOut[0]["id"] . "'" : "null";
        $this->arrayDataResult["out_country_id"]            = isset($ora["countryId"]) ? "'" . $ora["countryId"] . "'" : "null";

        $city = explode(".", isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_CityName"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_CityName"] : "");
        if (count($city) > 1) {
            $city = trim($city[1]);
        } else {
            $city = trim($city[0]);
        }

        // if (count($arrStreet) > 1) {
        //     $streetType = $arrStreet[0] . ".";
        //     $street = trim($arrStreet[1]);
        // } else {
        //     $streetType = "вул.";
        //     $street = trim($arrStreet[0]);
        // }
        $arrRegion = explode(" ", isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_Region"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_Region"] : "");
        if (count($arrRegion) > 1 || !empty($arrRegion)) {
            $region = trim($arrRegion[0]);
        } else {
            //var_dump($arrRegion);
            $region = trim($arrRegion[1]);
        }

        $arrStreet = explode(".", isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_StreetName"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_StreetName"] : "");
        if (count($arrStreet) > 1) {
            $streetType = $arrStreet[0] . ".";
            $street = trim(preg_replace("/\(([\s\S]+?)\)/", '', $arrStreet[1]));
        } else {
            $streetType = "вул.";
            $street = trim(preg_replace("/\(([\s\S]+?)\)/", '', $arrStreet[0]));
        }
        // $building =
        //     trim($this->arrayDataSource["NewRegistrationAddress"]["cbc_BuildingNumber"])
        //     . !empty(trim($this->arrayDataSource["NewRegistrationAddress"]["BuildingPart"])) ? "-" . trim($this->arrayDataSource["NewRegistrationAddress"]["BuildingPart"]) : "";


        //$this->arrayDataSource["NewRegistrationAddress"]["cbc_BuildingNumber"]."-".$this->arrayDataSource["NewRegistrationAddress"]["BuildingPart"];
        $this->arrayDataSource["NewRegistrationAddress"]["cbc_District"] = isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_District"]) ? trim($this->arrayDataSource["NewRegistrationAddress"]["cbc_District"]) : "";


        $new_cbc_Country = !empty(trim($this->arrayDataSource["NewRegistrationAddress"]["cbc_Country"])) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_Country"] : "";
        $new_cbc_District = !empty(trim($this->arrayDataSource["NewRegistrationAddress"]["cbc_District"])) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_District"] : "";
        $new_region = isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_Region"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_Region"] : "";
        $new_cbc_CityName = isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_CityName"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_CityName"] : "";
        $new_City_District = !empty(trim($this->arrayDataSource["NewRegistrationAddress"]["City_District"])) ? trim($this->arrayDataSource["NewRegistrationAddress"]["City_District"]) : "";
        $new_streetType = !empty($streetType) ? $streetType : "";
        $new_street = !empty($street) ? $street : "";
        $new_cbc_BuildingNumber = isset($this->arrayDataSource["NewRegistrationAddress"]["cbc_BuildingNumber"]) ? $this->arrayDataSource["NewRegistrationAddress"]["cbc_BuildingNumber"] : "";
        $new_BuildingPart = isset($this->arrayDataSource["NewRegistrationAddress"]["BuildingPart"]) ? $this->arrayDataSource["NewRegistrationAddress"]["BuildingPart"] : "";
        $new_cityType = isset($this->arrayDataSource["NewRegistrationAddress"]["CityType"]) ? $this->arrayDataSource["NewRegistrationAddress"]["CityType"] : "";
        $new_Apartment = isset($this->arrayDataSource["NewRegistrationAddress"]["Apartment"]) ? trim($this->arrayDataSource["NewRegistrationAddress"]["Apartment"]) : "";

        $nra = json_decode($camel->getAdsess(
            $new_cbc_Country,
            $new_cbc_District,
            $new_region,
            $city,
            $new_cityType,
            $new_City_District,
            $new_street,
            $new_streetType,
            $new_cbc_BuildingNumber,
            $new_Apartment
        ), true);

        // print_r([
        //     $new_cbc_Country,
        //     $new_cbc_District,
        //     $new_region,
        //     $city,
        //     $new_cityType,
        //     $new_City_District,
        //     $street,
        //     $streetType,
        //     $new_cbc_BuildingNumber,
        //     $new_Apartment
        // ]);

        $new_original_cbc_Country           =         $new_cbc_Country ? $new_cbc_Country . ", " : "";
        $new_original_cbc_District          =         trim($new_cbc_District) ? $new_cbc_District . ", " : "";
        $new_original_region                =         $new_region ? $new_region . ", " : "";
        $new_original_cbc_CityName          =         $new_cbc_CityName ? $new_cbc_CityName . ", " : "";
        $new_original_City_District         =         $new_City_District ? $new_City_District . ", " : "";
        $new_original_streetType            =         $new_streetType ? $new_streetType . " " : "";
        $new_original_street                =         $new_street ? $new_street . ", " : "";
        $new_original_cbc_BuildingNumber    =         $new_cbc_BuildingNumber ? $new_cbc_BuildingNumber . " " : " ";
        $new_original_BuildingPart          =         $new_BuildingPart ? ", корпус " . $new_BuildingPart . " " : " ";
        $new_original_Apartment             =         isset($this->arrayDataSource["NewRegistrationAddress"]["Apartment"]) ? ", кв." . $this->arrayDataSource["NewRegistrationAddress"]["Apartment"] : "";

        $this->arrayDataResult["original_address"]          =   pg_escape_string(
            $new_original_cbc_Country
                . $new_original_cbc_District
                . $new_original_region
                . $new_original_cbc_CityName
                . $new_original_City_District
                . $new_original_streetType
                . $new_original_street
                . $new_original_cbc_BuildingNumber
                . $new_original_BuildingPart
                . $new_original_Apartment
        );


        $this->arrayDataResult["residence_id"]              = !empty($nra["residenceId"]) ? "'" . $nra["residenceId"] . "'" : "null";
        $this->arrayDataResult["street_id"]                 = !empty($nra["streetId"]) ? "'" . $nra["streetId"] . "'" : "null";
        $this->arrayDataResult["district_id"]               = !empty($nra["districtId"]) ? "'" . $nra["districtId"] . "'" : "null";
        $locality                                           = json_decode($camel->getLocality($city, $new_cityType), true);
        $this->arrayDataResult["locality_id"]               = !empty($locality[0]["id"]) ? "'" . $locality[0]["id"] . "'" : "null";
        $this->arrayDataResult["country_id"]                = !empty($nra["countryId"]) ? "'" . $nra["countryId"] . "'" : "null";
        $this->arrayDataResult["building_id"]               = !empty($nra["buildingId"]) ? "'" . $nra["buildingId"] . "'" : "null";

        $this->arrayDataResult["status"]                    = 'null';
        $this->arrayDataResult["user_id"]                   = isset($this->arrayDataResult["user_id"]) ? $this->arrayDataResult["user_id"] : 'null';
        $this->arrayDataResult["organization_id"]           = isset($this->arrayDataResult["organization_id"]) ? $this->arrayDataResult["organization_id"] : 'null';
        //$this->arrayDataResult["locked"]                    = 'null';
        //$this->arrayDataResult["version"]                   = 'null';
        //$this->arrayDataResult["processed"]                 = 'null';

        //print_r($this->arrayDataResult);
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
                        ,{$this->arrayDataResult['mother_original_address']} 
                        ,{$this->arrayDataResult['mother_residence_id']}
                        ,{$this->arrayDataResult['mother_building_id']}
                        ,{$this->arrayDataResult['mother_street_id']} 
                        ,{$this->arrayDataResult['mother_district_id']} 
                        ,{$this->arrayDataResult['mother_locality_id']} 
                        ,{$this->arrayDataResult['mother_country_id']} 
                        ,{$this->arrayDataResult['father_id']} 
                        ,{$this->arrayDataResult['father_original_address']}
                        ,{$this->arrayDataResult['father_residence_id']}
                        ,{$this->arrayDataResult['father_building_id']} 
                        ,{$this->arrayDataResult['father_street_id']} 
                        ,{$this->arrayDataResult['father_district_id']} 
                        ,{$this->arrayDataResult['father_locality_id']} 
                        ,{$this->arrayDataResult['father_country_id']} 
                        ,{$this->arrayDataResult['out_statement_id']} 
                        ,{$this->arrayDataResult['out_register_record_id']} 
                        ,'{$this->arrayDataResult['out_original_address']}'
                        ,{$this->arrayDataResult['out_residence_id']} 
                        ,{$this->arrayDataResult['out_building_id']} 
                        ,{$this->arrayDataResult['out_street_id']} 
                        ,{$this->arrayDataResult['out_district_id']} 
                        ,{$this->arrayDataResult['out_locality_id']} 
                        ,{$this->arrayDataResult['out_country_id']} 
                        ,'{$this->arrayDataResult['original_address']}' 
                        ,{$this->arrayDataResult['residence_id']} 
                        ,{$this->arrayDataResult['building_id']} 
                        ,{$this->arrayDataResult['street_id']}
                        ,{$this->arrayDataResult['district_id']} 
                        ,{$this->arrayDataResult['locality_id']}
                        ,{$this->arrayDataResult['country_id']} 
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
                        --, mother_id={$this->arrayDataResult['mother_id']} 
                        --, mother_original_address='{$this->arrayDataResult['mother_original_address']}' 
                        --, mother_residence_id={$this->arrayDataResult['mother_residence_id']}
                        --, mother_building_id={$this->arrayDataResult['mother_building_id']}
                        --, mother_street_id={$this->arrayDataResult['mother_street_id']} 
                        --, mother_district_id={$this->arrayDataResult['mother_district_id']} 
                        --, mother_locality_id={$this->arrayDataResult['mother_locality_id']}
                        --, mother_country_id={$this->arrayDataResult['mother_country_id']} 
                        --, father_id={$this->arrayDataResult['father_id']} 
                        --, father_original_address='{$this->arrayDataResult['father_original_address']}'
                        --, father_residence_id={$this->arrayDataResult['father_residence_id']}
                        --, father_building_id={$this->arrayDataResult['father_building_id']} 
                        --, father_street_id={$this->arrayDataResult['father_street_id']} 
                        --, father_district_id={$this->arrayDataResult['father_district_id']} 
                        --, father_locality_id={$this->arrayDataResult['father_locality_id']} 
                        --, father_country_id={$this->arrayDataResult['father_country_id']} 
                        , out_original_address='" . $this->arrayDataResult['out_original_address'] . "'
                        , out_residence_id={$this->arrayDataResult['out_residence_id']} 
                        , out_building_id={$this->arrayDataResult['out_building_id']}
                        , out_street_id={$this->arrayDataResult['out_street_id']}
                        , out_district_id={$this->arrayDataResult['out_district_id']}
                        , out_locality_id={$this->arrayDataResult['out_locality_id']}
                        , out_country_id={$this->arrayDataResult['out_country_id']} 
                        , original_address='{$this->arrayDataResult['original_address']}' 
                        , residence_id={$this->arrayDataResult['residence_id']}
                        , building_id={$this->arrayDataResult['building_id']}
                        , street_id={$this->arrayDataResult['street_id']}
                        , district_id={$this->arrayDataResult['district_id']}
                        , locality_id={$this->arrayDataResult['locality_id']}
                        , country_id={$this->arrayDataResult['country_id']}
                        , updated=now()
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
                $this->arrayDataResult = $this->NormalaiseData($camel[0]["data"]);
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
}
