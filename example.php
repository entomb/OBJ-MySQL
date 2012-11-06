<?php
/*
 * Copyright (C) 2012  Jonathan Tavares <the.entomb@gmail.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
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
