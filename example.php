<?php
    include("bin/OBJ_mysql.php");
    
    //database configuration
    $config = array(
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'test_db',
    );

echo ini_get("mysqli.default_port");
    //creating a new MySQL Connection
    $db = new OBJ_mysql($config);
    $query = $db->query("show tables");
    var_dump($db);
    
    
    var_dump($query->num_rows);
    var_dump($query->getAll());
    var_dump($query);



?>
