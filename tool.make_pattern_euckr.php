<?php

$pattern = array(
	'��',
	'��',
	'��',
	'��',
	'��',
	'��',
	'��',
	'��',
	'��',
	'��',
	'����',
	'����',
	'��',
	'����',
	'��',
	'�̴�',
	'�ϴ�',
	'��',
	'�μ�',
	'�ν�',
	'�κ���',
	'����',
	'����'
);

echo "\$pattern = array(<br />";

foreach ($pattern as $key=>$val):
	$a = 0;
	echo "\t\"";
	while($val[$a]):
		echo sprintf("\\x%X", ord($val[$a]));
		$a++;
	endwhile;

	echo "\",<br />";
endforeach;

echo ");";
echo "<br /><br /><a href='@make_pattern.php'>UTF-8</a>";

?>