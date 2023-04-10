<?php
header('charset=UTF-8');

use function PHPUnit\Framework\isNull;

require_once('/home/.../db.class.php');
require_once('/home/.../processedDms.class.php');


$db = new \DBext("-", "-", "-", "-");
$sql = "SELECT requestid FROM \"temp\".inf_msg_receiver";

class ConsoleQuestion
{

    public static function readline()
    {
        return rtrim(fgets(STDIN));
    }
}

// echo "\e[33mHello: \e[31m";
// $con = ConsoleQuestion::readline();
// echo "\e[95m" . $con . "\n";
// exit();
$in_data = $db->query($sql);

$sql = "SELECT requestid_dms FROM public.dms_receiver";

$out_data = $db->query($sql);

array_walk(
    $out_data,
    function (&$item, $key) {
        return $item = $item['requestid_dms'];
    }
);
//file_put_contents('/home/.../camel_diff.json', json_encode($out_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//print_r($out_data);
$camel = [];
$cc = 0;

echo "From IN to OUT difference:\n";
$stringCount = 0;
foreach ($in_data as $key => $value) {
    if (!in_array((int) $value["requestid"], $out_data)) {
        $camel[] = $value["requestid"];
        // print_r("\n");
        // print_r(!array_search((int) $value["requestid"], $out_data, 'int'));
        // print_r("\n");
        print_r("\e[41m.\e[49m");
        $cc++;
    } else {
        $cc++;
        print_r("\e[44m.\e[49m");
    }
    $stringCount++;
    if ($stringCount >= 100) {
        echo "\n";
        $stringCount = 0;
    }
}
echo "\n Total IN: " . count($in_data);
echo "\n Total OUT: " . count($out_data);
echo "\n Total: " . $cc;


// $camel = [];
// $cc = 0;

// echo "From IN to OUT difference:\n";
// array_walk(
//     $in_data,
//     function (&$item, $key) {
//         return $item = $item['requestid'];
//     }
// );

// foreach ($out_data as $key => $value) {
//     if (!array_search($value, $in_data)) {
//         $camel[] = $value;
//         //print_r($value["requestid"]);
//         print_r("\e[41m.\e[49m");
//         $cc++;
//     } else {
//         $cc++;
//         print_r("\e[44m.\e[49m");
//     }
// }
// echo "\n Total IN: " . count($in_data);
// echo "\n Total OUT: " . count($out_data);
// echo "\n Total: " . $cc;




function question($camel = ["Emty" => "No diff data found."])
{
    echo "\n\e[96m";
    //print_r($camel);
    echo preg_replace(
        '/(^Array|^\\(\n|^\\)\n|^\s*)/m',
        '',
        print_r($camel, true)
    );
    echo "\e[94m";
}

while (true) {
    //question($camel);
    print_r("\nFound \e[30m\e[43m>" . count($camel) . "<\e[27m\e[49m\e[39m difference\n");
    print_r("Show or add?:\n 
    - 1 SHOW\n
    - 2 ADD \n
    Type # : ");
    $num = ConsoleQuestion::readline();
    if ($num == 1) {
        while (true) {
            question($camel);
            echo "For EXIT type \"x\".\nShow:";
            $pnum  = strtolower(ConsoleQuestion::readline());
            if ($pnum == "x") {
                echo "\e[27m\e[39m";
                break;
            }
            $pnum  = intval($pnum);
            if (array_key_exists($pnum, $camel) and ctype_digit(strval($pnum))) {
                print_r("\e[7m" . $camel[$pnum] . "\e[39m\e[49m\e[27m\n"); //\e[30m\e[42m
                $sql = "SELECT jdata FROM \"temp\".inf_msg_receiver where requestid = '" . $camel[$pnum] . "'";
                $r = $db->query($sql)[0];
                $r = json_encode(json_decode($r["jdata"], false), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $rand = rand(32, 37);
                print_r("\e[{$rand}m\e[7m" . $r . "\e[27m\n");

                //print_r("\e[" . $rand . "\e[7m" . $r . "\e[27m\n");
            } else {
                print_r("\t\t\t\e[31mValue with key \e[39m\e[41m>" . $num . "<\e[49m \e[31mis unavaible\e[39m\n");
                break;
            }
        }
    } elseif ($num == 2) {
        // $sql = "SELECT s_id, id, \"data\", created
        //                 FROM public.dms_receiver 
        //                 --where \"status\" ='new'
        //                 --AND processed=false
        //                 where s_id > 754
        //                 --where original_address like '%null%'
        //                 ORDER by s_id asc
        //                 ;";
        // $nor = new \pracessedDms();
        // //$nor->updateById(10);die;
        // try {
        //     $select_imr = $db2->query($sql);
        // } catch (Exception $e) {
        //     //error_log(date('Y-m-d H:m:s P')  . "[\n" . $e . "\n] DB insert Error=" . $e . "\n\n", 3, "/home/.../processed.log");
        //     print_r("\n\n" . $e . "\n\n");
        //     die;
        // }
        // foreach ($select_imr as $key => $value) {
        //     print_r($value["s_id"] . "\n\n");
        //     $nor->updateById($value["s_id"]);
        // }
        while (true) {
            question($camel);
            echo "For ADD all type \"a\".\n";
            echo "For EXIT type \"x\".\n\e[39mShow:";
            $pnum  = strtolower(ConsoleQuestion::readline());
            if ($pnum == "x") {
                echo "\e[27m\e[39m";
                break;
            }
            if ($pnum == "a") {
                $lastRangeToStr = '';
                foreach ($camel as $key => $value) {
                    if ($key == 0) {
                        $lastRangeToStr = "'" . $value . "'";
                    } else {
                        $lastRangeToStr .= ", '" . $value . "'";
                    }
                }

                $sql = "SELECT requestdata FROM \"temp\".inf_msg_receiver where requestid IN (" . $lastRangeToStr . ")";
                $r = $db->query($sql);
                foreach ($r as $key => $value) {
                    // try {
                    $nor = new pracessedDms(pg_escape_string(base64_decode($value)));
                    // } catch (Exception $e) {
                    //     error_log(date('Y-m-d H:m:s P')  . "[" . $requestID . "] Creating parser Error=" . $e . "\n\n", 3, "/home/.../processed.log");
                    //     //throw new SoapFault("510", "Creating parser Error.");
                    // }

                    // try {
                    $nor->NormalaiseData();
                    // } catch (Exception $e) {
                    //     error_log(date('Y-m-d H:m:s P')  . "[" . $requestID . "] Parsing DATA Error=\n" . $e . "\n\n", 3, "/home/.../processed.log");
                    //     //throw new SoapFault("511", "Parsing DATA Error.\n");
                    // }

                    // try {
                    $nor->dbInput();
                    // } catch (Exception $e) {
                    //     error_log(date('Y-m-d H:m:s P')  . "[" . $requestID . "] DB insert Error=" . $e . "\n\n", 3, "/home/.../InfMsgReceiver.log");
                    //     //throw new SoapFault("512", "Error inserting parse data in DB.\n");
                    // }
                }




                count($r);
                echo count($r) . "\e[27m\e[39m";
                die;
            }
            $pnum  = intval($pnum);
            if (array_key_exists($pnum, $camel) and ctype_digit(strval($pnum))) {
                print_r("\e[7m" . $camel[$pnum] . "\e[39m\e[49m\e[27m\n"); //\e[30m\e[42m
                $sql = "SELECT requestdata FROM \"temp\".inf_msg_receiver where requestid = '" . $camel[$pnum] . "'";
                $r = $db->query($sql)[0];
                $nor = new pracessedDms(pg_escape_string(base64_decode($r["requestdata"])));
                print_r($nor->NormalaiseData(null,$camel[$pnum]));
                echo "\n\n";
                $nor->dbInput();
                //print_r("\e[" . $rand . "\e[7m" . $r . "\e[27m\n");
            } else {
                print_r("\t\t\t\e[31mValue with key \e[39m\e[41m>" . $num . "<\e[49m \e[31mis unavaible\e[39m\n");
                break;
            }
        }
    }
}
