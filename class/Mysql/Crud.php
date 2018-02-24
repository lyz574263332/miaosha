<?php 
/**
* Easy Crud  -  This class kinda works like ORM. Just created for fun :) 
* @modify 王毅
* @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
* @version      0.1a
*/
namespace Mysql;
class Crud {//之后我们会继承这个类,使用里边的魔术方法 crud.php是基于Db更高层的封装

	private $db;
    protected $fields;
	public $variables;

	public function __construct($data = array()) {
        if ($this->fields && $data) {
            foreach ($data as $k => $d) {
                if (!in_array($k, $this->fields)) {
                    unset($data[$k]);
                }
            }
        }
		$this->variables  = $data;//构造方法中进行填充
	}

    public function setDb($db) {//设置数据库连接
        $this->db = $db;
    }
    public function getDb() {
        if (!$this->db) {
            $this->db = \Mysql\Db::getInstance('master');//说明就是Db类的实例
        }
        return $this->db;
    }

	public function __set($name,$value){
		if(strtolower($name) === $this->pk) {//魔术方法设置用于存变量,读写方便
			$this->variables[$this->pk] = $value;
		}
		else {
            if (!$this->fields || in_array($name, $this->fields)) {
                $this->variables[$name] = $value;
            }
		}
	}

	public function __get($name)
	{	
		if(is_array($this->variables)) {
			if(array_key_exists($name,$this->variables)) {
				return $this->variables[$name];
			}
		}

		$trace = debug_backtrace();
		trigger_error(
		'Undefined property via __get(): ' . $name .
		' in ' . $trace[0]['file'] .
		' on line ' . $trace[0]['line'],
		E_USER_NOTICE);
		return null;
	}

	public function save($id = "0") {//数据更新
		$this->variables[$this->pk] = $id ? $id : $this->variables[$this->pk];

		$fieldsvals = '';
		$columns = array_keys($this->variables);

		foreach($columns as $column)
		{
			if($column !== $this->pk)
			$fieldsvals .= "`{$column}` = :". $column . ",";//拼更新语句,要更新的字段,字段与内容分离
		}

		$fieldsvals = substr_replace($fieldsvals , '', -1);//字符串的最后一个字符替换掉,即去掉,

		if(count($columns) > 1 ) {
			$sql = "UPDATE `" . $this->table .  "` SET " . $fieldsvals . " WHERE `" . $this->pk . "`= :" . $this->pk;
			return $this->getDb()->query($sql,$this->variables);
		}
	}

	public function create() { 
		$bindings   	= $this->variables;

		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array('`' . implode("`,`",$fields) . '`', ":" . implode(",:",$fields));
			$sql 		= "INSERT INTO `".$this->table."` (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else {
			$sql 		= "INSERT INTO `".$this->table."` () VALUES ()";
		}

		$ok = $this->getDb()->query($sql,$bindings);
        if ($ok) {
            return $this->getDB()->lastInsertId();
        } else {
            return $ok;
        }
	}

	public function delete($id = "") {
		$id = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			$sql = "DELETE FROM `" . $this->table . "` WHERE `" . $this->pk . "`= :" . $this->pk. " LIMIT 1" ;
			return $this->getDb()->query($sql,array($this->pk=>$id));
		}
	}

	public function get($id = "") {
		$id = $id ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			$sql = "SELECT * FROM `" . $this->table ."` WHERE `" . $this->pk . "`= :" . $this->pk . " LIMIT 1";
			$this->variables = $this->getDb()->row($sql,array($this->pk=>$id));
		}
        return $this->variables;
	}

	public function all(){
		return $this->getDb()->query("SELECT * FROM `" . $this->table . '`');
	}

    public function count(){
        return $this->getDb()->query("SELECT COUNT(1) FROM `" . $this->table . '`');
    }

//	public function min($field)  {
//		if($field)
//		return $this->getDb()->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
//	}
//
//	public function max($field)  {
//		if($field)
//		return $this->getDb()->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
//	}
//
//	public function avg($field)  {
//		if($field)
//		return $this->getDb()->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
//	}
//
//	public function sum($field)  {
//		if($field)
//		return $this->getDb()->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
//	}

}
