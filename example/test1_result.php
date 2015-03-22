<?php
 

 $_c1 = 1;
  $_c2 = "DESC";
  
$_c6 = new userDb();
$_c6 ->getDataFromDb($_c1 , $_c2 );

class userDb  {
 
   function getDataFromDb($_a1 , $_a2 ) {

  $_b3 = "select * from users where user_id=:id order by :sorting;";
 
   $_b4 = new PDO ("mysql:dbname=test;host=localhost;port=3306", '', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND  => 'SET NAMES \'UTF8\'');
      
       $_b5 = $_b4 ->prepare('select  * from table where id =:id and title= :title ');
   
    $_b5 ->bindValue(':id', $_a1 , PDO::PARAM_INT );
   
   $_b5 ->bindValue(':sorting', $_a2 , PDO::PARAM_STR );
 
     $_b5 ->execute();
    return $_b5 ->fetch(PDO::FETCH_ASSOC );
  }
 
  
}
  
  