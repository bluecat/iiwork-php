<?
set_time_limit(0);
flush();

$mini['auto'] = 1;

include "_inc.php";
include "_inc.io.php";

echo "입력시작...<br />";
flush();

$query_board = $query_search = '';

//for ($a=1; $a<=10; $a++) {	
for ($a=11; $a<=20; $a++) {	
/*
	$fp = fopen("@db_b_{$a}.sql", "r");
	$query_board .= fread($fp, filesize("@db_b_{$a}.sql"));
	fclose($fp);
*/
//	/*
	$fp = fopen("@db_s_{$a}.sql", "r");
	$query_search .= fread($fp, filesize("@db_s_{$a}.sql"));
	fclose($fp);
//	*/
}

echo "입력중...<br />";
flush();

checkTime("query");
//mysql_query($query_board) or die(mysql_error());
mysql_query($query_search) or die(mysql_error());
checkTime("query");

echo "all complete!!";
printTime(1);

exit;
?>




<?
exit;


iss($_GET['start']);
def($start, $_GET['start']);
def($start, 1);
$count = 50000;
$start_num = 16000000 - (($start - 1) * $count);

if ($start_num == 16000000) {
	$query_board = "INSERT INTO m_board_test (num, title, ment, category, tag) VALUES ";
	$query_search = "INSERT INTO m_search (target, mode, ment, id) VALUES ";
}
else {
	$query_board = $query_search = '';
}

$ment_ar = array(
	'가',
	'나',
	'다',
	'라',
	'마',
	'바',
	'사',
	'아',
	'자',
	'차',
	'카',
	'타',
	'파',
	'하'
);

for ($a=$start_num; $a > $start_num-$count; $a--) {
	$num = $a % 10;
	$tag = "[태그{$num}]";
	$category = "[카테고리{$num}]";
	$title = "제목입니다 - {$num}";
	$ment_add = $ment_ar[array_rand($ment_ar)];
	$ment = "내용입니다 (".$ment_add.")";
	
	if ($a != 16000000) {
		$query_board .= ",";
		$query_search .= ",";
	}

	$query_board .= "($a,'$title','$ment','$category','$tag')";
	$query_search .= "($a,'ment','$ment_add','test')";
	$query_search .= ",($a,'tag','".str_replace(array("[","]"),"",$tag)."','test')";
	$query_search .= ",($a,'category','".str_replace(array("[","]"),"",$category)."','test')";

	if (($start_num - $a + 1) % 5000 == 0) {
		echo ($start_num - $a + 1)." ...<br />";
		flush();
	}
}

$fp = fopen("@db_b_{$start}.sql", "w+");
fwrite($fp, $query_board);
fclose($fp);

$fp = fopen("@db_s_{$start}.sql", "w+");
fwrite($fp, $query_search);
fclose($fp);

/*
checkTime('board');
mysql_query($query_board) or die(mysql_error());
echo "보드 끝<br>";
flush();
checkTime('board');

checkTime('search');
mysql_query($query_search) or die(mysql_error());
echo "검색 끝<br>";
flush();
checkTime('search');
*/

head();
echo "전체: ".($start * $count).", 시작 : {$start_num}, 끝 : ".($start_num-$count)."<br>";
echo "만들기 끝 <a href='{$_SERVER['PHP_SELF']}?start=".($start+1)."'>다음</a>";
foot();
?>