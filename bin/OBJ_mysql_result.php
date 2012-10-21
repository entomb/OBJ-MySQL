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
    
	/**
	 * Defines the default result type for methods like fetch() and get();
	 * @param string object|array
	 */
    var $_default_result_type = "object"; 

    /**
     * the MySQLi result object is stored here.
     */
    private $result;


    /**
     * the number of rows in the current result.
     */
    var $num_rows;



    function OBJ_mysql_result($result = null){
        if($result===null) return false;
        
        $this->result = $result;
        $this->num_rows = $this->result->num_rows;
    }

    function fetch(){
    	if($_default_result_type=='object'){
    		return $this->fetchObject();
    	}
    	if($_default_result_type=='array'){
    		return $this->fetchArray();
    	}
    }

    function fetchObject(){
        return ($row = mysqli_fetch_object($this->result)) ? $row : false;
    }

    function fetchArray(){
    	return ($row = mysqli_fetch_assoc($this->result)) ? $row : false;   
    }

    function fetchAll(){
    	if($_default_result_type=='object'){
    		return $this->fetchAllObject();
    	}
    	if($_default_result_type=='array'){
    		return $this->fetchAllArray();
    	}
    }

    function fetchAllObject(){
    	$DataAssoc = array();
        while($row = mysqli_fetch_assoc($this->result)){
            $DataAssoc[] = $row;
        }
        return $DataAssoc;
    }

    function fetchAllArray(){
    	$DataObject = array();
        while($row = mysqli_fetch_object($this->result)){
            $DataObject[] = $row;
        }
        return $DataObject;
    }

    //helper functions
    function is_empty(){
        return ($this->result->num_rows>0) ? true : false;
    }

    function __destroy(){

    	mysqli_free($this->result);
    }


    //aliases
    function getAll(){ return $this->fetchAll(); }

}
