<?php
// iiwork framwork test unit

include "ii.php";
$ii = new iiWork(true);
$ii->import("db.mysql");

$sql = iiDBMysql::getInstance();
//$sql->connect("localhost", "onbam", "onbam1", "onbam");
//iiDebug::trace($sql->getColumns("ob_art_list"));

function test($str) {
	global $a;
	$a .= $str;
}

function calluser($name, $args) {
	$name($args);
}

$array = array(
	'a' => 1,
	'b' => 2
);
$object = (object)$array;

$a = '';
iiDebug::check('array');
for ($i=0; $i<=10000; $i++) {
	$a .= $array['a'];
}
iiDebug::check('array');

$a = '';
iiDebug::check('object');
for ($i=0; $i<=10000; $i++) {
	$a .= $object->a;
}
iiDebug::check('object');


iiDebug::printTimes();
?>