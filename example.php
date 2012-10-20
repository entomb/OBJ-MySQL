<?php
    include("bin/OBJ_mysql.php");
    
    //database configuration
    $config = array(
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'database' => 'test_db',
    );


    //creating a new MySQL Connection
    $db = new OBJ_mysql($config);
    $db->query("SHOW TABLES");

    //prints current query
    echo $db;

    //lets find some users
    $db->query("SELECT * FROM test_users");

    if($db->count==0){
        echo "table is empty";
    }else{
        echo "table is not empty";
    }

    
    $db->insert("test_users",array(
                                'name' => "Mr. Smith",
                                'age' => 12,
                                'login' => "smith",
                                'password' => "MD5('thesmith')",
                                'date_created' => "NOW()",
                            )
                        );

     $db->query("SELECT * FROM test_users");

    if($db->count==0){
        echo "table is empty";
    }else{
        echo "table is not empty";
        echo $db;
    }


?>
