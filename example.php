<?php
    include("bin/OBJ_mysql.php");
    
    //database configuration
    $config = array(
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'test',
    );

    //creating a new MySQL Connection
    $db = new OBJ_mysql($config);
    $query = $db->query("show tables"); 
    var_dump($query); 

    $query = $db->query("SELECT * FROM client LIMIT 10 "); 
    $array = $query->fetchAll(); 
    $array2 = $query->fetchColumn('telefone');
    $array3 = $query->fetchArrayPair('id_client','usern');

    var_dump($array); 
    var_dump($array2); 
    var_dump($array3); 
    

?>
