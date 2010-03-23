<?
$ab_url = "d:\server\RWAPM4\RTM20040531\bin\ab";
$url = $_POST[url] ? $_POST[url] : "http://".$_SERVER[HTTP_HOST]."/";
$url2 = $_POST[url2] ? $_POST[url2] : "http://".$_SERVER[HTTP_HOST]."/";
$url3 = $_POST[url3] ? $_POST[url3] : "http://".$_SERVER[HTTP_HOST]."/";
if(!$_POST[count]) $_POST[count]= 300;
if(!$_POST[user]) $_POST[user] = 30;
?>

<form action='<?=$_SERVER['PHP_SELF']?>' method='post'>
iiAB - Apache Bench Tool<Br>
URL  <input type='text' name='url' value='<?=$url?>' size='40'><br>
URL2  <input type='text' name='url2' value='<?=$url2?>' size='40'><br>
URL2  <input type='text' name='url3' value='<?=$url3?>' size='40'><br>
COUNT <input type='text' name='count' value='<?=$_POST[count]?>' size='5'><br>
USER <input type='text' name='user' value='<?=$_POST[user]?>' size='3'><br>
COOKIES <input type='text' name='cookie' value='<?=$_POST[cookie]?>' size='20'><br>
POST_FILE_URL  <input type='text' name='file' value='<?=$_POST[file]?>' size='20'><br>
<input type='submit' value=' OK ' accesskey='s'>
</form>

<?
if($_POST[url3]) $width="width:33%;'";
elseif($_POST[url2]) $width="width:50%;'";
else $width="width:100%;'";

if($_POST[url] && $_POST[url]!="http://".$_SERVER[HTTP_HOST]."/"){
	set_time_limit(0);
	$make = "";

	if($_POST[cookie]) $make.=" -C {$_POST[cookie]}";

	echo "<hr>";
	echo "<div style='float:left;{$width}'>";
	system("{$ab_url} -n {$_POST[count]} -c {$_POST[user]} -w {$make} {$_POST[url]}");
	echo "</div>";

	if($_POST[url2] && $_POST[url2]!="http://".$_SERVER[HTTP_HOST]."/"){
		sleep(3);
		echo "<div style='float:left;{$width}'>";
		system("{$ab_url} -n {$_POST[count]} -c {$_POST[user]} -w {$make} {$_POST[url2]}");
		echo "</div>";
	}

	if($_POST[url3] && $_POST[url3]!="http://".$_SERVER[HTTP_HOST]."/"){
		sleep(3);
		echo "<div style='float:left;{$width}'>";
		system("{$ab_url} -n {$_POST[count]} -c {$_POST[user]} -w {$make} {$_POST[url3]}");
		echo "</div>";
	}
}
?>