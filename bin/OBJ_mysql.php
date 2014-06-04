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

//load the result class file
include("OBJ_mysql_result.php");

/**
 * OBJ-mysql - Database Abstraction Class
 *
 *
 **  Config DATA:
 **
 **  $config["hostname"]  = "YOUR_HOST";
 **  $config["database"]  = "YOUR_DATABASE_NAME";
 **  $config["username"]  = "USER_NAME";
 **  $config["password"]  = "PASSWORD";
 **  $config["port"]      = "PORT";
 **  $config["charset"]    = "CHARSET";
 **  $config["exit_on_error"] = TRUE;
 **  $config["echo_on_error"] = TRUE;
 **  $config["allow_logging"] = "TRUE|FALSE"; //defaults to true
 *
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 * @license GNU General Public License, version 3
 * @link https://github.com/entomb/OBJ-MySQL GitHub Source
 *
*/
Class OBJ_mysql{

    // Default configuration variables
    private $hostname = "";
    private $username = "";
    private $password = "";
    private $database = "";
    private $port = "3306";
    private $charset = "utf8";

    protected $link;
    protected $LOG;
    protected $connected = false;
    private $_errors = array();
    private $allow_logging = true;

    var $query_count = 0;

    var $css_mysql_box_border = "3px solid orange";
    var $css_mysql_box_bg = "#FFCC66";
    var $exit_on_error = true;
    var $echo_on_error = true;

    /**
     * OBJ_mysql constructor
     *
     * This is the config array template:
     *
     **  $config["hostname"]  = "YOUR_HOST";
     **  $config["database"]  = "YOUR_DATABASE_NAME";
     **  $config["username"]  = "USER_NAME";
     **  $config["password"]  = "PASSWORD";
     *
     * non mandatory configurations
     *
     **  $config["port"]      = "PORT"; //defaults to 3306
     **  $config["charset"]    = "CHARSET"; //defaults to UTF8
     **  $config["exit_on_error"] = "TRUE|FALSE"; //defaults to true
     **  $config["echo_on_error"] = "TRUE|FALSE"; //defaults to true
     **  $config["allow_logging"] = "TRUE|FALSE"; //defaults to true
     *
     *
     * @param array $config config array
     *
     */
    function OBJ_mysql($config=null){
        $this->connected = false;
        $this->_loadConfig($config);
        if($this->connect()){
            $this->set_charset($this->charset);
        }
    }

    /**
     * Establishes the connection
     * @return boolean connection status
     */
    function connect(){
        if($this->is_ready()) return true;

        $this->link = @mysqli_connect(
                            $this->hostname,
                            $this->username,
                            $this->password,
                            $this->database,
                            $this->port
                        );

        if(!($this->link)){
            $this->_displayError("Error:" . mysqli_connect_error());
        }else{
            $this->connected = true;
        }

        return $this->is_ready();
    }

    /**
     * Sets the connection charset
     * @param string $charset default is UTF8
     */
    function set_charset($charset){
        $this->charset = $charset;
        mysqli_set_charset($this->link,$charset);
    }

    /**
     * Restarts a connection with diferent connection array if given
     * @param  array $config configuration array
     * @return boolean connection status
     */
    function reconnect($config=null){
        $this->close();
        $this->_loadConfig($config);
        $this->connect();
        return $this->is_ready();
    }

    /**
     * checks if the connection is ready
     * @return boolean connection status
     */
    function is_ready(){
        return ($this->connected) ? true : false;
    }

    /**
     * Logs a query execution
     * @param  string $sql   SQL query
     * @param  int $duration execution time
     * @param  int $results  number of affected_rows
     * @return void
     */
    private function _logQuery($sql,$duration,$results){
        if(!$this->allow_logging){
            return;
        }
        $this->LOG[] = array(
                    'time' => round($duration,5),
                    'results' => $results,
                    'SQL' => $sql,
                );
    }

    /**
     * Mysql Query
     * @param  string $sql the SQL to execute
     * @param  array $params any array pair of parameters
     * @return Object OBJ_mysql_result() or false if the query failed
     * @see  OBJ_mysql_result()
     */
    function query($sql="",$params=false){
        if(!$this->is_ready()) return false;

        if (strlen($sql)==0){
            $this->_displayError("Can't execute an empty Query");
            return;
        }

        if($params!==FALSE && is_array($params)){
            $sql = $this->_parseQueryParams($sql,$params);
        }

        $this->query_count++;

        $query_start_time = microtime(true);
        $result = mysqli_query($this->link, $sql);
        $query_duration = microtime(true)-$query_start_time;

        $this->_logQuery($sql, $query_duration, (int)$this->affected_rows() );

        if(is_object($result) && $result!==null){
            //return query result object
            return new OBJ_mysql_result($sql,$result);
        }else{

            if($result===true){
                //this query was successfull
                if( preg_match('/^\s*"?(INSERT|UPDATE|DELETE|REPLACE)\s+/i', $sql) ){
                    //was it an INSERT?
                    if($this->insert_id()>0){
                        return (int)$this->insert_id();
                    }
                    //was it an UPDATE or DELERE?
                    if($this->affected_rows()>0){
                        return (int)$this->affected_rows();
                    }
                    return true;
                }else{
                    return true;
                }
            }else{
                //this query returned an error, we must display it
                $this->_displayError( mysqli_error($this->link) );
            }
        }
    }

    /**
     * Creates and executes an insert statement
     * @param  string $table target table
     * @param  array  $data  data to insert
     * @return mixed Affected rows or null if the query failed
     */
    function insert($table="",$data=array()){

        if(strlen($table)==0){
            $this->_displayError("invalid table name");
            return false;
        }
        if(count($data)==0){
            $this->_displayError("empty data to INSERT");
            return false;
        }

        //extracting column names
        $columns = array_keys($data);
        foreach($columns as $k => $_key){
            $columns[$k] = "`".$_key."`";
        }

        $columns = implode(",",$columns);
        //extracting values
        foreach($data as $k => $_value){
            $data[$k] = $this->secure($_value);
        }
        $values = implode(",",$data);


        $sql = "INSERT INTO `".$table."` ($columns) VALUES ($values);";

       return $this->query($sql);

    }

    /**
     * Creates and executes a replace statement
     * @param  string $table target table
     * @param  array  $data  data to insert
     * @return mixed Affected rows or null if the query failed
     */
    function replace($table="",$data=array()){

        if(strlen($table)==0){
            $this->_displayError("invalid table name");
            return false;
        }
        if(count($data)==0){
            $this->_displayError("empty data to INSERT");
            return false;
        }

        //extracting column names
        $columns = array_keys($data);
        foreach($columns as $k => $_key){
            $columns[$k] = "`".$_key."`";
        }

        $columns = implode(",",$columns);
        //extracting values
        foreach($data as $k => $_value){
            $data[$k] = $this->secure($_value);
        }
        $values = implode(",",$data);


        $sql = "REPLACE INTO `".$table."` ($columns) VALUES ($values);";

       return $this->query($sql);

    }

    /**
     * Creates and executes an update statement
     * @param  string $table target table
     * @param  array  $data  data to update
     * @param  string|array $where where clause
     * @return mixed Affected rows or null if the query failed
     */
    function update($table="",$data=array(),$where="1=1"){

        if(strlen($table)==0){
            $this->_displayError("invalid table name");
            return false;
        }
        if(count($data)==0){
            $this->_displayError("empty data to UPDATE");
            return false;
        }

        $SET = $this->_parseArrayPair($data);

        if(is_string($where)){
            $WHERE = ($where);
        }elseif(is_array($where)){
            $WHERE = $this->_parseArrayPair($where,"AND");
        }

        $sql = "UPDATE $table SET $SET WHERE ($WHERE);";

        return $this->query($sql);

    }

    /**
     * Creates and executes a delete statement
     * @param  string $table the target table
     * @param  string|array $where the where clause
     * @return mixed Affected rows or null if the query failed
     */
    function delete($table="",$where="1=1"){

        if(strlen($table)==0){
            $this->_displayError("invalid table name");
            return false;
        }

        if(is_string($where)){
            $WHERE = ($where);
        }elseif(is_array($where)){
            $WHERE = $this->_parseArrayPair($where,"AND");
        }

        $sql = "DELETE FROM $table WHERE ($WHERE);";

        return $this->query($sql);

    }

    /**
     * Parses arrays with value pairs and generates SQL to use in queries
     *
     * @access private
     * @param  array $ArrayPair The value pair to parse
     * @param  string $glue the glue for the implode(), can be "," for SETs or "AND" for WHEREs
     *
     */
    private function _parseArrayPair($ArrayPair,$glue=","){
        $sql = "";
        $pairs = array();
        if(!empty($ArrayPair)){
            foreach($ArrayPair as $_key => $_value){
                $_connector = "=";
                if(strpos($_key," IS")!==false){
                    $_connector = " IS";
                }
                if(strpos($_key," IN")!==false){
                    $_connector = " IN";
                }
                if(strpos($_key," >")!==false && strpos($_key,"=")===false){
                    $_connector = " >";
                }
                if(strpos($_key," <")!==false && strpos($_key,"=")===false){
                    $_connector = " <";
                }
                if(strpos($_key," >=")!==false){
                    $_connector = " >=";
                }
                if(strpos($_key," <=")!==false){
                    $_connector = " <=";
                }
                if(strpos($_key," LIKE")!==false){
                    $_connector = " LIKE";
                }

                $pairs[] = " `".str_replace($_connector,"",$_key)."` $_connector ".$this->secure($_value)." ";

            }
            $sql = implode($glue, $pairs);
        }

        return $sql;
    }

    /**
     * Parsing query parameters replacing any "?" with a given $param
     *
     * @access private
     * @param  string $sql SQL query to parse
     * @param  array $params Array with values to place on any "?" found
     * @return string Parsed SQL string
     *
     */
    private function _parseQueryParams($sql,$params){

        if (strpos($sql, "?") === FALSE){ //is there anything to parse?
            return $sql;
        }
        if ( !is_array($params) ){ //conver to array
            $params = array($params);
        }
        $parse_key = md5(uniqid(time(),true));
        $parsed_sql = str_replace("?",$parse_key,$sql);
        $k = 0;
        while(strpos($parsed_sql, $parse_key)>0){
            $value = $this->secure($params[$k]);
            $parsed_sql = preg_replace("/$parse_key/",$value,$parsed_sql,1);
            $k++;
        }
        return $parsed_sql;
    }

    /**
     * Generates secure values depending on the type of the input.
     *
     * This "smart" function will escape and secure your data depending on its type.
     * here are some things you need to know:
     *
     ** Will send NULL if $var is empty
     ** Will treat bools as 0 or 1
     ** Will escape any string value
     ** Will round(6) any float value. will also replace "," for "."
     ** WARNING: Will treat strings cast as an object as RAW MySQL
     ** to use this send anything like this (object)"NOW()" and it will bypass the escaping.
     ** make sure you only use this when you need to execute raw MySQL functions like NOW() or ENCODE()
     *
     * @param mixed $var the value to secure
     * @return string Secure value
     *
    */
    function secure($var){
        if(is_object($var) && isset($var->scalar) && count((array)$var)==1){
            $var = (string)$var->scalar;
        }elseif(is_string($var)){
            $var = trim($var);
            $var = "'".$this->escape($var)."'";
        }elseif(is_int($var)){
            $var = intval((int)$var);
        }elseif(is_float($var)){
            $var = "'".round(floatval(str_replace(",",".",$var)),6)."'";
        }elseif(is_bool($var)){
            $var = (int)$var;
        }elseif(is_array($var)){
            $var = NULL;
        }

        $var = iconv("UTF-8", "UTF-8", $var);
        return ($var != "") ? $var  : "NULL";
    }

    /**
     * escapes any given string
     *
    */
    function escape($str){
        $str = get_magic_quotes_gpc() ? stripslashes($str) : $str;
        $str = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($this->link,$str) : mysqli_escape_string($str);
        return (string)$str;
    }

    /**
     * Closes the MySQLi Connection
     */
    function close(){
        $this->connected = false;
        if($this->link) mysqli_close($this->link);
    }

    /**
     * returns mysqli_insert_id()
     */
    function insert_id(){
        return mysqli_insert_id($this->link);
    }
    /**
     * returns mysqli_affected_rows()
     */
    function affected_rows(){
        return mysqli_affected_rows($this->link);
    }

    /**
     * Returns all erros occurrend during the execution
     * @return array MySQL Errors
     */
    function errors(){
        return count($this->_errors)>0 ? $this->_errors : false;
    }

    /**
     * Return last error occurred
     * @return string Mysql Error
     */
    function lastError(){
        return count($this->_errors)>0 ? end($this->_errors) : false;
    }

    /**
     * Returns the query log
     * @return array Log entries
     */
    function log(){
        return $this->LOG;
    }

    /**
     * __destruct magic method
     *
     * This will make sure that the connection is closed when the variable is unset()
     *
    */
    function __destruct(){
        $this->close();
        return;
    }

    /**
     * Displays a given error mensage and exits().
    */
    private function _displayError($e){

        $this->_errors[] = $e;

        if($this->echo_on_error){
            $box_border = $this->css_mysql_box_border;
            $box_bg = $this->css_mysql_box_bg;
            echo "<div class='OBJ-mysql-box' style='border:$box_border; background:$box_bg; padding:10px; margin:10px;'>";
            echo "<b style='font-size:14px;'>MYSQL Error:</b> ";
            echo "<code style='display:block;'>";
            echo $e;
            echo "</code>";
            echo "</div>";
        }

        if($this->exit_on_error){
            exit();
        }

    }

    /**
     * Loads a configuration
     *
    */
    private function _loadConfig($config){
        if(isset($config['hostname']) && !empty($config['hostname'])){
            $this->hostname = $config['hostname'];
        }
        if(isset($config['username']) && !empty($config['username'])){
            $this->username = $config['username'];
        }
        if(isset($config['password']) && !empty($config['password'])){
            $this->password = $config['password'];
        }
        if(isset($config['database']) && !empty($config['database'])){
            $this->database = $config['database'];
        }
        if(isset($config['port']) && !empty($config['port'])){
            $this->port = $config['port'];
        }
        if(isset($config['exit_on_error'])){
            $this->exit_on_error = $config['exit_on_error'];
        }
        if(isset($config['echo_on_error'])){
            $this->echo_on_error = $config['echo_on_error'];
        }
        if(isset($config['allow_logging'])){
            $this->allow_logging = (bool)$config['allow_logging'];
        }
        if(isset($config['charset']) && !empty($config['charset'])){
            $this->charset = $config['charset'];
        }
    }
}
