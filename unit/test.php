<?php
require "compressLib.php";

class phpCompressTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->ck = array(
            2 // php 部分 1:change varible in all file  3:only function  2:only remove comment
            ,1
            ,0
            ,0
            ,0
            ,0
            ,1 //用空白打亂
        );

    }

    public function testCompressFileVariable() 
    {
        $ck = array(
            1
            ,1
            ,0
            ,0
            ,0
            ,0
            ,1 //用空白打亂
        );

        $str = <<<PHP
        <?php
        \$ab = 10;
        echo \$ab;

PHP;

        $phpCom=new php_compress();
        $phpCom->setting($ck);

        $data = $phpCom->compress($str);
        //print_r($data);
        if (preg_match('/\$_c0/', $data)) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);

        }
    }

    public function testRemovePerf() 
    {
        $ck = $this->ck;
        $str = <<<PHP
        <?php
        \$a = 10;
           perfUtil::rm("title");

        echo \$a;
           perfUtil::rm("title");

PHP;

        $phpCom=new php_compress();
        $phpCom->setting($ck);

        $data = $phpCom->compress($str);
        //print_r($data);
        if (preg_match('/perf/', $data)) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);

        }
    }

}



