<?php

$pattern = array(
	'와',
	'과',
	'을',
	'를',
	'은',
	'는',
	'이',
	'가',
	'도',
	'고',
	'에서',
	'에게',
	'에',
	'보다',
	'의',
	'이다',
	'니다',
	'다',
	'로서',
	'로써',
	'로부터',
	'부터',
	'으로'
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
echo "<br /><br /><a href='@make_pattern_euckr.php'>EUC-KR</a>";

?>