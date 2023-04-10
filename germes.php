<?php
require_once('/home/.../db.class.php');


function constructDmsRegistrationType($registerRecord)
{
    if ($registerRecord->registrationStatus === 'проживає') {
        return ($statement->reverseOperation) ? 12 : 10;
    } else if ($registerRecord->registrationStatus === 'вибув з проживання') {
        return ($statement->reverseOperation) ? 12 : 30;
    } else if ($registerRecord->registrationStatus === 'перебуває') {
        return 11;
    }
}

$mapDoc = array(
    "ПГУ-1993" => 'ПГУ',
    "ПГУ-2015" => 'ІД',
    "СВ" => 'СВ',
    "ТПГУ" => 'ТПГУ',
    "ППП" => 'ППП',
    "ПТП" => 'ПТП',
    "ПБ" => 'ПБ',
    "ПДЗ" => 'ПДЗ',
    "ПТЗ" => 'ПТЗ'
);


$DmsRegistrationReason = array(
    "заява громадянина" => 1, 
    "заява законного представника" => 2, 
    "заява представника за довіреністю" => 3, 
    "дані про новонароджену дитину з органу соціального захисту" => 4, 
    "рішення суду про позбавлення права власності або користування" => 6, 
    "рішення суду про виселення" => 7, 
    "рішення суду про зняття з реєстрації" => 8, 
    "рішення суду про визнання безвісно відсутньою або померлою" => 9, 
    "документ про підтвердження смерті" => 10, 
    "повідомлення від дмс" => 11, 
    "закінчення строку дії посвідки на тимчасове проживання" => 12, 
    "скасування посвідки на тимчасове проживання" => 12, 
    "скасування дозволу на імміграцію" => 12, 
    "скасування посвідки на постійне проживання в україні" => 12, 
    "документ спецсоцустанови" => 13, 
    "документ про припинення права проживання" => 14, 
    "документ про припинення права користування" => 14, 
    "повідомлення про зняття з реєстрації місця проживання" => 15, 
    "первинне введення даних" => 20, 
    "документ військової частини" => 1, 
    "рішення суду" => 6
);

$RegistrationCancelReasons = array(
    "рішення суду" => 1, 
    "рішення органів опіки/піклування" => 2, 
    "відсутність законних підстав" => 3, 
    "невідповідність даних" => 4, 
    "технічна помилка" => 5
);


// $values = array("decisionOfCourt" => 1, "decisionOfGuardianshipAuthorities" => 2, "lackOfLegalGround" => 3, "dataMismatch" => 4, "technicalError" => 5);

    // $values = array(
    //     "personStatement" => 1, 
    //     "representativeStatement" => 2, 
    //     "representativeStatementWithPowerOfAttorney" => 3, 
    //     "newbornChildDataBySocialProtectionBody" => 4, 
    //     "registrationBefore20160404" => 5, 
    //     "deprivationOfOwnershipByCourt" => 6, 
    //     "evictionByCourt" => 7, 
    //     "deregistrationByCourt" => 8, 
    //     "declarationMissingOrDeadByCourt" => 9, 
    //     "deathCertificate" => 10, 
    //     "deahtCertificateByDms" => 11, 
    //     "illegalForeigner" => 12, 
    //     "noPermissionForHomeless" => 13, 
    //     "noPermissionToUseResidence" => 14, 
    //     "deregistrationNotification" => 15, 
    //     "deregistrationBefore20160404" => 16, 
    //     "cvk" => 20
    // );



    // registerPermanentResidence: 10,
    // registerTemporaryResidence: 11,
    // registerPermanentResidenceWithUnregistration: 12,
    // clarifyPersonalData: 13,
    // unregisterPermanentResidence: 30,
    // permanentResiding: 'проживає',
    // temporaryResiding: 'перебуває',
    // unregistered: 'вибув з проживання',
    // movedOut: 'вибув з перебування'