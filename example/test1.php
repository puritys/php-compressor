<?php


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

