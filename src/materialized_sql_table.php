<?php
/***
 * SqlTable, that materialized herself if requested for result. This can be faster than
 * repated evaluation of the sql JOIN
 *
 * Usage
 * $t = new SqlTable(....);
 * ....
 * $mt = new MaterializeTable($table, $dbmole, ['field' => 'a, b, c', 'copy_joins' => [...]]);
 * $dbmole->selectInteger($mt->result()->count());
 * $dbmole->selectRows($mt->result()->select());
 * ....
 **/
class MaterializedSqlTable {
	function __construct($sqlTable, $dbmole, $materializeOptions, $options=[]) {
		$options+= ['materialize' => true ];

		$this->dbmole=$dbmole;
		$this->object=$this->table=$sqlTable;
		$this->materialized=null;
		$this->_options = $options;
		$this->materializeOptions=$materializeOptions;
	}

	function isMaterialized() {
		return (bool) $this->materialized;
	}

	function __clone() {
		$this->table = clone $this->table;
		if($this->materialized) {
			$this->object=$this->materialized= clone $this->materialized;
		} else {
			$this->object = $this->table;
		}
	}

	function __call($name, $arguments) {
		return call_user_func_array([$this->object, $name], $arguments);
	}

	function __get($name) {
		return $this->object->$name;
	}

	function discardMaterialized() {
		$this->object=$this->table;
		$this->materialized=null;
	}

	function result($options=[]) {
		$options += $this->_options;
		if(!$this->materialized) {
			if($options['materialize']) {
				$object = $this->materialize();
			}
		}
		unset($options['materialize']);
		return $this->object->result($options);
	}

	function materialize() {
		$options = $this->materializeOptions;
		if(is_callable($this->materializeOptions)) {
			$materializeOptions=($this->materializeOptions)();
		} else {
			$materializeOptions = $this->materializeOptions;
		}
		$this->object=$this->materialized=$this->table->materialize($this->dbmole, $materializeOptions['fields'], $materializeOptions);
	}
}
