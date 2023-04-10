<?php
require_once('/home/.../processedDms.class.php');

$data = "...";
$arrayData = base64_decode($data);
$jData = '{}';
$nor = new pracessedDms();

        //$nor->updateById();
        print_r($nor->getCombineData($jData,"11111111"));

        
        die;

        function tst($data = null)
        {
    $data = "...";
    $arrayData = json_decode(base64_decode($data), true);
    $cityToChekIn = isset($arrayData["NewRegistrationAddress"]["cbc_CityName"]) ? $arrayData["NewRegistrationAddress"]["cbc_CityName"] : null;
    $cityToChekOut = isset($arrayData["RegistrationAddress"]["cbc_CityName"]) ? $arrayData["RegistrationAddress"]["cbc_CityName"] : null;
    if ($arrayData["TypeService10"] == "1") {
        $statusToChek = "ER-10";
        $statusValue = "TypeService10";
    } elseif ($arrayData["TypeService11"] == "1") {
        $statusToChek = "ER-11";
        $statusValue = "TypeService11";
    } elseif ($arrayData["TypeService4"] == "1") {
        $statusToChek = "ER-4";
        $statusValue = "TypeService4";
    } else {
        echo "Wrong TypeService";
    }

    function str_contains($haystack = "", $needle = ""): bool
    {
        $haystack = mb_strtolower($haystack);
        $needle = mb_strtolower($needle);
        return '' === $needle || false !== strpos($haystack, $needle);
    }

    if ($cityToChekIn or $cityToChekOut) {

        if ($statusToChek == "ER-11") {
            if (str_contains($cityToChekOut, 'київ')) {
                return ["chek out" => $cityToChekOut, "Status" => $statusToChek];
            } else {

                return "In \"RegistrationAddress\"=>\"cbc_CityName\" wrong value for " . $statusValue . " : " . $cityToChekOut . "\n";
            }
        } else {
            if (str_contains($cityToChekIn, 'київ')) {
                return ["chek in" => $cityToChekIn, "Status" => $statusToChek];
            } else {
                return "In \"NewRegistrationAddress\"=>\"cbc_CityName\" wrong value for " . $statusValue . " : " . $cityToChekIn . "\n";
            }
        }
    } else {
        return [$cityToChekIn, $cityToChekOut, $statusToChek];
    }
}
//print_r(tst());
$a = (object)["qwe" => "qwe", 123, 321];
print_r($a->{'0'});