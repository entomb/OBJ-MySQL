<?php
    include("bin/OBJ_mysql.php");
    
    //database configuration
    $config = array(
        'host' => 'localhost',
        'user' => 'root',
        'pass' => 'pass',
        'database' => 'testdb',
    );

    $db = new OBJ_myqsl($config);
    $db->query("SHOW TABLES");

    //prints current query
    echo $db;


?>
