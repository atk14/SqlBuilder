<?php
define("TEST",true);
require(__DIR__."/../vendor/autoload.php");

function &dbmole_connection($dbmole){
	static $connection;

	if(!isset($connection)) {
			$connection = pg_connect("dbname=test user=test password=test host=127.0.0.1");
	}
	return $connection;
}
