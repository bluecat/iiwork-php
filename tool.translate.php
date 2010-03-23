<?
set_time_limit(0);

$mini['auto'] = 1;
include "_inc.php";
include "_inc.io.php";

head("
	style:css/style.css
");


/** 한글만 뽑아내기
 * @class 
 * @param
		$name: description
		$is: 확인만
 * @return 
 */
function getKorean($data, $is = 0) {
	$hangul_jamo = '\x{1100}-\x{11ff}';
	$hangul_compatibility_jamo = '\x{3130}-\x{318f}';
	$hangul_syllables = '\x{ac00}-\x{d7af}';

	if ($is) {
		return preg_match('/['.$hangul_jamo.$hangul_compatibility_jamo.$hangul_syllables.']+/u', $data);
	}
	else {
		preg_match_all('/(['.$hangul_jamo.$hangul_compatibility_jamo.$hangul_syllables.']+)/u', $data, $mat);
		return $mat[1];
	}
} // END function

function getAllFile($dir = '') {
	def($dir, './');
	$output = array();

	foreach (getDir("dir: {$dir}, is_file:1, full:1") as $key=>$val):
		if (preg_match("/(addon|file|sfile|image)$/i", $val)) continue;

		if (is_dir($val))
			$output = array_merge($output, getAllFile($val));
		else if (preg_match("/\.(js|css|php|html|mini)$/i", basename($val))) {
			$output[] = $val;
		}
	endforeach;

	return $output;
}



//// 뽑기

$all = getAllFile();
$output = array();

foreach ($all as $key=>$val):
	$data = file($val);
	foreach ($data as $key2=>$val2):
		if (getKorean($val2, 1)) {
			$output[$key][$key2] = $val2;
		}
	endforeach;
endforeach;


//// 출력

foreach ($output as $key=>$val):
	echo "<span style='font:bold 20px Calibri'>{$all[$key]}</span><br /><br />\n";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";

	foreach ($val as $key2=>$val2):
		echo 
			"<tr><td height='1' colspan='100' bgcolor='#e1e1e1'></td></tr>".
			"<tr>".
			"<td style='padding:3px; font:11px Calibri;' align='center' width='60' nowrap='nowrap'>".number_format($key2)."</td>".
			"<td style='padding:3px; font:11px dotum;' width='450'>".str($val2, 'encode', 1)."</td>".
			"<td style='padding:3px;' width='450'><input type='text' name='ins[{$key}][{$key2}]' value='".addSlashes($val2)."' style='border:1 solid #ddd; font:11px dotum; width:100%;'></td>".
			"</tr>\n";
	endforeach;

	echo "<tr><td height='1' colspan='100' bgcolor='#e1e1e1'></td></tr></table><br /><br />\n";
endforeach;

foot(); ?>