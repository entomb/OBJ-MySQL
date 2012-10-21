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
	 * Defines the default result type for methods like fetch() and fetchAll();
	 * basicly, I want to know if you prefer mysqli_fetch_object or mysqli_fetch_assoc.
	 *
	 * @var string object|array
	 * 
	 */
    var $_default_result_type = "object"; 

    /**
     * the MySQLi result object is stored here.
     */
    private $result;


    /**
     * the number of rows in the current result are loaded on the __construct
     */
    var $num_rows;



    function OBJ_mysql_result($result = null){
        if($result===null) return false;
        
        $this->result = $result;
        $this->num_rows = $this->result->num_rows;
    }

    function fetch(){
    	if($this->_default_result_type=='object'){
    		return $this->fetchObject();
    	}
    	if($this->_default_result_type=='array'){
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
    	if($this->_default_result_type=='object'){
    		return $this->fetchAllObject();
    	}
    	if($this->_default_result_type=='array'){
    		return $this->fetchAllArray();
    	}
    }

    function fetchAllObject(){
    	$Data = array();
        while($row = mysqli_fetch_object($this->result)){
            $Data[] = $row;
        }
        return $Data;
    }

    function fetchAllArray(){
    	$Data = array();
        while($row = mysqli_fetch_assoc($this->result)){
            $Data[] = $row;
        }
        return $Data;
    }

    //helper functions
    function is_empty(){
        return ($this->result->num_rows>0) ? true : false;
    }

    

    function free(){
    	mysqli_free_result($this->result);
    }

    /**
     * This will make sure that the result is set free when the variable is unset() 
     * it also works when it falls under garbage colecting
     * 
    */
    function __destruct(){
    	return $this->free(); 
    }


    //aliases
    function getAll(){ return $this->fetchAll(); }
    function getObject(){ return $this->fetchAllObject(); }
    function getArray(){ return $this->fetchAllArray(); }

}
