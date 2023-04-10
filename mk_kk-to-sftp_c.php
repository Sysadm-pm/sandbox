<?php
//ver 1.2
require_once('/home/.../autoload.php');
require_once('/home/.../db.class.php');
//	Used packages:
// 	"telegram-bot/api": "^2.3",
// 	"aws/aws-sdk-php": "^3.235"

//	Used php extensions
// PDO,


const MKKK_DB_SERVER = "-";
const MKKK_DB_USER = "-";
const MKKK_DB_PASSWORD = "-";
const MKKK_DB_NAME = "-";

const MKKK_S3_KEY = "-";
const MKKK_S3_SECRET = "-";
const MKKK_S3_BUCKET = "-";
const MKKK_S3_BUCKET_KEY = "-";
const MKKK_S3_BUCKET_STORAGE_CLASS = "-";


$db = new \DBext(MKKK_DB_SERVER,  MKKK_DB_USER, MKKK_DB_PASSWORD, MKKK_DB_NAME);
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
	FROM public.persons p 
		JOIN public.p psc ON psc.person_id = p.id
		LEFT JOIN (
			SELECT
				 pbc.person_id
				,pr.code		benefit_product_code
			FROM public.pp pbc
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
			WHERE pbc.is_active
				and pbc.delete_date is NULL
		) t1 ON t1.person_id = p.id
		LEFT JOIN (
			SELECT
				 pbc.person_id
				,pr.code		benefit_product_code
			FROM public.pc pbc
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

try {
	$data = $db->query($sql);
} catch (\Throwable $th) {
	error_log(
		date('Y-m-d H:i:s') . " DB connection Error" . $th->getMessage() . "\n\n",
		3,
		"/home/.../mk_kk-to-sftp.log"
	);
}

$dataCsv = json_encode($data);

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Set Amazon s3 credentials
$client = S3Client::factory(
  [
    'version'		=> 'latest',
    'region'		=> 'eu-central-1',
    'credentials'	=> [
						'key'	=> MKKK_S3_KEY,
						'secret'=> MKKK_S3_SECRET
						]
	]
);

var_dump("Send START");
	error_log(
				date('Y-m-d H:i:s') . " S3 sending START" . "" . "\n",
				3,
				"/home/.../mk_kk-to-sftp.log"
			);

try {
	
	$client->putObject(array(
		'Bucket'		=>	MKKK_S3_BUCKET,
		'Key'			=>	MKKK_S3_BUCKET_KEY,
		'Body'			=>	$dataCsv,
		'StorageClass'	=>	MKKK_S3_BUCKET_STORAGE_CLASS
	));

} catch (S3Exception $e) {
	error_log(
		date('Y-m-d H:i:s') . " S3 sending ERROR\n" . $e->getMessage() . "\n\n",
		3,
		"/home/.../mk_kk-to-sftp.log"
	);
	die;
}

error_log(
	date('Y-m-d H:i:s') . " S3 sending OK\n" . "\n\n",
	3,
	"/home/.../mk_kk-to-sftp.log"
);

var_dump("OK\r");