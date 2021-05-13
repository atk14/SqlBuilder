<?php

class SqlWhere {
	function __construct($where='') {
		$this->where = $where;
	}

	function setWhere($where) {
		$this->where = $where;
		return $this;
	}

	function _undefined($str) {
		return $str === '' || $str === null;
	}

	function not($or) {
		if($this->_undefined($and)) {
			return $this->setWhere('FALSE');
		}
	}

	function isEmpty() {
		return $this->_undefined($this->where);
	}

	function and($with) {
		if($this->_undefined($with)) return $this;
		if($this->isEmpty()) return $this->setWhere($with);
		return $this->setWhere("({$this->where}) AND ({$with})");
	}

	function or($with) {
		if($this->_undefined($with)) return $this->setWhere('TRUE');
		if($this->isEmpty()) return $this->setWhere('TRUE');
		return $this->setWhere("({$this->where}) OR ({$with})");
	}

	function __toString() {
		return $this->where;
	}

}
