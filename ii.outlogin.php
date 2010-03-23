<?php

/** 외부로그인
 * @class io 
 * @param
		-skin: 로그인되어 있을 떄 스킨
		-skin_not: 로그인이 되어있지 않을 때 스킨
		-id: 스킨 적용할 게시판 번호 or 아이디
		-group: 스킨 적용할 그룹 번호
		-skip_style: 기본 스타일 로드를 스킵할지 선택 합니다.
 * @return Array 스킨이 있을 경우엔 리턴 안됨
 */
function outlogin($param) {
	global $mini;
	$param = param($param);
	$skin = '';

	// 없을 경우 기본 그룹으로 지정
	if (empty($param['id']) && empty($param['group'])) $param['group'] = 1;

	$q = '';
	if (!empty($param['id'])) {
		$q = "id={$param['id']}";
		$data_board = getBoard($param['id'], 1);
		$data_site = getSite($data_board['site'], 1);
	}
	if (!empty($param['group'])) {
		$q = "group={$param['group']}";
		$data_site = getSite($param['group'], 1);
	}

	// 로그인 되어 있을 떄
	if (!empty($mini['log'])) {
		$val = &$mini['member'];
		$val = array_merge($val, array(
			'url_logout' => "{$mini['dir']}login.php?mode=logout&amp;url=".url(),
			'url_myinfo' => "{$mini['dir']}member.php?mode=modify&amp;no={$mini['member']['no']}&amp;{$q}&amp;url=".url(),
			'url_mymenu' => "{$mini['dir']}mymenu.php?mode=memo&amp;no={$mini['member']['no']}&amp;{$q}&amp;url=".url(),
			'url_admin' => "{$mini['dir']}admin/",
			'js_back' => "onclick='history.back();'",
			'js_back2' => "onclick='history.go(-2);'",
			'is_admin' => !empty($mini['member']['level_admin']),
			'is_god' => (!empty($mini['member']['level_admin']) && $mini['member']['level_admin'] == 4),
			'date' => $mini['date'],
			'ip' => $mini['ip']
		));

		if (!empty($val['url_myinfo'])) $val['pop_myinfo'] = "iiPopup.init({ url: \"{$val['url_myinfo']}\", width:iiSize[\"myinfo\"][0], height:iiSize[\"myinfo\"][1], close: true });";
		if (!empty($val['url_mymenu'])) $val['pop_mymenu'] = "iiPopup.init({ url: \"{$val['url_mymenu']}\", width:iiSize[\"mymenu\"][0], height:iiSize[\"mymenu\"][1] });";

		if (!empty($param['skin'])) $skin = $param['skin'];
	}

	else {
		$val = array(
			'form_start' => "
				<form id='form_login' name='form_login' action='{$mini['dir']}login.php?url=".url()."' method='post'>
				<input type='hidden' name='mode' value='login' />
			",
			'form_end' => "</form>
				<script type='text/javascript' src='{$mini['dir']}js/mootools.js'></script>
				<script type='text/javascript' src='{$mini['dir']}js/ii.js'></script>
				<script type='text/javascript' src='{$mini['dir']}js/ii.form.js'></script>
				<script type='text/javascript' src='{$mini['dir']}js/size.js'></script>
				<script type='text/javascript' src='{$mini['dir']}js/md5.js'></script>
				<script type='text/javascript' src='{$mini['dir']}js/sha1.js'></script>
				<script type='text/javascript'>
				//<![CDATA[
					var miniDir = '{$mini['dir']}';
					var secure_pass = '{$data_site['secure_pass']}';
					var ip = '{$mini['ip']}';
					var session_id = Cookie.get('".ini_get('session.name')."');


//					var form_outlogin = $('outlogin');
//					form_outlogin.submitAction = function () {
//						error(\"랄라\");
//						return false;
//					};
//					form_outlogin.setForm();
				//]]>
				</script>
				<script type='text/javascript' src='{$mini['dir']}js/mini.login.js'></script>
			",
			'form_autologin' => "<input type='checkbox' name='autologin' value='1' id='outlogin_autologin' />",
			'url_login' => "{$mini['dir']}login.php?{$q}&amp;url=".url(),
			'url_join' => "{$mini['dir']}member.php?{$q}&amp;url=".url(),
			'url_find' => "{$mini['dir']}login.find.php?{$q}&amp;url=".url(),
			'date' => $mini['date'],
			'ip' => $mini['ip']
		);

		if (!empty($val['url_find'])) $val['pop_find'] = "iiPopup.init({ url: \"{$val['url_find']}\", width:iiSize[\"find\"][0], height:iiSize[\"find\"][1] });";
		$val['pop_login'] = "iiPopup.init({ url: \"{$val['url_login']}\", width:iiSize[\"login\"][0], height:iiSize[\"login\"][1] });";
		$val['pop_join'] = "iiPopup.init({ url: \"{$val['url_join']}\", width:iiSize[\"join\"][0], height:iiSize[\"join\"][1], close: true });";

		if (!empty($param['skin_not'])) $skin = $param['skin_not'];
	}

	urlToLink($val);

	// 변환
	if (!empty($skin)) {
		$preg_left = $preg_right = array();
		$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+):\]/ie";
		$preg_right[] = "\$val['\\1']['\\2']['\\3']";
		$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+):\]/ie";
		$preg_right[] = "\$val['\\1']['\\2']";
		$preg_left[] = "/\[:([a-z0-9_]+):\]/ie";
		$preg_right[] = "\$val['\\1']";
		$skin = preg_replace($preg_left, $preg_right, $skin);
		echo $skin;
	}
	else {
		return $val;
	}
} // END function
?>