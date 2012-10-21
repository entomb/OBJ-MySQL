<?php
/**
 * OBJ-mysql
 * Database Abstraction Class
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 *
 *
*/


/**
 * OBJ-mysql Result class
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 *
 *
*/
Class OBJ_mysql_result{
    protected $result;

    function OBJ_mysql_result_test($result = null){
        if($result===null){
            return false;
        }
        $this->result = $result;
        $this->num_rows = $this->result->num_rows;
    }

    function getAll(){
        $DataAssoc = array();
        while($row = mysqli_fetch_assoc($this->result)){
            $DataAssoc[] = $row;
        }

        return $DataAssoc;
    }

    function empty(){
        return ($this->result->num_rows>0) ? true : false;
    }
}
