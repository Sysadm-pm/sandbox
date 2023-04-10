<?php
error_reporting(0);
header('charset=UTF-8');


// print "Null -> " . json_encode(new jsontest(null)) . "\n";
// print "Array -> " . json_encode(new jsontest(Array(1))) . "\n";
// print "Assoc. -> " . json_encode(new jsontest(Array('a'=>1,'b'=>3,'c'=>4))) . "\n";
// print "Int -> " . json_encode(new jsontest(5)) . "\n";
// print "String -> " . json_encode(new jsontest('Hello, World!')) . "\n";
// print "Object -> " . json_encode(new jsontest((object) Array('a'=>1,'b'=>3,'c'=>4))) . "\n";


// die;
require_once('/home/.../db.class.php');

class jsontest implements JsonSerializable
{
    function __construct($value)
    {
        $this->value = $value;
    }
    function jsonSerialize()
    {
        return $this->value;
    }
}
function str_contains($haystack = "", $needle = ""): bool
{
    $haystack = mb_strtolower($haystack);
    $needle = mb_strtolower($needle);
    return '' === $needle || false !== strpos($haystack, $needle);
}

function RegTypes($var)
{

    switch ($var) {
        case 1130.4;
        case 1130.5;
        case 1130.6:
            return 2;
            break;
        case 1274.1:
            return 3;
            break;
        default:
            return 1;
            break;
    }
}
function move_to_top(&$array, $key)
{
    $temp = array($key => $array[$key]);
    unset($array[$key]);
    $array = $temp + $array;
}
echo "Script start >>> " . date('Y-m-d H:i:s P') . "\n";
try {
    $db = new \DBext("-",  "-", "-", "-");
} catch (\Throwable $th) {
    print_r("No connection to RTGK-DB!\n\n");
    print_r($th);
    die;
}
try {
    $db_au = new \DBext("-",  "-", "-", "-");
} catch (\Throwable $th) {
    print_r("No connection to RTGK-Auth!\n\n");
    print_r($th);
    die;
}
try {
    //$db_adr = new \DBext("-",  "-", "-", "-");
} catch (\Throwable $th) {
    print_r("No connection to Adresses!\n\n");
    print_r($th);
    die;
}

$sql = file_get_contents('dms_collect2.sql');
echo "Start DATA loading >>> " . date('Y-m-d H:i:s P') . "\n";
$data = $db->query($sql);
echo "DATA loaded >>> " . date('Y-m-d H:i:s P') . "\n";

$cc = 0;
$cb = 0;
$stringCount = 0;
echo "Start foreach >>> " . date('Y-m-d H:i:s P') . "\n";

$del_sql = "
            DELETE FROM \"temp\".rtg_test
            WHERE 1=1
            ";
$db->query($del_sql);
foreach ($data as $key => $value) {
    $registration = json_decode($value["Registration"], true);
    unset($data[$key]["Registration"]);

    //$value["ttt"] = $registration;
    $data[$key]["Person"] = $registration["Person"];
    //move_to_top($data[$key], "Person");
    $data[$key]["Document"] = $registration["Document"];
    $data[$key]["CardType"] = $registration["CardType"];
    $data[$key]["SourceID"] = $registration["SourceID"];
    $data[$key]["CardRegDate"] = $registration["CardRegDate"];
    $data[$key]["AddrRegDate"] = $registration["AddrRegDate"]; //"CardRegDate", "AddrRegDate", "CardRegOrgan", "SourcePersID", "CardRegReason"
    $data[$key]["CardRegOrgan"] = $registration["CardRegOrgan"];
    $data[$key]["SourcePersID"] = $registration["SourcePersID"];
    $data[$key]["CardRegReason"] = $registration["CardRegReason"];

    if (empty($data[$key]["Person"]["Born_address"])) {
        $data[$key]["Person"]["Born_address"] = [];
    }
    $data[$key]["Reg_address"] = [];
    $data[$key]["InOut_address"] = [];
    //var_dump($data[$key]);
    // ----   CardRegOrgan ----- //  
    if ($value["organization"]) {
        $sql_au = "SELECT o.name 
                        FROM public.organizations o 
                        WHERE o.id = " . $value["organization"];
        $result = $db_au->query($sql_au)[0];
        $data[$key]["CardRegOrgan"] = $result["name"];
    } else {
        $data[$key]["CardRegOrgan"] = "ГІОЦ-КМДА";
    }
    // ----   Born_address ----- // 
    //$data[$key]["Registration"]["Person"]["Born_address"][] = [];

    $sql_adr = "SELECT ao.address_object_dkbs 
                        FROM address.address_object ao 
                        WHERE ao.address_object_guid = '" . $value["building_id"] . "'";
    $result_bid = $db_adr->query($sql_adr);

    $data[$key]["RegType"] = RegTypes($result_bid[0]["address_object_dkbs"]);

    if ($value["current_building"]) {
        $sql_adr = "SELECT ao.address_object_dkbs 
                            FROM address.address_object ao 
                            WHERE ao.address_object_guid = '" . $value["current_building"] . "'";
        $result_bid = $db_adr->query($sql_adr);
        $data[$key]["InOutType"] = RegTypes($result_bid[0]["address_object_dkbs"]);
    }
    //$value["InOutType"] = null;

    if ($value["current_residence"]) {
        $sql_adr = "SELECT *
                            FROM address.vaddress_search s
                        JOIN address.locality l ON s.locality_guid = l.locality_guid
                            LEFT JOIN address.region r ON s.region_guid = r.region_guid
                            LEFT JOIN address.district d ON s.district_guid = d.district_guid
                            JOIN address.address_object ao ON s.ao_guid = ao.address_object_guid
                        WHERE s.dataguid = '{$value["current_residence"]}' 
                            --and(NOT ao.address_object_own_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]' 
                            --OR (ao.address_object_own_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]' and ao.master_guid IS Null))
                            ";
        $result_ioad = $db_adr->query($sql_adr);
        if (!empty($result_ioad[0]["master_guid"])) {
            //print_r("hello");    
            $sql_adr = "SELECT
                             s.dataguid, s.masterguid, s.post_index, s.country_guid, s.country_name, s.region_type_guid, s.region_type, s.region_type_full, s.region_type_at_first_position, s.region_guid, s.region_name, s.district_type_guid, s.district_type, s.district_type_full, s.district_type_at_first_position, s.district_guid, s.district_name, s.locality_type_guid, s.locality_type, s.locality_type_full, s.locality_type_at_first_position, s.locality_guid, s.locality_name, s.locality_area_type_guid, s.locality_area_type, s.locality_area_type_full, s.locality_area_type_at_first_position, s.locality_area_guid, s.locality_area_name, s.street_type_guid, s.street_type, s.street_type_full, s.street_type_at_first_position, s.street_guid, s.street_name, s.ao_type_guid, s.ao_type, s.ao_type_full, s.ao_type_at_first_position, s.ao_guid, m.ao_name, s.ao_child_type_guid, s.ao_child_type, s.ao_child_type_full, s.ao_child_type_at_first_position, s.ao_child_name, s.pao_type_guid, s.pao_type, s.pao_type_full, s.pao_type_at_first_position, s.pao_guid, s.pao_name, s.ao_has_inherited_objects, s.premise_type_guid, s.premise_type, s.premise_type_full, s.premise_type_at_first_position, s.premise_guid, s.premise_name, l.locality_koatuu
                            FROM address.vaddress_search s
                                JOIN address.locality l ON s.locality_guid = l.locality_guid
                                LEFT JOIN address.region r ON s.region_guid = r.region_guid
                                LEFT JOIN address.district d ON s.district_guid = d.district_guid
                                ,address.vaddress_search m  
                            WHERE s.dataguid = '{$value["current_residence"]}'
                            and s.ao_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]'
                            and EXISTS (select address_object_id from address.address_object ao 
                                where s.ao_guid = ao.address_object_guid and ao.master_guid = m.dataguid
                                )
                            ";

            $camel_ioad = $db_adr->query($sql_adr);

            // var_dump(
            //     json_encode(new jsontest(
            //         [
            //             "result_ioad" => [
            //                 "ao_name"   =>     $result_ioad[0]["ao_name"],
            //                 "pao_name"   =>     $result_ioad[0]["pao_name"],
            //                 "ao_child_name"   =>     $result_ioad[0]["ao_child_name"]
            //             ],
            //             "camel_ioad" => [
            //                 "ao_name"   =>     $camel_ioad[0]["ao_name"],
            //                 "ao_guid"   =>     $camel_ioad[0]["ao_guid"],
            //                 "pao_name"   =>     $camel_ioad[0]["pao_name"],
            //                 "ao_child_name"   =>     $camel_ioad[0]["ao_child_name"]
            //             ],
            //             [
            //                 "current_residence" => $value["current_residence"]
            //             ],
            //             [
            //                 "camel_ioad" => $camel_ioad,
            //                 "result_ioad" => $result_ioad,
            //                 "master_guid" => $result_ioad[0]["master_guid"]
            //             ]

            //         ]
            //     ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            // );
            // //var_dump($camel_ioad);
            // die;
            if (!empty($camel_ioad)) {
                $result_ioad = $camel_ioad;
            }
        } else {
        }
        //print_r($result_ioad[0]);

        $data[$key]["InOut_address"][] = ["type" => "country", "name" => $result_ioad[0]["country_name"]];
        if (!empty($result_ioad[0]["region_name"]) && mb_strtolower($result_ioad[0]["region_name"]) !== "київ") {
            $data[$key]["InOut_address"][] = ["type" => "region", "name" => $result_ioad[0]["region_name"] . " " . $result_ioad[0]["region_type_full"], "koatuu" => $result_ioad[0]["region_koatuu"]];
        }
        if (!empty($result_ioad[0]["district_name"])) {
            $data[$key]["InOut_address"][] = ["type" => "district", "name" => $result_ioad[0]["district_name"] . " " . $result_ioad[0]["district_type_full"], "koatuu" => $result_ioad[0]["district_koatuu"]];
        }
        $data[$key]["InOut_address"][] = ["type" => "city", "prefix" => $result_ioad[0]["locality_type_full"], "name" => $result_ioad[0]["locality_name"], "koatuu" => $result_ioad[0]["locality_koatuu"]];
        $data[$key]["InOut_address"][] = ["type" => "city_district", "prefix" => $result_ioad[0]["locality_area_type_full"], "name" => $result_ioad[0]["locality_area_name"]];
        $data[$key]["InOut_address"][] = ["type" => "street", "prefix" => $result_ioad[0]["street_type_full"], "name" => $result_ioad[0]["street_name"]];

        if (!empty($result_ioad[0]["ao_child_name"])) {
            $data[$key]["InOut_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_ioad[0]["ao_name"]];
            $data[$key]["InOut_address"][] = ["type" => "building", "prefix" => "корпус", "name" => $result_ioad[0]["ao_child_name"]];
        } elseif (!empty($result_ioad[0]["pao_name"])) {
            $data[$key]["InOut_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_ioad[0]["ao_name"]];
            $data[$key]["InOut_address"][] = ["type" => "building", "prefix" => "корпус", "name" => $result_ioad[0]["pao_name"]];
        } else {
            $data[$key]["InOut_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_ioad[0]["ao_name"]];
        }

        if (empty($result_ioad[0]["premise_name"]) || $result_ioad[0]["premise_type_full"] == 'приватний будинок' || $result_ioad[0]["premise_type_full"] == 'соціальний заклад' || $result_ioad[0]["premise_type_full"] == 'адміністративна будівля' || $result_ioad[0]["premise_type_full"] == 'військова частина' || ($result_ioad[0]["premise_type_full"] == 'гуртожиток' && !str_contains($result_ioad[0]["premise_name"], 'кім'))) {
        } else {
            switch ($result_ioad[0]["premise_type_full"]) {
                case 'кімната в комунальній квартирі';
                case 'кімната в гуртожитку';
                case 'жилий блок';
                case 'блок';
                case 'кімната';
                case ($result_ioad[0]["premise_type_full"] == 'гуртожиток' && str_contains($result_ioad[0]["premise_name"], 'кім')):
                    $swith_flat_type = 'кімната';
                    break;
                case 'приміщення':
                    $swith_flat_type = 'житлове приміщення';
                    break;
                default:
                    $swith_flat_type = 'квартира';
                    break;
            }
            $data[$key]["InOut_address"][] = ["type" => "flat", "prefix" => $swith_flat_type, "name" => $result_ioad[0]["premise_name"]];
        }
    } else {
        //$result_vaddr = "";
        if ($value["current_residence_country"] && !$value["current_locality"] && !$value["current_residence_district"]) {

            $sql_vaddr = "SELECT * 
                        FROM address.vaddress_search v
                        WHERE v.dataguid = '" . $value["current_residence_country"] . "'";
            $result_vaddr = $db_adr->query($sql_vaddr);
            $data[$key]["InOut_address"][] = ["type" => "country", "name" => $result_vaddr[0]["country_name"]];
        } elseif ($value["current_residence_country"] && $value["current_locality"] && !$value["current_residence_district"]) {
            $sql_vaddr = "SELECT * 
                        FROM address.vaddress_search v
                            JOIN address.locality l ON l.locality_guid = v.locality_guid
                            LEFT JOIN address.region r ON r.region_guid = v.region_guid
                        WHERE v.dataguid = '" . $value["current_locality"] . "'";
            $result_vaddr = $db_adr->query($sql_vaddr);
            $data[$key]["InOut_address"][] = ["type" => "country", "name" => $result_vaddr[0]["country_name"]];
            if ($result_vaddr[0]["region_name"] && mb_strtolower(trim($result_vaddr[0]["region_name"])) !== "київ") {
                $data[$key]["InOut_address"][] = ["type" => "region", "name" => $result_vaddr[0]["region_name"] . " " . $result_vaddr[0]["region_type_full"], "koatuu" => $result_vaddr[0]["region_koatuu"]];
            }
            $data[$key]["InOut_address"][] = ["type" => "city", "prefix" => $result_vaddr[0]["locality_type_full"], "name" => $result_vaddr[0]["locality_name"], "koatuu" => $result_vaddr[0]["locality_koatuu"]];
        } else {
            //var_dump($result_vaddr);
        }
    }


    $sql_adr = "SELECT *
                        FROM address.vaddress_search s
        JOIN address.locality l ON s.locality_guid = l.locality_guid
        LEFT JOIN address.region r ON s.region_guid = r.region_guid
        LEFT JOIN address.district d ON s.district_guid = d.district_guid
        JOIN address.address_object ao ON s.ao_guid = ao.address_object_guid
    WHERE s.dataguid = '{$value["residence_id"]}' 
        --and(NOT ao.address_object_own_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]' 
           -- OR (ao.address_object_own_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]' and ao.master_guid IS Null))
          ";
    $result_rad = $db_adr->query($sql_adr);
    //echo "\nresult_rad\n";
    //print_r([$result_rad,$result_rad[0]["master_guid"]]);
    if (!empty($result_rad[0]["master_guid"])) {
        //print_r("hello");    
        $sql_adr = "SELECT
            s.dataguid, s.masterguid, s.post_index, s.country_guid, s.country_name, s.region_type_guid, s.region_type, s.region_type_full, s.region_type_at_first_position, s.region_guid, s.region_name, s.district_type_guid, s.district_type, s.district_type_full, s.district_type_at_first_position, s.district_guid, s.district_name, s.locality_type_guid, s.locality_type, s.locality_type_full, s.locality_type_at_first_position, s.locality_guid, s.locality_name, s.locality_area_type_guid, s.locality_area_type, s.locality_area_type_full, s.locality_area_type_at_first_position, s.locality_area_guid, s.locality_area_name, s.street_type_guid, s.street_type, s.street_type_full, s.street_type_at_first_position, s.street_guid, s.street_name, s.ao_type_guid, s.ao_type, s.ao_type_full, s.ao_type_at_first_position, s.ao_guid, m.ao_name, s.ao_child_type_guid, s.ao_child_type, s.ao_child_type_full, s.ao_child_type_at_first_position, s.ao_child_name, s.pao_type_guid, s.pao_type, s.pao_type_full, s.pao_type_at_first_position, s.pao_guid, s.pao_name, s.ao_has_inherited_objects, s.premise_type_guid, s.premise_type, s.premise_type_full, s.premise_type_at_first_position, s.premise_guid, s.premise_name, l.locality_koatuu
            FROM address.vaddress_search s
                JOIN address.locality l ON s.locality_guid = l.locality_guid
                LEFT JOIN address.region r ON s.region_guid = r.region_guid
                LEFT JOIN address.district d ON s.district_guid = d.district_guid
                ,address.vaddress_search m  
            WHERE s.dataguid = '{$value["residence_id"]}'
            and s.ao_name ~* '\(|\)|\*|;|,|\.|\"|''|[a-zA-Z]'
            and EXISTS (select address_object_id from address.address_object ao 
                   where s.ao_guid = ao.address_object_guid and ao.master_guid = m.dataguid
                )
            ";
        $camel_rad = $db_adr->query($sql_adr);
        //echo "\ncamel_rad\n";
        //print_r($camel_rad);
        if (!empty($camel_rad)) {
            $result_rad = $camel_rad;
        }
    }

    $data[$key]["Reg_address"][] = ["type" => "country", "name" => $result_rad[0]["country_name"]];
    if (!empty($result_rad[0]["region_name"]) && mb_strtolower($result_rad[0]["region_name"]) !== "київ") {
        $data[$key]["Reg_address"][] = ["type" => "region", "name" => $result_rad[0]["region_name"] . " " . $result_rad[0]["region_type_full"], "koatuu" => $result_rad[0]["region_koatuu"]];
    }
    if (!empty($result_rad[0]["district_name"])) {
        $data[$key]["Reg_address"][] = ["type" => "district", "name" => $result_rad[0]["district_name"] . " " . $result_rad[0]["district_type_full"], "koatuu" => $result_rad[0]["district_koatuu"]];
    }
    $data[$key]["Reg_address"][] = ["type" => "city", "prefix" => $result_rad[0]["locality_type_full"], "name" => $result_rad[0]["locality_name"], "koatuu" => $result_rad[0]["locality_koatuu"]];
    $data[$key]["Reg_address"][] = ["type" => "city_district", "prefix" => $result_rad[0]["locality_area_type_full"], "name" => $result_rad[0]["locality_area_name"]];
    $data[$key]["Reg_address"][] = ["type" => "street", "prefix" => $result_rad[0]["street_type_full"], "name" => $result_rad[0]["street_name"]];

    if (!empty($result_rad[0]["ao_child_name"])) {
        $data[$key]["Reg_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_rad[0]["ao_name"]];
        $data[$key]["Reg_address"][] = ["type" => "building", "prefix" => "корпус", "name" => $result_rad[0]["ao_child_name"]];
    } elseif (!empty($result_rad[0]["pao_name"])) {
        $data[$key]["Reg_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_rad[0]["ao_name"]];
        $data[$key]["Reg_address"][] = ["type" => "building", "prefix" => "корпус", "name" => $result_rad[0]["pao_name"]];
    } else {
        $data[$key]["Reg_address"][] = ["type" => "building", "prefix" => "будинок", "name" => $result_rad[0]["ao_name"]];
    }

    if (empty($result_rad[0]["premise_name"]) || $result_rad[0]["premise_type_full"] == 'приватний будинок' || $result_rad[0]["premise_type_full"] == 'соціальний заклад' || $result_rad[0]["premise_type_full"] == 'адміністративна будівля' || $result_rad[0]["premise_type_full"] == 'військова частина' || ($result_rad[0]["premise_type_full"] == 'гуртожиток' && !str_contains($result_rad[0]["premise_name"], 'кім'))) {
    } else {
        switch ($result_rad[0]["premise_type_full"]) {
            case 'кімната в комунальній квартирі';
            case 'кімната в гуртожитку';
            case 'жилий блок';
            case 'блок';
            case 'кімната';
            case ($result_rad[0]["premise_type_full"] == 'гуртожиток' && str_contains($result_rad[0]["premise_name"], 'кім')):
                $swith_flat_type = 'кімната';
                break;
            case 'приміщення':
                $swith_flat_type = 'житлове приміщення';
                break;
            default:
                $swith_flat_type = 'квартира';
                break;
        }
        $data[$key]["Reg_address"][] = ["type" => "flat", "prefix" => $swith_flat_type, "name" => $result_rad[0]["premise_name"]];
    }


    //$data[$key]["Reg_address"] = null;


    if ($value["country_of_birth"] or $value["region_of_birth"] or $value["area_of_birth"]) {



        if ($value["country_of_birth"] == "1437c9b6-370f-11e7-8ed7-000c29ff5864" /*|| empty($value["Born_address"])*/) {
            # code...
            $sql_adr = "SELECT * 
                                FROM address.vlocality_search v 
                                WHERE 
                                ";
            // if($value["country_of_birth"]){
            // $value["country_of_birth"]   = "'" . $value["country_of_birth"] . "'"?: 'null';
            $sql_adr_country    = $value["country_of_birth"] ? "\rv.locality_country_guid = '{$value["country_of_birth"]}'" : '';
            // }else{$sql_adr_country="";} 

            // if($value["region_of_birth"]){
            // $value["region_of_birth"]    = $value["region_of_birth"] ? "'" .$value["region_of_birth"]. "'": 'null';
            $sql_adr_region     = $value["region_of_birth"] ? "\nand v.locality_region_guid = '{$value["region_of_birth"]}'" : '';
            // }else{$sql_adr_region="";}

            // if($value["area_of_birth"]){
            //     $value["area_of_birth"]      = $value["area_of_birth"] ?"'" .$value["area_of_birth"]. "'": 'null';
            $sql_adr_district   = $value["area_of_birth"] ? "\nand v.locality_district_guid = '{$value["area_of_birth"]}'" : '';
            // }else{$sql_adr_district="";}

            //if($value["place_of_birth"]){
            $sql_adr_locality   = $value["place_of_birth"] ? "\nand v.locality_guid = '{$value["place_of_birth"]}'" : "";
            //}else{$sql_adr_locality="";}

            $sql_adr = $sql_adr . $sql_adr_country . $sql_adr_region . $sql_adr_district . $sql_adr_locality;
            //$data[$key]["Person"]["Born_address"] = [];


            $result = $db_adr->query($sql_adr);

            $data[$key]["Person"]["Born_address"][] = ["type" => "country", "name" => $result[0]["country_name"]];
            if (!empty($result[0]["region_type_full"]) && mb_strtolower($result[0]["region_name"]) !== "київ") {
                $data[$key]["Person"]["Born_address"][] = ["type" => "region", "name" => $result[0]["region_name"] . " " . $result[0]["region_type_full"]];
            }
            if (!empty($result[0]["district_type_full"])) {
                $data[$key]["Person"]["Born_address"][] = ["type" => "district", "name" => $result[0]["district_name"] . " " . $result[0]["district_type_full"]];
            }
            $data[$key]["Person"]["Born_address"][] = ["type" => "city", "prefix" => $result[0]["locality_type_full"], "name" => $result[0]["locality_name"], "koatuu" => $result[0]["locality_koatuu"]];
            $data[$key]["Person"]["Born_address_txt"] = null;
            // echo "\n\n----------------->";
            // print_r([
            //     "country_of_birth"=>$value["country_of_birth"],
            //     "region_of_birth"=>$value["region_of_birth"],
            //     "area_of_birth"=>$value["area_of_birth"],
            //     "place_of_birth"=>$value["place_of_birth"]
            // ]);
            //         //print_r($sql_adr);
            //         //$data[$key]["CardRegOrgan"] = $result["name"];
            // echo "\n\n<-----------------";

        } else {
            if (empty($data[$key]["Person"]["Born_address"])) {
                $data[$key]["Person"]["Born_address"][] = ["type" => "country", "name" => "Україна"];
                $data[$key]["Person"]["Born_address_txt"] = null;
            }
            //    if($value["country_of_birth"] && $value["area_of_birth"]){

            //        echo "\n ===================hello==================";
            //        print_r([
            //             "country_of_birth"=>$value["country_of_birth"],
            //             "region_of_birth"=>$value["region_of_birth"],
            //             "area_of_birth"=>$value["area_of_birth"],
            //             "place_of_birth"=>$value["place_of_birth"]
            //         ]);
            //         echo "\n ===================hello==================";
            //    }
        }
    } else {
        //$data[$key]["CardRegOrgan"] = "ГІОЦ-КМДА";
        print_r("\nno Born_address pharams\n");
    }




    if ($value["building_id"]) {
        //echo "\t\t\n \e[91m===================(".$key.")==================\e[39m\n";
        // $books = array(26, 0, 2, 1, 4, 5, 6, 7, 8, 9, 10, 11, 12 ,13 ,14 ,15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 3);
        // $books =      array(    26,         0,          2,              1,             4,       5,           6,                 7,                     8,           9,                  10,                 11,                 12 ,                 1 ,         14,           15,             16,             5,             18,             19,                     20,                     21,                         22,                     23,                 24,           0,         3,           2,            4,            0,            0,             0,               0,                0,     );
        // $sort_array = array("date_oper","for_dms", "CardRegOrgan", "organization", "r_id", "citizen_id", "Born_address", "country_of_birth", "region_of_birth", "area_of_birth", "place_of_birth", "Born_address_txt", "foreign_place_of_birth", "RegType", "Reg_address", "building_id", "residence_id", "InOutType", "InOut_address", "current_address", "current_residence_country", "current_locality", "current_residence_district", "current_building", "current_residence", "Person", "Document", "CardType", "SourceID", "CardRegDate", "AddrRegDate", "CardRegOrgan", "SourcePersID", "CardRegReason" );
        // array_multisort($books, $sort_array);
        $final = [];
        $final["Person"] = $data[$key]["Person"];
        $final["Person"]["Born_address"] = empty($data[$key]["Person"]["Born_address"]) ? "" : $data[$key]["Person"]["Born_address"];
        $final["RegType"] = $data[$key]["RegType"];
        $final["CardType"] = $data[$key]["CardType"];
        $final["Document"] = $data[$key]["Document"];
        $final["SourceID"] = $data[$key]["SourceID"];
        $final["InOutType"] = $data[$key]["InOutType"];
        $final["AddrRegDate"] = $data[$key]["AddrRegDate"];
        $final["CardRegDate"] = $data[$key]["CardRegDate"];
        $final["Reg_address"] = $data[$key]["Reg_address"];
        $final["CardRegOrgan"] = $data[$key]["CardRegOrgan"];
        $final["SourcePersID"] = $data[$key]["SourcePersID"];
        $final["CardRegReason"] = $data[$key]["CardRegReason"];
        $final["InOut_address"] = empty($data[$key]["InOut_address"]) ? null : $data[$key]["InOut_address"];
        //$final["InOut_address"]["Born_address"] = array_values([$data[$key]["Person"]["Born_address"]]);
        //print_r($sort_array);
        //print_r($data[$key]);

        // print_r( json_encode( new jsontest($final), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) );
        // echo "\t\t\n \e[91m^^^^^^^^^^^^^^^^^^^(".$key.")^^^^^^^^^^^^^^^^^^\e[39m\n";
    }
    //$SourcePersID = !empty(trim($data[$key]["SourcePersID"])) ? "'" . $data[$key]["SourcePersID"] . "'" : 'null';
    $insert_sql = "INSERT INTO temp.rtg_test
                                ( data, 
                                    citizen_id, 
                                    r_id, 
                                    date_oper 
                                    ) VALUES(
                                    '" . pg_escape_string(json_encode(new jsontest($final), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "',
                                    '{$data[$key]["SourcePersID"]}', 
                                    '{$data[$key]["r_id"]}', 
                                    '{$data[$key]["date_oper"]}' 
                                )
                        ";
    // print_r(
    //     //pg_escape_string(json_encode(new jsontest($final), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
    //     //$data[$key]["InOut_address"]
    //     $insert_sql
    // );
    $result_final = $db->query($insert_sql);
    // $result_final = "qwe";
    if (!$result_final) {
        print_r("\e[41m.\e[49m");
        $cc++;
    } else {
        $cb++;
        print_r("\e[44m.\e[49m");
    }

    $stringCount++;
    if ($stringCount >= 100) {
        echo "\n";
        $stringCount = 0;
    }
}

echo "\nEnd foreach >>> " . date('Y-m-d H:i:s P') . "\n";

print_r("\nInsert in DB => " . $cb . "\n");
print_r("Error inserting in DB => " . $cc . "\n");
