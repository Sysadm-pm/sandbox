<?php
require_once('/home/.../vendor/autoload.php');
require_once('/home/.../db.class.php');
//Send file via sftp to server

const MKKK_DB_SERVER = "-";
const MKKK_DB_USER = "-";
const MKKK_DB_PASSWORD = "-";
const MKKK_DB_NAME = "-";

const MKKK_S3_KEY = "-";
const MKKK_S3_SECRET = "-";
const MKKK_S3_BUCKET = "-";
const MKKK_S3_BUCKET_KEY = "-";
const MKKK_S3_BUCKET_STORAGE_CLASS = "-";


// $strServer = "-";
// $strServerPort = -;
// $strServerUsername = "-";
// $strServerPassword = "-";

// $csv_filename = date("Ymd-Hi").".json";
// $csv_pach_filename = "/home/.../!test_scripts/".$csv_filename;


$db = new \DBext("-",  "-", "-", "-");
$sql = "WITH tmp_01 AS (
	SELECT
		 psc.hash										-- хэш банковской карты
		,psc.id						bank_card_id		-- uid банковской карты
		,psc.graphic_data_code		transport_number	-- транспортный номер
		,psc.card_number			kmm					-- социальный номер (Номер КММ)
		,psc.status_code			card_status_code	-- Статус картки
		--
		,p.tin						rnokpp				-- рнокпп (инн)
		,p.status_code				person_status_code	-- Статус облікового запису пільговика
		,p.area_type				person_area_type	-- Тип пільговика
		--
		,(CASE
			WHEN t1.benefit_product_code is NULL THEN false
			ELSE true
		  END)						code_1				-- 'Метро (Пільговики київські)'
		,(CASE
			WHEN t3.benefit_product_code is NULL THEN false
			ELSE true
		  END)						code_3				-- 'Метро (Пільговики державні)'
	FROM public.p p 
		JOIN public.person_smart_cards psc ON psc.person_id = p.id
		LEFT JOIN (
			SELECT
				 pbc.person_id
				,pr.code		benefit_product_code
			FROM public.pbc pbc
				JOIN person_privileges pp ON pp.person_beneficiary_category_id = pbc.id
											and pp.delete_date is NULL
											and pp.is_active
											and (pp.privilege_end_date is null or pp.privilege_end_date >= now())
				JOIN benefit_products bp ON bp.id = pp.benefit_product_id
											and bp.deleted_date is NULL
											and bp.is_active
											and (bp.duration_discount_to is null or bp.duration_discount_to  >= now())
				JOIN products pr ON pr.code = bp.product_code
									and pr.deleted_date is NULL
									and pr.is_active
									and pr.code = '1'
			WHERE pbc.is_activ
				and pbc.delete_date is NULL
		) t1 ON t1.person_id = p.id
		LEFT JOIN (
			SELECT
				 pbc.person_id
				,pr.code		benefit_product_code
			FROM public.pbc pbc
				JOIN person_privileges pp ON pp.person_beneficiary_category_id = pbc.id
											and pp.delete_date is NULL
											and pp.is_active
											and (pp.privilege_end_date is null or pp.privilege_end_date >= now())
				JOIN benefit_products bp ON bp.id = pp.benefit_product_id
											and bp.deleted_date is NULL
											and bp.is_active
											and (bp.duration_discount_to is null or bp.duration_discount_to  >= now())
				JOIN products pr ON pr.code = bp.product_code
									and pr.deleted_date is NULL
									and pr.is_active
									and pr.code = '3'
			WHERE pbc.is_active
				and pbc.delete_date is NULL
		) t3 ON t3.person_id = p.id
	WHERE p.status_code = 'active'
		and psc.status_code IN('5','8')
	ORDER BY 
		 p.tin
		,psc.id
	-- 826769 rows affected.
)
SELECT
		 t.hash					-- хэш банковской карты
		,t.bank_card_id			-- uid банковской карты
		,t.transport_number		-- транспортный номер
		,t.kmm					-- социальный номер (Номер КММ)
		,t.card_status_code		-- Статус картки
		,t.rnokpp				-- рнокпп (инн)
		,t.person_status_code	-- Статус облікового запису пільговика
		,t.person_area_type		-- Тип пільговика
		,t.code_1				-- 'Метро (Пільговики київські)'
		,t.code_3				-- 'Метро (Пільговики державні)'
FROM tmp_01 t
GROUP BY 
		 t.hash					-- хэш банковской карты
		,t.bank_card_id			-- uid банковской карты
		,t.transport_number		-- транспортный номер
		,t.kmm					-- социальный номер (Номер КММ)
		,t.card_status_code		-- Статус картки
		,t.rnokpp				-- рнокпп (инн)
		,t.person_status_code	-- Статус облікового запису пільговика
		,t.person_area_type		-- Тип пільговика
		,t.code_1				-- 'Метро (Пільговики київські)'
		,t.code_3				-- 'Метро (Пільговики державні)'";

//$dataCsv = $db->query("SELECT * FROM public.p LIMIT 1");
try {
	$data = $db->query($sql);
} catch (\Throwable $th) {
	error_log(
		date('Y-m-d H:i:s') . " DB connection Error" . $th->getMessage() . "\n\n",
		3,
		"/home/.../!test_scripts/mk_kk-to-sftp.log"
	);
}

// // To file
// $dataCsvP = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
// if (!$resFile = fopen($csv_pach_filename, 'a')) {
// 	echo "Не могу открыть файл ($csv_filename)\n";
// 	exit;
// }
// if (fwrite($resFile, $dataCsvP) === FALSE) {
// 	echo "Не могу произвести запись в файл ($csv_filename)\n";
// 	exit;
// }
// echo "Ура! Записали данные из БД в файл ($csv_filename)\n\n";

// fclose($resFile);
// die;
// // END fo file

$dataCsv = json_encode($data);

//connect to server
// try {
// 	$resConnection = ssh2_connect($strServer, $strServerPort);
// 	if(ssh2_auth_password($resConnection, $strServerUsername, $strServerPassword)){
// 		//Initialize SFTP subsystem
// 		echo "connected\n";
// 		//echo $csv_filename."\n";
	
// 		$tmpHandle = tmpfile();
// 		fwrite($tmpHandle, $dataCsv);
// 		$tmpFilename = stream_get_meta_data($tmpHandle)['uri'];
// 		$key = basename($tmpFilename);

// 		var_dump($key);
	
// 		die;
		
// 		echo "\n";
// 		ssh2_scp_send($resConnection, $tmpFilename, 'mkkk/'.$csv_filename, 0644);
// 		fclose($tmpHandle);
// 		echo "Sending complite!\n";
	
// 	}else{
// 		error_log(
// 			date('Y-m-d H:i:s') . " SFTP connection Error" . "" . "\n",
// 			3,
// 			"/home/.../!test_scripts/mk_kk-to-sftp.log"
// 		);
// 		echo "Unable to authenticate on server";
// 	}
	
// } catch (\Throwable $th) {
// 	error_log(
// 		date('Y-m-d H:i:s') . " SFTP connection Exception!" . $th->getMessage() . "\n\n",
// 		3,
// 		"/home/.../!test_scripts/mk_kk-to-sftp.log"
// 	);
// }

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Set Amazon s3 credentials
$client = S3Client::factory(
  [
    'version' => 'latest',
    'region'  => 'eu-central-1',
    'credentials' => [
        'key'    => "...",
    	'secret' => "..."
    ]
 ]
);

var_dump("Send START");
	error_log(
				date('Y-m-d H:i:s') . " S3 sending START" . "" . "\n",
				3,
				"/home/.../!test_scripts/mk_kk-to-sftp.log"
			);
try {
	$client->putObject(array(
		'Bucket'=>'-',
		'Key' =>  '-.json',
		'Body' => $dataCsv,
		'StorageClass' => 'REDUCED_REDUNDANCY'
	));
} catch (S3Exception $e) {
	//echo $e->getMessage();
	error_log(
		date('Y-m-d H:i:s') . " S3 sending ERROR\n" . $e->getMessage() . "\n\n",
		3,
		"/home/.../!test_scripts/mk_kk-to-sftp.log"
	);
	die;
}
error_log(
	date('Y-m-d H:i:s') . " S3 sending OK\n" . "\n\n",
	3,
	"/home/.../!test_scripts/mk_kk-to-sftp.log"
);
// fclose($tmpHandle);
var_dump("OK\r");