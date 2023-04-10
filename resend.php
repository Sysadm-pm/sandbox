<?php
//require_once('./autoload.php');
require_once('/home/.../db.class.php');

const RTG_DB_HOST = "-";
const RTG_DB_USER = "-";
const RTG_DB_PASSWORD = "-";
const RTG_DB_NAME = "-";
const PACH = "/home/.../!test_scripts/";
const _10IN_FILE = "10-in.json";
const _13IN_FILE = "13-in.json";
const _30IN_FILE = "30-in.json";
const _10OUT_FILE = "10-out.json";
const _13OUT_FILE = "13-out.json";
const _30OUT_FILE = "30-out.json";



$db = new \DBext(RTG_DB_HOST,  RTG_DB_USER, RTG_DB_PASSWORD, RTG_DB_NAME);
// $sql_select = "
// SELECT
// *
// FROM t n
// where 
// n.\"locked\" = false
// and 
// --n.received_json::JSON->'Person'->>'PersINN' = '1111111111'
// n.is_clarify_person is null
// and 
// n.created >= '2022-11-24'
// --and 
// --n.created < '2022-12-26'
// and
// n.response_status = 'REJECTED'
// --n.id = ''
// ";
$sql_select = '
select 
distinct on (register_record_id)
register_record_id
--,count(register_record_id)
--,is_clarify_person 
,received_json::JSON->\'CardType\' "CardType"
--,received_json 
--,*
--,count(\'register_record_id\') id_count 
from public.trembita_dms_notifications  
where 
created >= (\'2022-11-01 00:00:00\')
and created <= (\'2022-12-24 23:59:00\')
--and id_count > 1 
and "locked" = false 
and received_json::JSON->>\'CardType\' is not null
and received_json::JSON->>\'CardType\' = \'10\'
--and "register_record_id" = 173416 
group by id,register_record_id 
order by 1
';
// $result = $db->query($sql_select);
// $in = $result;
//print_r(count($result));
echo "\n";

function get_json($file){
    $json_object = file_get_contents(PACH . $file);
    return json_decode($json_object, true);
}

function save_file($file, $data){
    $fp = fopen(PACH.$file, 'w');
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fclose($fp);
    return "ok";
}

function processing($count, $type = 10, $active = false){
    if( $type !== 10 && $type !== 13 && $type !== 30 ){
        return false;
    }else{
        switch ($type) {
            case 10:
                    $name_in =  _10IN_FILE;
                    $name_out =  _10OUT_FILE;
                break;
            case 13:
                    $name_in =  _13IN_FILE;
                    $name_out =  _13OUT_FILE;
                break;
            case 30:
                    $name_in =  _30IN_FILE;
                    $name_out =  _30OUT_FILE;
                break;
                    
            default:
                    return false;
                break;
        }
    }

    $in = get_json( $name_in );
    $out = get_json(  $name_out );
    $out_c = array_slice($in, 0, $count, true);
    if(!is_array($out)){$out = [];}
    array_push($out, ...$out_c);
    $in = array_diff_assoc($in, $out_c);
    save_file($name_in, $in);
    save_file($name_out, $out);
    return true;
}
if(!processing(1,30)){
    print_r("Error\n");
}else{
    print_r("Success\n");
}


//print_r($out);
//save_file(_10IN_FILE, $result);

// foreach ($result as $key => $value) {
//     $sql_insert = "
//     INSERT INTO public.t
//     (register_record_id, is_clarify_person )
//     VALUES
//     ({$value['register_record_id']}, {$value['is_clarify_person']});
//     ";
//     var_dump($sql_insert);
//         try {
//             //$result = $db->query($sql_insert);
//         } catch (\Throwable $th) {
//             //throw $th;
//             print_r($value['register_record_id']."\n");
//         }
//         //exit();
//     }
//print_r("All OK \n"); 
    
//end();
