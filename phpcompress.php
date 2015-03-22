<?php
require 'compressLib.php';

if ( $_SERVER['argc'] < 3) {
    echo  "Example: php phpcompress.php input output type";
    exit(1);
}

$file = $_SERVER['argv'][1];
$outputfile = $_SERVER['argv'][2];

$type = 2;

if (isset($_SERVER['argv'][3])) {
    switch ($_SERVER['argv'][3]){
        case "c1":
            $type = 2;
            break;
    }
}

if (!is_file($file)) {
    echo "php compress , file is not exist.";
    exit(1);
}
$r = file_get_contents($file);

//echo "input = ".$file."\n";
//echo "output = ".$outputfile."\n";
$ck=array(
    1 
    ,0
    ,1
    ,1
    ,1
    ,1
    ,0 //用空白打亂
);
$phpCom=new php_compress();
$phpCom->setting($ck);
$data=$phpCom->compress($r);
file_put_contents($outputfile,$data);

