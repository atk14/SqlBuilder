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

	function __construct($fields, $options=[]) {
		$options+= [
			'bind_ar' => []
		];
		$this->fields = $fields;
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
		$str = [];
		foreach($values as $v) {
			if($v === null) {
				$str[] = "null";
			} elseif(is_int($v) or is_float($v)) {
				$str[] = $v;
			} else {
				$id = ":" . current($this->fields) . "_$i";
				$this->bind_ar[$id] = $v;
				$str[] = $id;
			}
			next($this->fields);
		}
		$this->data[] = implode(",", $str);
	}

	function sql() {
		if($this->data) {
			return "VALUES (".implode('),(', $this->data).")";
		}
		return "(SELECT)";
	}

	function withSql($tableName) {
		$fields = implode(",", $this->fields);
		return "$tableName($fields) AS ({$this->sql()})";
	}

	function bind() {
		return $this->bind_ar;
	}
}
}
