<?php

class BaseSqlWhere {
	function __construct($where='') {
		$this->where = $where;
	}

	function setWhere($where) {
		$this->where = $where;
		return $this;
	}

	static function _undefined($str) {
		return $str === '' || $str === null;
	}

	function not() {
		if($this->_undefined($this->where)) {
			return $this->setWhere('FALSE');
		}
		return $this->setWhere("NOT ({$this->where})");
	}

	function isEmpty() {
		return $this->_undefined($this->where);
	}

	function __toString() {
		return $this->where;
	}

	function andWith($with) {
		if($this->_undefined($with)) return $this;
		if($this->isEmpty()) return $this->setWhere($with);
		return $this->setWhere("({$this->where}) AND ({$with})");
	}

	/**
	 * Result $where is ({$this->where}) OR ($with)
	 * If one of the "sided" is empty and $undefined is True,
	 * the result is TRUE (i.e. always satisfied). Otherwise,
	 * the result is just the "nonempty side".
   **/
	function orWith($with, $undefined=false) {
		if($this->_undefined($with)) {
			if($undefined) {
				$this->setWhere('TRUE');
			}
			return $this;
		}
		if($this->isEmpty()) {
			if($undefined) {
			  return $this->setWhere('TRUE');
			} else {
				return $this->setWhere($with);
			}
		}
		return $this->setWhere("({$this->where}) OR ({$with})");
	}
}

if(PHP_VERSION_ID >= 70000):
	#syntax not supported for PHP 5.x
	require_once(__DIR__ .'/sql_where_7.0.php');
else:

class SqlWhere extends BaseSqlWhere {
}

endif;
