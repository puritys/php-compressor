PHP Compresspor
===============

This tool was developed in 2009 and published in 2015

If you have any problem to use this tool. Please open a issue and I will solve it as soon as possible.  


How to use this tool
--------------------

* sudo php phpcompress.php input.php output.php

<br />

Give you a example. If I have a PHP file which has the following script.

<pre>
$id = 1;
$sorting = "DESC";

$userDb = new userDb();
$userDb->getDataFromDb($id, $sorting);                                                                                                              

class userDb {

    function getDataFromDb($id, $sorting) {

        $SQL = "select * from users where user_id=:id order by :sorting;";

        $pdo = new PDO ("mysql:dbname=test;host=localhost;port=3306", '', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
    
        $sth = $pdo->prepare('select  * from table where id =:id and title= :title ');
    
        $sth->bindValue(':id', $id, PDO::PARAM_INT);
    
        $sth->bindValue(':sorting', $sorting, PDO::PARAM_STR);
    
        $sth->execute();
        return $sth->fetch(PDO::FETCH_ASSOC);
    }   


}
</pre>

Then I execute this compress tool.

The result will be like the following script.

<pre>
$_c1 = 1;$_c2 = "DESC"; $_c6 = new userDb();$_c6 ->getDataFromDb($_c1 , $_c2 ); class userDb  { function getDataFromDb($_a1 , $_a2 ) { $_b3 = "select * from users where user_id=:id order by :sorting;";   $_b4 = new PDO ("mysql:dbname=test;host=localhost;port=3306", '', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND  => 'SET NAMES \'UTF8\'');     $_b5 = $_b4 ->prepare('select  * from table where id =:id and title= :title ');   $_b5 ->bindValue(':id', $_a1 , PDO::PARAM_INT );   $_b5 ->bindValue(':sorting', $_a2 , PDO::PARAM_STR );   $_b5 ->execute();   return $_b5 ->fetch(PDO::FETCH_ASSOC ); }}
</pre>


Setting about PHP Compressor
----------------------------

The first parameter in php_compress class.

1. Compress every thing in PHP file.
2. Only compress variable in PHP function.
3. Only remove comments




What PHP syntax was not supported?
================================

1. global variable
2. <<<TEXT

