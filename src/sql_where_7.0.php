<?php

class SqlWhere extends BaseSqlWhere {
	function and($with) {
		return $this->andWith($with);
	}

	function or($with, $undefined=false) {
		return $this->orWith($with, $undefined);
	}
}

