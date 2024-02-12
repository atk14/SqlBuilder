<?php
namespace SqlBuilder {

/**
 * Usage:
 * $w = new SqlWhere();
 * $w->and('id = 4');
 * $w->and('root_id = 3');
 * $dbmole->selectRows("select * from table where " . $w);
 **/

class BaseSqlWhere {

	var $where;

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
	#methods, that can be defined only in PHP with version at least 7.0
	require_once(__DIR__ .'/sql_where.inc.7.0');
else:

class SqlWhere extends BaseSqlWhere {
}

endif;

}
