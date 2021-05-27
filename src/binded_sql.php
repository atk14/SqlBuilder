<?php
namespace SqlBuilder {
/***
 * SQL query with bind arguments usage
 * $sql = new BindedSql('select .... where id = :id', [':id' => $id]);
 * $sql->seletctIntoArray($dbmole);
 * $dbmole->selectIntoArray()
 * list($query, $bind) = $sql;
 **/

class BindedSql implements \ArrayAccess {

	function __construct($sql, $bind=[]) {
		$this->sql = $sql;
		$this->bind = $bind;
	}

	function __call($name, $arguments) {
		$dbmole = key_exists('0', $arguments) && $arguments[0] ? $arguments[0] : $GLOBALS['dbmole'];
		if(substr($name, 0, 6) !== 'select') {
			throw new Exception("Unknown method BindedSql::$name");
		}
		return call_user_func([$dbmole, $name], $this->sql, $this->bind);
	}

	function escaped($dbmole = null) {
		if(!$dbmole) { $dbmole = $GLOBALS['dbmole']; }
		return strtr($this->sql, array_map([$dbmole, 'escapeValue4Sql'], $this->bind));
	}

	function __toString() {
		return $this->sql;
	}

	function getBind() {
		return $this->bind;
	}

	function getSql() {
		return $this->sql;
	}

	function offsetExists($offset) {
		return in_array($offset, [0,1]);
	}

	function offsetGet($offset) {
		switch($offset) {
			case 0: return $this->sql;
			case 1: return $this->bind;
			default: throw new Exception('No such key in BindedSql');
		}
	}

	function offsetSet($offset, $value) {
			throw new Exception('Not implemented');
	}

	function offsetUnset($offset) {
			throw new Exception('Not implemented');
	}

	function concat($other) {
		if($other instanceof BindedSql) {
			return new BindedSql($this->sql . $other->sql, $this->bind + $other->bind);
		} else {
			return new BindedSql($this->sql . $other, $this->bind);
		}
	}

	function append($other) {
		if($other instanceof BindedSql) {
			$this->sql.=$other->sql;
			$this->bind+=$other->bind;
		} else {
			$this->sql.=$other;
		}
		return $this;
	}

	static function Concatenate($a, $b) {
		if($a instanceof BindedSql) {
			return $a->concat($b);
		} else {
			$out = new BindedSql($a);
			return $out->append($b);
		}
	}

	function addBindFrom($bind) {
		if($bind instanceof BindedSql) {
			$this->addBind($bind->bind);
		} elseif(!is_string($bind)) {
			$this->addBind($bind);
		}
	}

	function addBind($bind) {
			$this->bind+=$bind;
	}
}

}
