<?php
namespace SqlBuilder {

/***
 * Generate SQL expresion for table
 *
 * $values = new SqlValues(["id, name"]);
 * $values->add([4,"Jan"]);
 * $values->add([6,"Petr"]);
 * $this->dbmole->selectRows("SELECT * FROM (".$values->sql().")q (id,name)", $values->bind()); // "SELECT * FROM (VALUES(4,'Jan'),(6,'Petr'))q (id,name)"
 ***/
class SqlValues {

	var $fields;
	var $types;
	var $data;
	var $bind_ar;
	var $row;

	function __construct($fields, $options=[]) {
		$options+= [
			'bind_ar' => [],
			'types' => null
		];
		if(!is_array($fields)) {
			$this->fields = [ $fields ];
		} else {
			$this->fields = array_values($fields);
		}
		if(is_array($options['types'])) {
			$this->types = array_values($options['types']);
		} else {
			$this->types = array_fill(0, count($fields), $options['types']);
		}
		foreach($this->fields as $k => &$f) {
			if($p=strpos($f, '::')) {
				$this->types[$k]=substr($f,$p+2);
				$f=substr($f,0,$p);
			}
		}
		$this->data = [];
		$this->bind_ar = $options['bind_ar'];
		$this->row=0;
	}

	function hasData() {
		return (bool) $this->data;
	}

	function addMore($values) {
		foreach($values as $v) {
			$this->add($v);
		}
	}

	function add($values) {
		if(!is_array($values)) {
			$values = func_get_args();
		}
		$i=$this->row++;

		reset($this->fields);
		if(!$this->data) {
			reset($this->types);
		}

		$str = [];
		foreach($values as $v) {
			if($v === null) {
				$s= "null";
			} elseif(is_int($v) or is_float($v)) {
				$s = $v;
			} else {
				$id = ":" . current($this->fields) . "_$i";
				$this->bind_ar[$id] = $v;
				$s = $id;
			}
			if(!$this->data) {
				$type=current($this->types);
				if($type!==null) {
					$s.="::$type";
				}
				next($this->types);
			}
			$str[] = $s;
			next($this->fields);
			continue;
		}
		$this->data[] = implode(",", $str);
	}

	function _bracketed() {
	  return "(".implode('),(', $this->data).")";
	}

	function sql() {
		if($this->data) {
			return "VALUES ". $this->_bracketed();
		}
		return "(SELECT)";
	}

	function sqlArray($type=null) {
		if($type === null) {
			if(count($this->types) == 1) {
				$type = $this->types[0];
			}
			if($type === null) {
				throw Exception("Can not determine the type of the SQL array");
			}
		}

		if($this->data) {
			$data = count($this->fields) === 1 ? implode(",", $this->data) : $this->_bracketed();
			$out="ARRAY[$data]";
		} else {
			$out = "ARRAY[]";
		}

		if($type) {
			$out.="::{$type}[]";
		}
		return $out;
	}


	function withSql($tableName) {
		$fields = implode(",", $this->fields);
		if($this->data) {
			$data = $this->sql();
		} else {
			$data = implode(',', array_map(function($v) {return $v===null?'NULL':"NULL::$v";}, $this->types));
			$data = "SELECT $data WHERE false";
		}
		return "$tableName($fields) AS ($data)";
	}

	function bind() {
		return $this->bind_ar;
	}

	function createTemporaryTableSql($name) {
		return "CREATE TEMPORARY TABLE " . $this->withSql($name);
	}

	function count() {
		return count($this->data);
	}
}
}
