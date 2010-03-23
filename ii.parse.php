<?php

/** 문자열을 HTML용 HEX로 변경
 * @class string
 * @param
		$str: 문자열
 * @return String
 */
function strtohex($str) { 
	$retval = '';
	for ($i=0; $i<strlen($str[1]); $i++) { 
		$retval .= "&#x" . bin2hex(substr($str[1], $i, 1)) . ";"; 
	}
	return $retval; 
} // END function


/** 라이센스 가져오기
 * @class license
 * @param
		$name: description
 * @return 
 */
function getLicense($val = '') {
	global $mini;

	$licdata = array(
		'nothing' => array('No License', ''),
		'CCL' => array('Creative Commons License', 'http://creativecommons.org/'),
		'LGPLv21' => array('LGPL v2.1', 'http://www.gnu.org/licenses/lgpl-2.1.html'),
		'LGPLv3' => array('LGPL v3', 'http://www.gnu.org/licenses/lgpl-3.0.html'),
		'GPL' => array('GPL v1.0', 'http://www.gnu.org/licenses/gpl-1.0.txt'),
		'GPLv2' => array('GPL v2.0', 'http://www.gnu.org/licenses/gpl-2.0.html'),
		'GPLv3' => array('GPL v3.0', 'http://www.gnu.org/licenses/gpl-3.0.html'),
		'BSD' => array('BSD License', 'http://opensource.org/licenses/bsd-license.php'),
		'MIT' => array('MIT License', 'http://www.opensource.org/licenses/mit-license.php'),
		'Shareware' => array('Shareware', 'http://wiki.creativecommons.org/Shareware_license'),
		'NPL' => array('NPL', 'http://www.mozilla.org/MPL/NPL-1.0.html'),
		'MPL' => array('MPL', 'http://www.mozilla.org/MPL/MPL-1.1.html'),
		'Apache' => array('Apache License 1.0', 'http://www.apache.org/licenses/LICENSE-1.0'),
		'Apache11' => array('Apache License 1.1', 'http://www.apache.org/licenses/LICENSE-1.1'),
		'Apache2' => array('Apache License 2.0', 'http://www.apache.org/licenses/LICENSE-2.0'),
		'AL' => array('Artistic License', 'http://www.opensource.org/licenses/artistic-license-1.0.php'),
		'AL2' => array('Artistic License 2.0', 'http://www.opensource.org/licenses/artistic-license-2.0.php'),
		'PD' => array('Public Domain', 'http://creativecommons.org/licenses/publicdomain/deed.ko')
	);

	if ($val != 'array') {
		// CCL 처리
		if (preg_match("/^CCL/", $val)) {
			$tmp = preg_replace("/^CCL/i", "", $val);

			$tmp_count = 0;
			$output = "<img src='{$mini['dir']}admin/image/license/ccl_1.gif' border='0' class='middle' alt='저작자표시' /> ";
			if (strpos($tmp, '$') !== false) {
				$output .= "<img src='{$mini['dir']}admin/image/license/ccl_2.gif' border='0' class='middle' alt='비영리' /> ";	
				$tmp_count++;
			}
			if (strpos($tmp, '=') !== false) {
				$output .= "<img src='{$mini['dir']}admin/image/license/ccl_3.gif' border='0' class='middle' alt='변경금지' /> ";
				$tmp_count+=2;
			}
			else {
				$output .= "<img src='{$mini['dir']}admin/image/license/ccl_4.gif' border='0' class='middle' alt='동일조건변경허락' /> ";
				$tmp_count+=4;
			}

			switch ($tmp_count):
				case 0: $tmp_link = "http://creativecommons.org/licenses/by/2.0/kr/"; break;
				case 1: $tmp_link = "http://creativecommons.org/licenses/by-nc/2.0/kr/"; break;
				case 2: $tmp_link = "http://creativecommons.org/licenses/by-nd/2.0/kr/"; break;
				case 3: $tmp_link = "http://creativecommons.org/licenses/by-nc-nd/2.0/kr/"; break;
				case 4: $tmp_link = "http://creativecommons.org/licenses/by-sa/2.0/kr/"; break;
				case 5: $tmp_link = "http://creativecommons.org/licenses/by-nc-sa/2.0/kr/"; break;
				default:
					
			endswitch;

			$output = "<a href='{$tmp_link}' target='_blank' style='text-decoration:none;'>{$output}</a>";
		}

		else if (!empty($licdata[$val]) && !empty($licdata[$val][1])) {
			$output = "<a href='{$licdata[$val][1]}' target='_blank' class='license'>{$licdata[$val][0]}</a>";
		}

		else {
			$output = "<a href='#' class='license' onclick='return false;'>{$licdata[$val][0]}</a>";
		}

		return $output;
	}

	else {
		return $licdata;
	}
} // END function

?>