<?php
/**
 * OBJ-mysql - Database Abstraction Class
 *
 * @package Database
 * @subpackage MySQL
 * @author Jonathan Tavares <the.entomb@gmail.com>
 * @license GNU General Public License, version 3 
 * @link https://github.com/entomb/OBJ-MySQL GitHub Source
 *
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
 *
*/


//load the result class file
include("OBJ_mysql_result.php");

/**
 * OBJ-mysql - Database Abstraction Class
 *
 *
 *  Config DATA:
 *
 *  $database_info["hostname"]  = "YOUR_HOST";
 *  $database_info["database"]  = "YOUR_DATABASE_NAME";
 *  $database_info["username"]  = "USER_NAME";
 *  $database_info["password"]  = "PASSWORD";
 *  $database_info["port"]      = "PORT";
 *  $database_info["socket"]    = "SOCKET";
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

    /**
     * Default configuration variables
    */
    private $hostname = "";
    private $username = "";
    private $password = "";
    private $database = "";
    private $port = "3306"; 
    private $charset = "UTF-8"; 

    protected $link;
    protected $LOG;
    protected $connected = false;

    var $query_count = 0;

    var $css_mysql_box_border = "3px solid orange";
    var $css_mysql_box_bg = "#FFCC66";
    var $exit_on_error = true; 


    function OBJ_mysql($config=null){
        $this->connected = false;
        $this->_loadConfig($config);
        $this->connect();
        $this->set_charset($this->charset);
    }

    function connect(){
        if($this->connected) return true;   

        $this->link = mysqli_connect(
                            $this->hostname,
                            $this->username,
                            $this->password,
                            $this->database,
                            $this->port
                        );

        if($e = $this->connect_error){
            $this->_displayError($e);
        }else{
            $this->connected = true;
        }


    }

    function set_charset($charset){
        mysqli_set_charset($this->link,$charset);
    }

    function reconnect($config=null){ 
        $this->close();
        $this->_loadConfig($config);
        $this->connect();
    }

    function ready(){
        return ($this->connected) ? true : false;
    }

    private function _logQuery($sql,$duration,$results){
        $this->LOG[] = array(
                    'time' => round($duration,5),
                    'results' => $results,
                    'SQL' => $sql,
                );
    }
 
    function query($sql="",$params=false){
        if(!$this->connected) return false;

        if (strlen($sql)==0){
            $this->_displayError("Can't execute an empty Query");
            return;
        }

        if($params!==FALSE){
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

    function insert($table="",$data=array()){
        if(!$this->connected) return false;

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
        var_dump($columns);
        $columns = implode(",",$columns); 
        //extracting values
        foreach($data as $k => $_value){
            $data[$k] = $this->secure($_value);
        }
        $values = implode(",",$data);


        $sql = "INSERT INTO `".$table."` ($columns) VALUES ($values);";
       
       return $this->query($sql);

    }

    function update($table="",$data=array(),$where=array()){

    }

    function delete($table="",$where){
        
    }


    private function _parseQueryParams($sql,$params){
        
        if (strpos($sql, "?") === FALSE){ //is there anything to parse?
            return $sql;
        }
        if ( !is_array($params) ){ //conver to array
            $params = array($params);
        }

        $parsed_sql = str_replace("?","{_?!?_}",$sql);
        $k = 0;
        while(strpos($parsed_sql, "{_?!?_}")>0){ 
            $value = $this->secure($params[$k]); 
            $parsed_sql = preg_replace("/(\{_\?\!\?_\})/",$value,$parsed_sql,1);
            $k++;
        } 
        return $parsed_sql;
    }


    function secure($var){ 
        if(is_object($var) && isset($var->scalar) && count((array)$var)==1){
            $var = (string)$var->scalar;
        }elseif(is_string($var)){
            $var = trim($var);
            $var = "'".$this->escape($var)."'";
        }elseif(is_int($var)){
            $var = intval((int)$var) ;
        }elseif(is_float($var)){
            $var = "'".round(floatval(str_replace(",",".",$item)),6)."'";
        }elseif(is_bool($var)){ 
            $var = (int)$var;
        }
        
        $var = iconv("UTF-8", "UTF-8", $var);
        return ($var != "") ? $var  : "NULL"; 
    }

    function escape($str){
        $str = get_magic_quotes_gpc() ? stripslashes($str) : $str;
        $str = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($str) : mysql_escape_string($str);
        return (string)$str;
    }

    /**
     * Closes the MySQLi Connection
     */
    function close(){
        $this->connected = false;
        if($this->link) mysqli_close($this->link);
    }

    function insert_id(){
        return mysqli_insert_id($this->link);
    }

    function affected_rows(){
        return mysqli_affected_rows($this->link);
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
     *
    */
    private function _displayError($e){

        $box_border = $this->css_mysql_box_border;
        $box_bg = $this->css_mysql_box_bg;

        echo "<div class='OBJ-mysql-box' style='border:$box_border; background:$box_bg; padding:10px; margin:10px;'>";
        echo "<b style='font-size:14px;'>MYSQL Error:</b> ";
        echo "<code style='display:block;'>";
        echo $e;
        echo "</code>";
        echo "</div>"; 
        if($this->exit_on_error) exit();
        
    } 

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
        if(isset($config['exit_on_error']) && !empty($config['exit_on_error'])){
            $this->exit_on_error = $config['exit_on_error'];
        }
    }


}
