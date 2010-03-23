<?php
/* 정통부 우편번호 INSERT TOOL
 * XLS 파일로 불러서 "우편번호 읍면동 리 도서 주소(전체)" 순으로 탭으로 구분 txt 파일로 저장
 * 파일명 zipcode.txt로 같은 디렉토리에 위치시키고 실행시키면 됩니다.
 * - 읍면동이 빈값일경우 사서함입니다.
 * ZIPCODE	SIDO	GUGUN	DONG	RI	BUNJI	SIDO_ENG	GUGUN_ENG	DONG_ENG	RI_ENG	BUNJI_ENG
 */

include "_inc.php";
include "_inc.admin.php";
@set_time_limit(0);
$filename = "zipcode.txt";

head("style:css/style.css");
getMember();
checkAdmin("god");

echo <<< END
<div style='padding:15px; border-bottom:1px solid #ddd; margin-bottom:10px; font:12px dotum;'>
	<span class='word'><span style='font:bold 16px Georgia, tahoma'>the M</span></span>		
	<span style='font:14px Georgia;'>zipcode</span><br />

	<div style='border:1px solid #e1e1e1; margin-top:10px; padding:5px; background-color:#f0f0f0; line-height:1.5;'>
		우정사업본부에서 고시한 우편번호 DB를 이용할 수 있도록 설치합니다.<br />
		- 최신 고시된 우편번호DB는 우정사업본부에서 다운로드 받으실 수 있습니다. <a href='http://www.koreapost.go.kr/' target='_blank'>[우정사업본부 바로가기]</a><br />
		- 우편번호 DB 설치에는 많은 시간이 필요할 수 있습니다.<br /><br />
		- 설치방법
		<ul>
			<li style='list-style-type:decimal;'>우정사업본부에서 다운로드 받은 파일 중 xls파일을 엑셀로 엽니다.</li>
			<li style='list-style-type:decimal;'>우편번호, 시도, 시군구, 읍면동, 리, 도서, 번지, 아파트/건물명 순으로 배치합니다.</li>
			<li style='list-style-type:decimal;'>다른 이름으로 저장을 선택하여 파일 유형을 탭으로 구분한 txt 파일로 선택하고 "zipcode.txt"로 저장 합니다.</li>
			<li style='list-style-type:decimal;'>zipcode.txt 파일을 @zipcode.php 파일이 있는 미니보드 디렉토리에 업로드 합니다.</li>
			<li style='list-style-type:decimal;'>@zipcode.php 파일을 웹에서 접근 합니다. (http://자신의도메인/미니보드설치주소/@zipcode.php)</li>
		</ul>
	</div>
</div>
<div style='padding:15px; line-height:1.5; font:12px "Malgun Gothic", dotum;'>
END;

if (!file_exists($filename)) {
	echo "@zipcode.php 가 있는 폴더에 zipcode.txt 파일을 위치시키셔야 합니다.";
	foot();
	exit;
}

if (empty($_REQUEST['confirm'])) {
	echo "<a href='@zipcode.php?exec=1&amp;confirm=1' style='font:13px \"Malgun Gothic\", dotum;'>우편번호 DB를 입력합니다.</a>";
	foot();
	exit;
}

//// 자료 입력
$count = 0;
$data = file($filename);
iss($output);
iss($_REQUEST['exec']);

if ($_REQUEST['exec']) {
	//// TABLE 생성
	if (sql("SHOW TABLES LIKE '{$mini['name']['zipcode']}'")) sql("DROP TABLE {$mini['name']['zipcode']}");
	sql("CREATE TABLE {$mini['name']['zipcode']} (
		no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
		zipcode varchar(255) not null default '',
		h1 varchar(255) not null default '',
		h2 varchar(255) not null default '',
		h3 varchar(255) not null default '',
		h4 varchar(255) not null default '',
		h5 varchar(255) not null default '',
	
		KEY h1(h1),
		KEY h2(h2),
		KEY h3(h3),
		KEY h4(h4)
	)");

	foreach($data as $key=>$val):
		unset($ins);
		if($val){
			$val = str_replace(array("'","\""),array("&$39;","&#34;"),$val);
			$ins = explode("	",$val);

			// 규칙 입력 부분
			if (!empty($ins[7])) $ins[5] = !empty($ins[5]) ? $ins[5]." ".$ins[7] : $ins[7];
			if (!empty($ins[6])) $ins[5] = !empty($ins[5]) ? $ins[5]." ".$ins[6] : $ins[6];
			if(!$ins[3]) $ins[3]="사서함";

			$result = sql("INSERT INTO {$mini['name']['zipcode']} (zipcode,h1,h2,h3,h4,h5) VALUES ('$ins[0]','$ins[1]','$ins[2]','$ins[3]','$ins[4]','$ins[5]')");

			if($result){ 
				echo ".";
				if($key%150==0) echo "<br>";
				$count++;
			}
			else {
				echo "<br>값 : {$val}";
				sql("DELETE FROM {$mini['name']['zipcode']}");
				break;
			}
		}
	endforeach;

	$output = "<br><br>{$count} 개의 우편번호를 {$mini['name']['zipcode']} 테이블에 입력 했습니다.";
}

else {
	$output = number_format(count($data))." 개의 우편번호 정보가 있습니다. <br />입력하시겠습니까? <input type='button' value='확인' accesskey='r' onclick='location.href=\"{$_SERVER['PHP_SELF']}?exec=1\";'>";
}

echo $output;
echo "</div>";
foot();
?>