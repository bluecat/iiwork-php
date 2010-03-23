<?php

global $mini;


/**
 * 보기설정 가져오기
 * @class admin
 * @return Array
 */
function getArr() {
	global $mini;

	iss($mini['arr']);
	iss($mini['arr']['board']);
	iss($mini['arr']['member']);
	iss($mini['arr']['site']);
	iss($mini['arr_name']);
	iss($mini['arr_name']['board']);
	iss($mini['arr_name']['member']);
	iss($mini['arr_name']['site']);

	if (!empty($_SESSION['field'])) {
		$mini['arr'] = unserialize($_SESSION['field']);
	}

	// 컬럼 명 지정
		$mini['arr_name']['board'] = array(
			'no' => '번호',
			'name' => '게시판명',
			'id' => '아이디',
			'site' => '그룹',
			'skin' => '스킨',
			'total' => '게시물수',
			'date' => '생성일',
			'dir' => '파일폴더'
		);

		$mini['arr_name']['member'] = array(
			'no' => '번호',
			'uid' => '아이디',
			'name' => '닉네임',
			'site' => '그룹',
			'id_mode' => '가입구분',
			'level' => '레벨',
			'admin' => '권한',
			'real_name' => '실명',
			'confirm_jumin' => '실명인증',
			'mail' => '메일',
			'cp' => '휴대전화',
			'birth' => '생일',
			'tel' => '전화번호',
			'address' => '주소',
			'homepage' => '홈페이지',
			'sex' => '성별',
			'admit' => '승인',
			'icon_name' => '닉콘',
			'photo' => '사진',
			'status' => '상태',
			'confirm_co' => '사업자',
			'point' => '포인트',
			'point_sum' => '누적포인트',
			'money' => '적립금',
			'count_login' => '로그인수',
			'count_vote' => '추천수',
			'count_post' => '글수',
			'count_comment' => '댓글수',
			'count_recent_comment' => '댓글달린수',
			'lock_login' => '로그인실패',
			'ip' => '아이피',
			'ip_join' => '가입아이피',
			'date' => '가입일',
			'date_login' => '최근로그인'
		);

		$mini['arr_name']['site'] = array(
			'no' => '번호',
			'name' => '이름',
			'site_link' => '그룹연결',
			'date' => '가입일'
		);
} // END function


/**
 * 검색폼 출력
 * @class admin
 * @param
		$arr: searchForm설정 배열
 * @return String
 */
function printSearch($no, $arr) {
	$output = '';
	iss($arr[0]);
	iss($arr[1]);
	iss($arr[2]);
	iss($arr[3]);

	// 변수 입력
		$key = $arr[0];
		$key_name = preg_replace("/[^a-z0-9_]/i", "", $arr[0]);
		$name = $arr[1];
		$mode = $arr[2];
		$option = $arr[3];

	// 기본 출력
		$output .= "\t<div id='searchItem_{$key_name}' class='searchItem' onclick='openSearch(\"{$key}\");'>{$name}</div>\n";
		$output .= "\t<div id='searchInput_{$key_name}' class='searchInput' style='display:none;'>\n";
		$output .= "\t\t<div style='float:left;'>\n";
		$output .= "\t\t\t{$name}\n";
		$output .= "\t\t</div>\n";
		$output .= "\t\t<div style='float:right; margin-bottom:5px;'>\n";
		$output .= "\t\t\t<input type='button' value='적용' class='button' style='width: 52px; background-image:url(\"image/icon/plus.gif\");' onclick='adoptSearch(\"{$key}\");' />\n";
		$output .= "\t\t\t<input type='button' value='해제' class='button' style='width: 52px; background-image:url(\"image/icon/minus.gif\");' onclick='delSearch(\"{$key}\");' />\n";
		$output .= "\t\t\t<input type='button' value='닫기' class='button' style='width: 52px; background-image:url(\"image/icon/x_gray.gif\");' onclick='$(\"searchInput_{$key_name}\").toggle(\"hide\");' />\n";
		$output .= "\t\t</div>\n";
		$output .= "\t\t<div id='searchInputMent_{$key_name}' style='clear:both;'>\n\t\t\t";

	// 폼 출력
	switch ($mode):
		case 'text':
			$output .= "<input type='text' name='tmp[{$key}]' class='searchInputText' style='width:100%;' />";
			$output .= "
				<script type='text/javascript'>
				//<![CDATA[
					$($('searchForm').elements['tmp[{$key}]']).addEvent('keydown', function (e) {
						var event = setEvent(e);

						if (event.key == 'enter') {
							new Event(e).stop();
							adoptSearch(\"{$key}\");
							this.form.submit();
						}
					});
				//]]>
				</script>
			";
			break;

		case 'select':
			$output .= "<select name='tmp[{$key}]' class='searchInputSelect'>\n";

			$output .= getOption("
				{$option}
			");
				
			$output .= "\t\t\t</select>";
			break;

		case 'textarea':
			$output .= "<textarea name='tmp[{$key}]' class='searchInputText' style='width:100%; height:50px;'></textarea>";
			break;

		// text:value (value 생략하면 1로)
		case 'checkbox':
			iss($value);
			iss($text);
			if (strpos($option, ":") !== false) {
				$tmp = array();
				$tmp = explode(":", $option);
				$value = $tmp[1];
				$text = $tmp[0];
			}
			else {
				$value = 1;
				$text = $option;
			}

			$output .= "<input type='checkbox' name='tmp[{$key}]' value='{$value}' id='tmpSearch_{$key_name}' /> <label for='tmpSearch_{$key_name}' style='font-weight:normal;'>{$text}</label>";
			break;

		case 'date':
			$output .= "<input type='text' name='tmp[{$key}+]' class='searchInputText' style='width:92px;' /> <img src='image/icon/calendar.gif' border='0' style='vertical-align:middle; cursor:pointer;' onclick='myCal.initialize($(this).getPrevious());' alt='달력선택' /> <img src='image/icon/clock.gif' border='0' style='vertical-align:middle; cursor:pointer;' alt='오늘' onclick='$(this).getPrevious().getPrevious().value=\"".date("Y-n-j")."\";' /> ~ ";		
			$output .= "<input type='text' name='tmp[{$key}-]' class='searchInputText' style='width:92px;' /> <img src='image/icon/calendar.gif' border='0' style='vertical-align:middle; cursor:pointer;' onclick='myCal.initialize($(this).getPrevious());' alt='달력선택' /> <img src='image/icon/clock.gif' border='0' style='vertical-align:middle; cursor:pointer;' alt='오늘' onclick='$(this).getPrevious().getPrevious().value=\"".date("Y-n-j")."\";' />";

			$output .= "<div class='searchComment'><img src='image/icon/quote.gif' border='0' style='vertical-align:middle;' alt='날짜 선택 안내' /> 시작일과 종료일을 일치시키면 당일만 검색 됩니다.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;날짜는 아이콘을 클릭하시거나 2008-1-1 혹은<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2008/1/1 과 같이 입력해 주세요.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2008/1/1 13:10:00 도 가능합니다.<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<u>2008-1-1 은 2008-1-1 00:00:00</u> 입니다.</div>\n";

			$output .= "
				<script type='text/javascript'>
				//<![CDATA[
					$($('searchForm').elements['tmp[{$key}+]']).addEvent('keydown', function (e) {
						var event = setEvent(e);

						if (event.key == 'enter') {
							new Event(e).stop();
							adoptSearch(\"{$key}\");
							this.form.submit();
						}
					});

					$($('searchForm').elements['tmp[{$key}-]']).addEvent('keydown', function (e) {
						var event = setEvent(e);

						if (event.key == 'enter') {
							new Event(e).stop();
							adoptSearch(\"{$key}\");
							this.form.submit();
						}
					});
				//]]>
				</script>
			";
			break;

		case 'highlow':
			$output .= "<input type='text' name='tmp[{$key}]' class='searchInputText' style='width:220px;' />&nbsp;&nbsp;<select name='tmp_{$key}_option' class='searchInputSelect'><option value=':high:'>이상▲</option><option value=''>일치=</option><option value=':low:'>이하▼</option></select>";
			$output .= "
				<script type='text/javascript'>
				//<![CDATA[
					$($('searchForm').elements['tmp[{$key}]']).addEvent('keydown', function (e) {
						var event = setEvent(e);

						if (event.key == 'enter') {
							new Event(e).stop();
							adoptSearch(\"{$key}\");
							this.form.submit();
						}
					});
				//]]>
				</script>
			";
			break;
	endswitch;

	//$output .= "\t\t<div class='searchComment'><img src='image/icon/quote.gif' border='0' style='vertical-align:middle;' /> 적용을 누르시면 해당 항목이 활성화되면서 검색 조건에 반영 됩니다.</div>\n";

	$output .= "\n\t\t</div>\n";
	$output .= "\t</div>\n";


	return $output;
} // END function


/**
 * 빠른검색
 * @class admin
 * @param
		$str: 검색 적용할 필드명, 콤마로 구분하고 필드명에 ! 삽입시 일치 쿼리 적용
  */
function quickSearch($str, $param = '') {
	global $mini;
	$param = param($param);

	def($param['name'], 'default');
	iss($mini['list'][$param['name']]);
	iss($_GET['s']);
	iss($_GET['and']);
	iss($_GET['target']);
	iss($_GET['quick']);
	iss($_REQUEST['id']);
	iss($where);
	$is_all = 0;
	$output = '';

	$sep = $_GET['and'] ? "and" : "or ";
	$data = &$mini['list'][$param['name']];
	iss($data['where']);

	if ($_GET['quick']){

		$_GET['quick'] = convChar($_GET['quick']);
		$_GET['quick'] = str_replace("\\'", "&#39;", $_GET['quick']);
		$_GET['quick'] = str_replace("\\\\'", "'", $_GET['quick']);
		$_GET['quick'] = str_replace("\\\"", "\"", $_GET['quick']);

		$tmp = array();
		$tmp = explode(",",trim($str));

		foreach($tmp as $val):
			$val = trim($val);
			if (preg_match("/\!/", $val))
				$output .= " {$sep} ".str_replace("!", "", $val)." = '{$_GET['quick']}'";
			else
				$output .= " {$sep} {$val} LIKE '%{$_GET['quick']}%'";
		endforeach;

		if ($output) $output = substr($output, 4);
		$output = " and ({$output})";

		$data['where'] = $data['where'] ? $data['where'].$output : "WHERE ".substr($output, 4);
		$data['key'] = 0;
	}
} // END function


/**
 * 환경설정 수정
 * @class admin
 * @param
		$data: 수정할 자료, key, value를 토대로 작성한다
		$filename: 대상파일
  */
function setINI($param, $filename = '') {
	global $mini;
	$param = param($param);

	if (!$filename) $filename = "{$mini['dir']}ini.php";

	if (is_array($param)) {
		if (!file_exists($filename)) __error("[{$filename}] 이 존재하지 않습니다.");
		if (!is_writable($filename)) __error("[{$filename}] 의 쓰기 권한이 없습니다. 해당 파일/폴더의 퍼미션을 변경해 주세요.");

		// 입력 데이터 가공
			foreach ($param as $key=>$val):
				if (preg_match("/[^0-9a-z_\.]/i", $key)) {
					__error('키 값이 올바르지 않습니다');
				}

				if (preg_match("/(\r\n|\n)/is", $val)) {
					__error('환경설정에 개행 문자를 입력할 수 없습니다.');
				}

				str($param[$key], 'encode');
			endforeach;
			$keys = array_keys($param);

		// 입력
			$output = array();
			$file = getINI($filename);

			foreach ($file as $key => $val):
				if (is_array($val)) {
					$name = (!empty($val['mode']) ? "{$val['mode']}.{$val['name']}" : $val['name']);

					if (in_array($name, $keys)) {
						$val['value'] = $param[$name];
						unset($param[$name]);
					}

					iss($val['ment2']);
					iss($val['ment']);
					if ($val['ment2']) $val['ment'] .= " ; {$val['ment2']}";
					if ($val['ment']) $val['ment'] = " ; ".$val['ment'];

					$output[$key] = "{$val['name']} = \"{$val['value']}\"{$val['ment']}";
				}
				else {
					$output[$key] = $val;
				}
			endforeach;

			// 없는 것은 새로 생성
			if (is_array($param)) {
				foreach ($param as $key => $val):
					if (strpos($key, '.') === false) $output[] = "{$key} = \"{$val}\"";
				endforeach;
			}
			
			$input = implode("\n", $output);

			$fp = '';
			while(!$fp){ $fp = fopen($filename, "w+"); }
			flock($fp, LOCK_EX);
			fwrite($fp, $input);
			flock($fp, LOCK_UN);
			fclose($fp);
	}
} // END function


/**
 * 환경설정 가져오기
 * @class admin
 * @param
		$filename: 환경설정 파일
 * @return Array ([0] => (name, value, ment, ment2))
 */
function getINI($filename = '') {
	global $mini;
	$output = array();

	if (!$filename) $filename = "{$mini['dir']}ini.php";
	$file = file($filename);
	$mode = '';

	foreach ($file as $key => $val):
		$val = trim($val);
		$mat = '';
		preg_match("/^([a-z0-9_]+)\s*\=\s*\"([^\"]*)\"(.*)/is", $val, $mat);
		
		if (empty($mat[1])) {
			preg_match("/^([a-z0-9_]+)\s*\=\s*([^;]*)(.*)/is", $val, $mat);
		}

		if (!empty($mat[1])) {
			$name = trim($mat[1]);
			$value = trim($mat[2]);
			$ment = trim($mat[3]);
			$ment = preg_replace("/^;\s*/", "", $ment);
			$ment2 = '';

			if (strpos($ment, ';') !== false) {
				$tmp = explode(';', $ment);
				$ment = trim($tmp[0]);
				$ment2 = trim($tmp[1]);
			}

			if (empty($ment)) $ment = $name;
			
			$output[$key] = array('mode'=>$mode, 'name'=>$name, 'value'=>$value, 'ment'=>$ment, 'ment2'=>$ment2);
		}

		else {
			if (preg_match("/^\[/", $val)) {
				preg_match("/^\[(.+)\]/", $val, $mat);
				if (!empty($mat[1])) $mode = $mat[1];
			}
			$output[$key] = $val;
		}
	endforeach;

	return $output;
} // END function


/**
 * 관리자 권한 체크
 * @class admin
 * @param
		-site: 사이트 번호
		-board: 게시판 번호
		-mode: 허용 모드(해당 번호와 관계 없다) [site|board|admin|god]
		-type: script 모드
 */
function checkAdmin($param = '') {
	global $mini;
	$param = param($param);

	iss($param['site']);
	iss($param['board']);
	iss($param['mode']);
	def($param['type'], 'move');

	iss($mini['member']);
	iss($mini['member']['board_admin']);
	iss($mini['member']['site_admin']);
	$check = 1;

	if (empty($mini['log']))
		$check = 0;

	else {
		// 허용 모드
			if ($param['mode']) {
				switch ($param['mode']):
					case 'god':
						if (empty($mini['member']['level_admin']) || $mini['member']['level_admin'] < 4) $check = 0;
						break;
					case 'admin':
						if (empty($mini['member']['level_admin']) || $mini['member']['level_admin'] < 3) $check = 0;
						break;
					case 'site':
						if (empty($mini['member']['is_god']) && empty($mini['member']['is_admin']) && !count($mini['member']['site_admin'])) $check = 0;
						break;
					case 'board':
						if (empty($mini['member']['is_god']) && empty($mini['member']['is_admin']) && !count($mini['member']['site_admin']) && !count($mini['member']['board_admin'])) $check = 0;
						break;
				endswitch;
			}

		// 지정모드
			else {
				$check = 0;
				if (!empty($mini['member']['is_god']) || !empty($mini['member']['is_admin']))
					$check = 1;
				if ($param['site'] && in_array($param['site'], $mini['member']['site_admin']))
					$check = 1;
				if ($param['board'] && in_array($param['board'], $mini['member']['board_admin']))
					$check = 1;
			}
	}

	// 처리
		if (!$check) {
			__error(array(
				'msg' => '권한이 없습니다',
				'mode' => $param['type'],
				'url' => "{$mini['dir']}login.php?url=".url('','','reload=1')
			));
		}
} // END function


/**
 * 권한 폼 출력 함수
 * @class admin
 * @param
		$text: 항목 제목
		$name: 변수명
		$ment: 마지막에 출력할 문구
  */
function setPermit($text, $name, $ment = '') {
	echo "<tr>
		<td class='contentLeft' nowrap='nowrap'>{$text}</td>
		<td id='td_permit_{$name}' class='contentRight kor_s'>
			<input type='hidden' name='permit_{$name}' />

			{$ment}

			<span class='word'><a href='#' onclick=\"addPermit({ 'name':'{$name}', 'mode':'=', 'value':'board', 'field':'admin' }); return false;\">관리자권한추가</a></span><span class='word'><a href='#' onclick='copyPermit($(this).getParent().getParent().getLast()); return false;'>복사</a></span><span class='word'><a href='#' onclick='pastePermit($(this).getParent().getParent().getLast()); return false;'>붙여넣기</a></span><span class='word'><a href='#' onclick='if (confirm(\"해당 권한을 모두 삭제합니다\")) { $(this).getParent().getParent().getLast().innerHTML = \"\"; } return false;'>모두삭제</a></span>
			
			<div id='div_permit_{$name}' class='permitdiv'>
			<input type='radio' name='permit_{$name}_and' value='1' id='labelpermit_{$name}_and1' class='middle' /> <label for='labelpermit_{$name}_and1'>모두같다(AND)</label>
			<input type='radio' name='permit_{$name}_and' value='0' id='labelpermit_{$name}_and0' class='middle' /> <label for='labelpermit_{$name}_and0'>한개이상(OR)</label>
			<br />

			<select name='tmp_permit_field' class='formSelect' onchange='changePermit(this)'>
				<option value='level'>레벨</option>
				<option value='admin'>특별권한</option>
				<option value='date'>가입일</option>
				<option value='date_login'>최근로그인</option>
				<option value='site'>그룹</option>
				<option value='no'>회원번호</option>
				<option value='point'>가용포인트</option>
				<option value='point_sum'>누적포인트</option>
				<option value='confirm_cp'>휴대전화인증</option>
				<option value='confirm_mail'>메일인증</option>
				<option value='confirm_co'>사업자인증</option>
				<option value='sex'>성별</option>
				<option value='age'>나이</option>
				<option value='money'>적립금</option>
				<option value='count_login'>로그인수</option>
				<option value='count_vote'>추천받은수</option>
				<option value='count_post'>글쓴수</option>
				<option value='count_comment'>댓글쓴수</option>
				<option value='count_recent_comment'>댓글달린수</option>
			</select>이
			<span class='tmp_insertPermit' style='vertical-align:middle;'></span>값과
			<select name='tmp_permit_mode' class='formSelect' onblur='rulePermit(this);'>
				<option value='='>같다</option>
				<option value='>='>이상</option>
				<option value='&lt;='>이하</option>
				<option value='!='>다르다</option>
			</select>
			<input type='button' id='permitButton_{$name}' value='추가' class='button' style='background-image:url(\"image/icon/plus.gif\"); width:50px;' onclick='addPermit({ target:this });' />
			</div>

			<div id='permit_{$name}'></div>
		</td>
	</tr>";
} // END fundtion


/**
 * permit tool
 * @class admin
 */
function scriptPermit() {
	global $mini, $option_site;
	?>
	<!-- tmp 폼들 -->
	<div id='formPermit' style='display:none;'>
		<input type='text' name='tmp_permit_value' class='formText' style='width: 60px;' />
	</div>
	<div id='formPermit_no' style='display:none;'>
		<input type='text' id='ajaxPermitMember' name='tmp_permit_value' class='formText' style='width: 60px;' />
	</div>
	<div id='formPermit_confirm_cp' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'><option value='0'>미인증</option><option value='1'>인증완료</option></select>
	</div>
	<div id='formPermit_confirm_mail' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'><option value='0'>미인증</option><option value='1'>인증완료</option></select>
	</div>
	<div id='formPermit_confirm_co' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'><?php echo getOption("str:[미인증:][확인요청:request][확인완료:ok][거부:denied]"); ?></select>
	</div>
	<div id='formPermit_sex' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'><option value='man'>남자</option><option value='woman'>여자</option></select>
	</div>
	<div id='formPermit_level' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'>
			<?php echo getLevel(); ?>
		</select>
	</div>
	<div id='formPermit_site' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'><?php echo $option_site; ?></select>
	</div>
	<div id='formPermit_admin' style='display:none;'>
		<select name='tmp_permit_value' class='formSelect'>
			<option value='god'>최고관리자</option>
			<option value='admin'>총관리자</option>
			<option value='site'>그룹관리자</option>
			<option value='board'>게시판관리자</option>
		</select>
	</div>
	<div id='formPermit_date' style='display:none;'>
		<input type='text' name='tmp_permit_value' class='formText' style='width: 60px;' />
		<img src='image/icon/calendar.gif' border='0' style='vertical-align:middle; cursor:pointer;' onclick='myCal.initialize($(this).getPrevious());' alt='달력선택' /> <img src='image/icon/clock.gif' border='0' style='vertical-align:middle; cursor:pointer;' alt='오늘' onclick='$(this).getPrevious().getPrevious().value="<?php echo date("Y-n-j"); ?>";' />
	</div>
	<div id='formPermit_date_login' style='display:none;'>
		<input type='text' name='tmp_permit_value' class='formText' style='width: 60px;' />
		<img src='image/icon/calendar.gif' border='0' style='vertical-align:middle; cursor:pointer;' onclick='myCal.initialize($(this).getPrevious());' alt='달력선택' /> <img src='image/icon/clock.gif' border='0' style='vertical-align:middle; cursor:pointer;' alt='오늘' onclick='$(this).getPrevious().getPrevious().value="<?php echo date("Y-n-j"); ?>";' />
	</div>
	<?php
} // END function


/**
 * 
 * @class 
 * @param
		$name: description
 * @return 
 */
function setUse($text, $name, $ment = '') {
	echo "<tr>
		<td class='contentLeft' nowrap='nowrap'>{$text}</td>
		<td class='contentRight kor_s'>
			<input type='radio' name='{$name}' value='1' id='label{$name}1' /> <label for='label{$name}1'>사용</label>
			<input type='radio' name='{$name}' value='0' id='label{$name}0' /> <label for='label{$name}0'>사용하지 않음</label>
			{$ment}
		</td>
	</tr>";
} // END function


/**
 * 레벨 Option 뽑기
 * @class io
 * @param
		$name: description
 * @return 
 */
function getLevel($site_data) {
	global $mini;

	if (!empty($mini['site'])) $site_data = $mini['site'];

	$output = '';

	for ($a = 1; $a <= 50; $a++):
		unset($mat);

		if (!empty($site_data['level_name']))
			preg_match("/\[".$a."\:([^\]]+)\]/", $site_data['level_name'], $mat);
		
		$output .= (!empty($mat[1]) ? "[{$a} {$mat[1]}:{$a}]" : "[{$a}]");
	endfor;

	return getOption("str:{$output}");
} // END function
?>