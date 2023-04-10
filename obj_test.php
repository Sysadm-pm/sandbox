<?php
// class Foo {
//     public $var1;
//     public $var2;
//     public function Func($t){
//         $this->var1 = $t;
//     }
// }

// class Bar {
//     public $var1;
//     public $var2;
//     public function __construct($t2)
//     {
//         $this->var1 = $t2->var1;
//         print_r($this->var1);
//         echo "\n";   
//     }
// }

$searchType = [
    "TypeService" => "0",
    "TypeService0" => 0,
    "TypeService1" => 0,
    "TypeService2" => 0,
    "TypeService3" => 0,
    "TypeService4" => 0,
    "TypeService5" => 0, 
    "TypeService6" => 0,
    "TypeService7" =>  "0",
    "TypeService8" =>  "0", 
    "TypeService9" =>  0,
    "TypeService10" => 1,
    "TypeService11" => "asdas",
    "TypeService12" => 0,
    "TypeService13" => 0,
    "TypeService14" => 0,
    "TypeService15" => 0,
    "TypeService16" => 0,
    "TypeService17" => 0,
    "TypeService18" => 0,
    "TypeService19" => 0,
    "TypeService20" => 0,
    "TypeServasfdicae20" => 1,
    "asdasd" => 1,
];
$filtered = array_filter($searchType, function($v) use($searchType){
    return preg_match('#TypeService\d#', $v) && (int)$searchType[$v] == 1 ;
  }, ARRAY_FILTER_USE_KEY);
  if(count($filtered)>1){
    print_r($filtered);
  }elseif(count($filtered)<1){
    echo "We have a problem!!!\n";
    print_r($filtered);
  }else{
      echo "Yeap!\n";
    print_r($filtered);
  }

die;
// $first = new Foo;
// $first->Func("HHello WORLD!");
// echo $first->var1."\n\n";
// $second = new Bar($first);