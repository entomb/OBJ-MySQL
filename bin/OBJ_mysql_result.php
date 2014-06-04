<?php
/**
 * OBJ-mysql - Database Abstraction Class
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 * @license GNU General Public License, version 3
 * @link https://github.com/entomb/OBJ-MySQL GitHub Source
 * @filesource
 *
 *
*/

/**
 * OBJ-mysql - Database Result Class
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 * @license GNU General Public License, version 3
 * @link https://github.com/entomb/OBJ-MySQL GitHub Source
 *
 *
*/

Class OBJ_mysql_result{

	/**
     * Default result type
     *
	 * Defines the default result type for methods like fetch() and fetchAll();
	 * basicly, I want to know if you prefer mysqli_fetch_object or mysqli_fetch_assoc.
	 *
	 * @var string object|array|json
	 * @access public
	*/
    var $_default_result_type = "object";

    /**
     * the MySQLi result object is stored here.
     * @access private
    */
    private $result;

    /**
     * the number of rows in the current result are loaded on the __construct
     * @access public
    */
    var $num_rows;

    /**
     * OBJ_mysql_result Construnctor
    */
    function OBJ_mysql_result($sql="", $result = null){
        if($result===null){
            return false;
        }

        $this->sql = $sql;
        $this->result = $result;
        $this->num_rows = $this->result->num_rows;
    }

    /**
     * Fetches next row depending on the value of $_default_result_type
     *
     * @see OBJ_mysql_result::$_default_result_type
     * @see OBJ_mysql_result::fetchObject()
     * @see OBJ_mysql_result::fetchArray()
    */
    function fetch(){
    	if($this->_default_result_type=='object'){
    		return $this->fetchObject();
    	}
        if($this->_default_result_type=='array'){
            return $this->fetchArray();
        }
    }

    /**
     * Fetches next row as an object using mysqli_fetch_object
    */
    function fetchObject(){
        return ($row = mysqli_fetch_object($this->result)) ? $row : false;
    }

    /**
     * Fetches next row as an array using mysqli_fetch_assoc
    */
    function fetchArray(){
        return ($row = mysqli_fetch_assoc($this->result)) ? $row : false;
    }

    /**
     * Fetches all rows depending on the value of $_default_result_type
    */
    function fetchAll(){
    	if($this->_default_result_type=='object'){
    		return $this->fetchAllObject();
    	}
        if($this->_default_result_type=='array'){
            return $this->fetchAllArray();
        }
    }

    /**
     * Fetches all rows as an array using mysqli_fetch_object
    */
    function fetchAllObject(){
        $Data = array();

        if( !$this->is_empty() ){
            $this->reset();
            while($row = mysqli_fetch_object($this->result)){
                $Data[] = $row;
            }
        }
        return $Data;
    }

    /**
     * Fetches all rows as an array using mysqli_fetch_assoc
    */
    function fetchAllArray(){
    	$Data = array();
        if( !$this->is_empty() ){
            $this->reset();
            while($row = mysqli_fetch_assoc($this->result)){
                $Data[] = $row;
            }
        }
        return $Data;
    }

    /**
     * Fetch Column
     *
     * Fetches data from a single column in the result set.
     * Will only return NOT NULL values
    */
    function fetchColumn($column=""){
        $ColumnData = array();
        $Data = $this->fetchAllArray();
        foreach($Data as $_index => $_row){
            if(isset($_row[$column])){
                 $ColumnData[] = $_row[$column];
            }
        }
        return $ColumnData;
    }

    /**
     * Fetch Array Pair
     *
     * Fetches data from two columns and compiles them into an array pair
     * Will only return NOT NULL values
     *
     * @param string $key the column to use as a key
     * @param string $value the column to use as value
     * @example:
     *
     *      $this->fetchArrayPair('id_user','username');
     *
     *      Array
     *            (
     *               [1] => admin
     *               [5] => testUser
     *               [10] => johnsmith
     *               [11] => smith79
     *               [12] => xxuser_name
     *            )
     *
    */
    function fetchArrayPair($key,$value){
        $ArrayPair = array();
        $Data = $this->fetchAllArray();
        foreach($Data as $_index => $_row){
            if(isset($_row[$key]) && isset($_row[$value]) ){
                $_key = $_row[$key];
                $_value = $_row[$value];
                $ArrayPair[$_key] = $_value;
            }
        }
        return $ArrayPair;

    }


    /**
     * Checks if the result is empty
    */
    function is_empty(){
        return ($this->num_rows>0) ? false : true;
    }

    /**
     * Resets the pointer for data seeking
    */
    function reset(){
        if( !$this->is_empty() ){
            mysqli_data_seek($this->result,0);
        }
        return $this;
    }

    /**
     * Fetches all of the rows in the a json format string
    */
    function json(){
        $Data = $this->fetchAllArray();
        return json_encode($Data);
    }

    /**
     * Free's the data in the result
    */
    function free(){
    	mysqli_free_result($this->result);
    }

    /**
     * __destruct magic method
     *
     * This will make sure that the result is set free when the variable is unset()
     * it also works when it falls under garbage colecting
     *
    */
    function __destruct(){
    	$this->free();
        return;
    }

    //aliases for people used to the "get" syntax

    /**
     * Alias for fetch()
     * @see OBJ_mysql_result::fetch()
    */
    function get(){
        return $this->fetch();
    }

    /**
     * Alias for fetchAll()
     * @see OBJ_mysql_result::fetchAll()
    */
    function getAll(){
        return $this->fetchAll();
    }

    /**
     * Alias for fetchAllObject()
     * @see OBJ_mysql_result::fetchAllObject()
    */
    function getObject(){
        return $this->fetchAllObject();
    }

    /**
     * Alias for fetchAllArray()
     * @see OBJ_mysql_result::fetchAllArray()
    */
    function getArray(){
        return $this->fetchAllArray();
    }

    /**
     * Alias for fetchColumn()
     * @see OBJ_mysql_result::fetchColumn()
    */
    function getColumn($key){
        return $this->fetchColumn($key);
    }
}
