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


//load the result class file
include("OBJ_mysql_result.php");

/**
 * OBJ-mysql class
 *
 *
 *  Config DATA:
 *
 *  $database_info["hostname"]  = "localhost";
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
    private $socket;

    protected $link;
    protected $connected = false;

    var $css_mysql_box_border = "1px solid orange";
    var $css_mysql_box_bg = "#FFCC66";


    function OBJ_mysql($config=null){
        $this->connected = false;
        $this->_loadConfig($config);
        $this->connect();
    }

    function connect(){
        if($this->connected){
            return true;   
        }

        $this->link = mysqli_connect($this->hostname,
                            $this->username,
                            $this->password,
                            $this->database,
                            $this->port,
                            $this->socket);
        if($e = $this->connect_error){
            $this->_displayBox($e);
        }else{
            $this->connected = true;
        }

    }

    function reconnect($config=null){

        $this->_loadConfig($config);
        $this->connect();
    }

    function ready(){
        return $this->connected();
    }

    function query($str){

        $result = mysqli_query($this->link, $str);
        if(is_object($result) && $result!==null){
            return new OBJ_mysql_result($result);
        }else{
            return ($result) ? true : false;
        }

    }

    function close(){
        mysqli_close($this->link);
    }

    private function _displayBox($e){

        $box_border = $this->css_mysql_box_border;
        $box_bg = $this->css_mysql_box_bg;

        echo "<div class='OBJ-mysql-box' style='boder:$box_border; background:$box_bg; padding:10px; margin:10px;'>";
        echo "<b style='font-size:14px;'>MYSQL Error:</b> ";
        echo $e;
        echo "</div>";

        $this->close();
        exit();
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
    }


}

//old db class 
class OBJ_mysql_old 
{

    //connection info
    var $database_info;
    var $status=0;
    var $output;
    var $link;
    private $logger; 

    var $q_number;
    var $F;

    var $lastQuery;
    var $lastID;
    var $affected_rows;
    private $data;
    var $count;


    var $EOF=true;

    function OBJ_mysql($db_info="default"){
        if($db_info!="default") $this->connect($db_info); 
    }

    function debug($str="all"){
        echo "OBJ_mysql class";
        echo "<br>";
        echo "output:".$this->output;
        echo "<br>";
        for($k=0;$k<count($this->logger);$k++){
            if($str=="all"){
                echo $this->logger[$k]["tipo"]." > ".$this->logger[$k]["txt"];
                echo "<br>";
            }else if($str==$this->logger[$k]["tipo"]){
                echo $this->logger[$k]["tipo"]." > ".$this->logger[$k]["txt"];
                echo "<br>";
            }
        }

    }

    /**
     * @name connect
     * @param (array) get_info = "default"
     * @return connects to the mysql database
     */
    function connect($get_info="default"){
        if($get_info!="default"){//non default case
            $this->database_info['host']=$get_info['host'];
            $this->database_info['database']=$get_info['database'];
            $this->database_info['user']=$get_info['user'];
            $this->database_info['pass']=$get_info['pass'];
        }
        $host=$this->database_info['host'];
        $database=$this->database_info['database'];
        $user=$this->database_info['user'];
        $pass=$this->database_info['pass']; 

        $this->status=0;
        $this->log("connect","tentativa de connectar a: ".$host. " user: ".$user);
        $this->link=mysql_connect($host, $user, $pass,true);
        
        if(!empty($this->database_info['database'])){
            $this->select_database($database);
        }

        $this->set_charset('utf8');
        

        if(!empty($this->link)){
            $this->log("connect","Connectado e pronto");
            $this->status=1;
        }else{
            $this->status=0;
            $this->log("connect","erro.");
        } 
    }
    
    function select_database($database){ 
        $this->log("connect","using DB $database");
        mysql_select_db($database,$this->link) or $this->output=mysql_error($this->link);
            
    }
    function set_charset($charset){
        mysql_set_charset($charset,$this->link);
    }

    /**
     * @name query
     * @param (string) mysql query string
     * @return connects to the mysql database
     */
    function query($str){
        if($this->status==1){
            $this->free();
            $temp=mysql_query($str,$this->link) or $this->output=mysql_error($this->link);
            $this->q_number++;

            if($temp){
                $this->data=$temp;
                //$this->F=mysql_fetch_assoc($temp);
                $this->count    = @mysql_num_rows($temp);
                $this->lastQuery= $str;//last query
                $this->lastID   = @mysql_insert_id($this->link);
                $this->affected_rows = @mysql_affected_rows($this->link);
                $this->log("query",$str." > ".$this->count."rows");
                if($this->count>0) $this->EOF=false;
                return true;
            }else{
                //echo $str."\n\n\n".$this->output;
                if(strpos($this->output,"Duplicate entry")===false){
                    echo mysql_error($this->link);
                }
                $this->log("query","ERROR:".$str);
                //die($this->output." <br><br><br> ".$str);
                return false;
            }

        }else{
                return false;
        }
    }

    /**
     * @name next
     * @return Moves the array on position
     */
    function next(){
        $this->F=mysql_fetch_assoc($this->data);
        if(empty($this->F)) $this->EOF=true;
        return !$this->EOF;
    }


    /**
     * @name free
     * @return frees the last query and resets the values
     */
    function free(){
        @mysql_free_result($this->data);
        $this->count=0;
        $this->lastID = 0;
        $this->affected_rows = 0;
        $this->EOF=true;
    }

    /**
     * @name closes
     * @return closes mysql connection
     */
    function close(){
        $this->log("query",$this->q_number. " querys in this page.");
        $this->log("exit","flushing results");
        $this->log("exit","Connection Closed");
        @mysql_free_result($this->data,$this->link);
        $this->status=0;
        @mysql_close($this->link);
    }

    /**
     * @name log
     * @return log something for debug
     */
    function log($tipo,$info){
        $this->logger[]=array("tipo"=>$tipo, "txt"=>$info);
    }
    
    /**
     * @name test
     * @param $sql (string) the sql query or table
     * @param $array (array) the array of data to manipulate into the database
     * @param $where (string) the WHERE clause
     * @return debugs a query call and stops the program. 
     */
    function test($sql,$array=null,$where=""){
        if(!empty($array)){
            echo "<b>Table: $sql</b><br/>";
            echo "<b>Where: $where</b><br/>";
            echo "<pre>";
            print_r($array);
            echo "</pre>";
        }else{
            echo "<pre>";
            echo ($sql);
            echo "</pre>";
        }
    }
    
    /**
     * @name table
     * @return builds a table out of a mysql result
     */
    private function getTable(){
        $out= "<pre>";
        $out.= "\n";
        $out.= $this->lastQuery;
        $out.= "\n";
        $out.= "</pre>";
        $first = true;
        $z = 0;
        while($this->next()){
            if($first){
                $out.= '<table>';
                $out.= '<thead>';
                $out.= '<tr>';
                $i=0;
                while ($i < count($this->F)) {
                    $meta = mysql_fetch_field($this->data, $i);
                    $out.= "<th>".$meta->name."</th>";
                    $fields[$i]=$meta->name;
                    $i++;
                }
                $max=$i;
                $out.= '</tr>';
                $out.= '</thead>';
                $first = false;
            }
            $class = ($z++ & 1 )? "row" : "oddrow";
            $out.= "<tr class='$class'>";
            for($x=0;$x<$max;$x++){
                $out.= "<td>";
                $out.= $this->field($fields[$x]);
                $out.= "</td>";
             }
            $out.= "</tr>";
        }
        $out.= "</table>";

        return $out;
    }

    /*
     * @name get Row
     * @info Gets next mysql Row
    */
    function getRow(){
        if($this->status==1){
            if(!$this->EOF){
                $this->next();
                return $this->F;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    /*
     * @name get Column
     * @info Gets an entire column
    */
    function getColumn($column=""){
        $output = array();
        while($this->next()){
            if(isset($this->F[$column])){
                $output[] = $this->f($column);
            }else{
                $output[] = 0;
            }
        }
        return $output;
    }

    /*
     * @name get Row
     * @info Gets something from next mysql Row
    */
    function nextF($str){
        if($this->status==1){
            if(!$this->EOF){
                $this->next();
                return $this->f($str);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * @name General INSERT Function
     * @param (string) Table to inser
     * @param (Array) array("Field"=>"value");
     * @return builds a table out of a mysql result
     */
    function insert($table,$values){
        if($this->status==1){
            if(is_array($values)){
                $insert_srt="";
                $value_str="";
                $total=count($values);
                $k=0;
                if($total>0){ 
                    foreach($values as $key => $value){
                        $insert_srt.="`".$key."`";
                        $value_str.=$this->secure_value($value);
                        $k++;
                        if($k<$total){
                            $insert_srt.=" , ";
                            $value_str.=" , ";
                        }
                    }

                    $str="INSERT INTO `".trim($table)."` (".$insert_srt.") VALUES (".$value_str.") ";
                    return $this->query($str);

                }else{
                    $this->log("query","EMPTY DATA TO INSERT");
                    return false;
                }
            }else{
                $this->log("query","WRONG DATA TO INSERT");
                return false;
            }

        }
    }//end insert


    /**
     * @name General UPDATE Function
     * @param (string) Table to update
     * @param (Array) array("Field"=>"value");
     * @param (string) WHERE clause
     * @return builds a table out of a mysql result
     */
    function update($table,$values,$getwhere="1=1"){
        if($this->status==1){
            if(is_array($values)){
                $update_str="";
                $total=count($values);
                $k=0;
                if($total>0){ 
                    foreach($values as $key => $value){
                            $update_str.=" `".$key."` = ".$this->secure_value($value);
                            $k++;
                            if($k<$total) $update_str.=" , ";
                    }

                    $str="UPDATE `".trim($table)."` SET  ".$update_str." WHERE ".$where;
                    return $this->query($str);

                }else{
                    $this->log("query","EMPTY DATA TO UPDATE");
                    return false;
                }
            }else{
                $this->log("query","WRONG DATA TO UPDATE");
                return false;
            }
        }
    }//end update



    /**
     * @name General DELETE Function
     * @param (string) Table to update
     * @param (string) WHERE clause
     * @return builds a table out of a mysql result
     */
    function delete($table,$clause){
        if($this->status==1){
            if(is_string($clause)){
                if($clause!=""){
                    $str="DELETE FROM `".trim($table)."` WHERE ( ".$clause." )";
                    return $this->query($str);
                }else{
                    $this->log("query","CAN'T DELETE FROM EMPTY CLAUSE");
                    return false;
                }
            }else{
                $this->log("query","WRONG DATA TO DELETE");
                return false;
            }
        }

    }//end delete


    /**
     * @name result
     * @return mysql_affected_rows
     */
    function result(){
        if($this->status==1){
            return mysql_affected_rows($this->link);
        }else{
            return 0;
        }
    }

    /**
     * @name secure_value
     * @param  (mixed) value
     * @return (string) mysql secure value
     *
     * @example inputs: 
     * item => value,
     *
     * item => array(type,value)
     * )
     */
    function secure_value($item){

            $tipo="text";

            if(!is_array($item)){
                if($item=="NOW()"){
                    $tipo="raw";
                }else if(strpos(strtoupper($item),'DATE')===0){
                    $tipo="raw";
                }else if(strpos(strtoupper($item),'MD5(')===0){
                    $tipo="raw";
                }else if(strpos(strtoupper($item),'SHA1(')===0){
                    $tipo="raw";
                }else if(strpos(strtoupper($item),'ENCODE(')===0){
                    $tipo="raw";
                }else if(strpos(strtoupper($item),'TIMESTAMP(')===0){
                    $tipo="raw";
                }else if(is_string($item)){
                    $item = trim($item);
                    $tipo="text";
                }else if(is_int($item)){
                    $tipo="int";
                }else if(is_float($item)){
                    $tipo="float";
                }
            }else{
                $tipo = $item[0];
                $item = $item[1];
            }
            if($tipo!="raw"){
                $item = get_magic_quotes_gpc() ? stripslashes($item) : $item;
                $item = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($item) : mysql_escape_string($item);
            }
            if(strtoupper($tipo)!="HTML"){
                $item = strip_tags($item);
            }
            $item = iconv("UTF-8", "UTF-8", $item);

            switch (strtoupper($tipo)) {
                case "TEXT":
                case "HTML":
                    $item = ($item != "") ? "'" . $item . "'" : "NULL";
                break;
                case "LONG":
                case "INT":
                    $item = ($item != "") ? intval((int)$item) : "NULL";
                break;
                case "FLOAT":
                    $item = ($item != "") ? "'" . round(floatval(str_replace(",",".",$item)),6) . "'" : "NULL";
                break;
                case "RAW":
                    $item = ($item != "") ? $item  : "NULL";
                break;
            }

            return $item;
    }

    function getData(){
        $data = array();
        if($this->EOF) return array();
        while($this->next()){
                $dataRow = array_map('stripslashes', $this->F);
                $data[] = $dataRow;
        }
        return $data;
    }

    function getSimpleArray($key,$value){
        $data = array();
        if($this->EOF) return array();
        while($this->next()){
                $ID  = $this->f($key);
                $VAL = $this->f($value);
                $data[$ID] = $VAL;
        }
        return $data;
    }

    //ALIAS 
    function q($str){ return $this->query($str);}   //query()
    function field($str){ return $this->F[$str];}   //F[]
    function f($str){ return isset($this->F[$str]) ? stripslashes($this->F[$str]) : "";}            //F[]
    function p($str){ echo $this->F[$str]; }                //echo F[]
    function __toString(){ return $this->getTable(); }      // getTable()
    
    
    /**
     * @name reset
     * @return reload last Query
     * @Example 
       $db->q('SELECT a,b,c FROM y');
       
       //get all data
       $all_data = $db->getData();
       
       //same query, reset results
       $just_a = $db->reset()->getColumn('a'); 
       $just_b = $db->reset()->getColumn('b');
     
     */
    function reset(){
        $this->EOF = false;
        mysql_data_seek($this->data,0); 
        return $this; 
    } // redo last query

}//END class

?>