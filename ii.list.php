<?php

/** 검색 처리
 * @class list
 * @param
		-name: 리스팅 변수배열 이름
		-is_simple: key table 사용 여부
		-where_and: 기본 and 검색조건
		-where: 기본 검색조건
		-other: 뒤에 들어갈 절
		-quickName: quick 검색 변수명
		-sName: 검색 변수명
		-andName: and 검색 변수명
  */
function setSearch($param = '') {
	global $mini;
	$param = param($param);

	/*
		리스팅 변수배열 멤버변수
		table
		keyTable
		list
		start
		div
		nowDiv
		key
		is_total
		fieldName
		where
		order
		order_desc

		검색 조건에 따라 total 이 들어가야 한다
	*/

	def($param['name'], 'default');
	def($mini['list'][$param['name']], '');
	def($_REQUEST['target'], '');
	def($_REQUEST['id'], '');
	def($param['quickName'], 'quick');
	def($param['sName'], 's');
	def($param['andName'], 'and');
	def($_REQUEST[$param['sName']], '');

	$where = $where_and = $both = '';
	$is_all = 0;
	$sep = !empty($_REQUEST[$param['andName']]) ? "and" : "or ";
	$data = &$mini['list'][$param['name']];

	def($data['key'], 0);
	def($data['is_total'], 0);

	$s = array();
	if (!empty($param['other'])) $data['other_query_after'] = $param['other'];

	//// 기본 검색조건 적용
		if (!empty($param['where_and'])) {
			$where_and .= " and {$param['where_and']}";
		}
		if (!empty($param['where'])) {
			$where .= " {$sep} {$param['where']}";
		}


	//// 모드 없는 검색 설정
		if (!empty($_REQUEST[$param['quickName']]) && empty($param['is_simple'])) {
			$s = array(
				'title' => $_REQUEST[$param['quickName']],
				'ment' => $_REQUEST[$param['quickName']],
				'name' => $_REQUEST[$param['quickName']],
				'tag' => $_REQUEST[$param['quickName']]
			);
		}

	//// 카테고리
		else if (!empty($_REQUEST['category']) && empty($_REQUEST[$param['sName']])) {
			$s = array('category!' => $_REQUEST['category']);
		}

	//// 일반		
		if (!empty($_REQUEST[$param['sName']])) {
			$s = array_merge($s, $_REQUEST[$param['sName']]);
		}

	//// 검색 루프 시작
		if (!empty($s) && is_array($s)) {
			// 키 테이블만 사용할 수 있는 조건인지 확인(PHP5 에서는 array_diff_key로 한번에 해결, 4.0.4에서 array_diff가 깨질 수 있음!)
				$tmp_keys = "[".implode("][", array_keys($s))."]";
				$tmp_keys = str_replace(array('!', '^', '$', '@', '+', '-', '*', '~'), '', $tmp_keys);
				$is_key = array_diff(getStr($tmp_keys), array('category', 'tag', 'title', 'ment', 'target_member', 'name')) || !empty($param['is_simple']) ? 0 : 1;

			foreach ($s as $key=>$val):
				// 조건 뽑기
					preg_match("/(\+|\-|\@|\^|\!|\$|\~)$/i", $key, $mat);
					$is_special = preg_match("/^\@/i", $key);
					$key = str_replace(array('@', '^', '!', '$', '+', '-', '*', '~'), '', trim($key));
					$option = $mat[1];

				// 검색어 언어셋 변경
					$val = convChar($val);
					$val = str_replace("\\'", "&#39;", $val);
					$val = str_replace("\\\\'", "'", $val);
					$val = str_replace("\\\"", "\"", $val);

				// 검색어 쪼개기
//					if (empty($param['is_simple']) && (!empty($_REQUEST[$param['quickName']]) || (!empty($_REQUEST[$param['sName']]) && count($_REQUEST[$param['sName']]) == 1))) {
//						$val_arr = array();
//						$val_arr = getIndex($val, 'search');
//						$count_val_arr = count($val_arr);
//					}

					$val_arr = array();
					if ($key != 'ip' && $key != 'date' && $key != 'target_member' && $key != 'name') {
						$val_arr = getIndex($val, 'search');
					}
					else {
						$val_arr = array($val);
					}
					$count_val_arr = count($val_arr);

				// 특수검색(@모드)
					if ($is_special) {
						switch ($key):
							// 모든 게시판에서 검색
							case 'all':
								$is_all = 1;
								break;
		/*
							case 'date':
								break;
							case 'private':
								break;
		*/
							default:
								__error("정의되지 않은 특별검색 입니다");
						endswitch;
					}

				// 검색테이블 사용
					if ($is_key) {
						$data['key'] = 1;
						
						$tmp_q = empty($_REQUEST['is_cmt']) ? " and cmt_no=0" : "";						
						if ($key == 'target_member' && preg_match("/[^0-9]/", $val)) continue;
						$tmp_sep = ($option == '~') ? " and" : " or ";
						$where_name = ($option == '~') ? "where_and" : "where";

						if ($option == '!') {
							foreach ($val_arr as $key2=>$val2):
								$$where_name .= ($key == 'target_member') ? "{$tmp_sep} (target_member={$val2}{$tmp_q})" : "{$tmp_sep} (mode='{$key}'{$tmp_q} and ment='{$val2}')";
							endforeach;

							// 총 게시물 수를 저장한 검색조건이라면 전체 검색을 할 수 있게 is_total 변수를 지정한다
							if ($key == 'category' && count($s) == 1) {
								$data['is_total'] = 1;
								$data['key'] = 1;
								$data['is_only_category'] = 1;

//								if (!isset($mini['board']['total'][$key][$val])) 
//									__error("존재하지 않는 {$key} 입니다.");
								if (isset($mini['board']['total'][$key][$val])) 
									$data['total'] = $mini['board']['total'][$key][$val];
								else 
									$data['total'] = 0;
							}
						}
						else {
							foreach ($val_arr as $key2=>$val2):
								$$where_name .= ($key == 'target_member') ? "{$tmp_sep} (target_member={$val2}{$tmp_q})" : "{$tmp_sep} (mode='{$key}'{$tmp_q} and ment LIKE '{$val2}%')";
							endforeach;
						}
					}

				// 테이블 직접 검색
					else {
						$tmp_sep = ($option == '~') ? "and" : $sep;
						$where_name = ($sep == 'and') ? "where_and" : "where";

						// +- 가 동시에 적용될 경우 두개는 and로 묶기(date between)
						if ($option == '-' || $option == '+') {
							if (isset($s["{$key}-"]) && isset($s["{$key}+"])) {
								$$where_name .= "{$tmp_sep} ({$key} <= '{$s[$key.'-']}' and {$key} >= '{$s[$key.'+']}')";
								$both .= "[{$key}]";
							}
						}

						// :keyword: 검색 적용(high, low)
						if (preg_match("/:[a-z]+:$/i", $val)) {
							$mat = array();
							preg_match("/:([a-z]+):$/i", $val, $mat);
							$val = preg_replace("/:[a-z]+:/i", "", $val);

							switch ($mat[1]):
								case 'high':
									if ($val !== '')
										$$where_name .= " {$tmp_sep} {$key} >= '{$val}'";
									break;

								case 'low':
									if ($val !== '')
										$$where_name .= " {$tmp_sep} {$key} <= '{$val}'";
									break;
							endswitch;
						}

						// 일반 검색
						else {
							if (is_array($val_arr))
							foreach ($val_arr as $key2=>$val2):
								switch ($option):
									case '!':
										$$where_name .= " {$tmp_sep} {$key}='{$val2}'";
										break;

									case '^':
										if ($val2 !== '')
											$$where_name .= " {$tmp_sep} {$key} LIKE '{$val2}%'";
										break;

									case '$':
										if ($val2 !== '')
											$$where_name .= " {$tmp_sep} {$key} LIKE '%{$val2}'";
										break;

									case '*':
										if ($val2 !== '')
											$$where_name .= " {$tmp_sep} {$key} LIKE '%[{$val2}]%'";
										break;

									case '+':
										if ($val2 !== '' && !inStr($key, $both))
											$$where_name .= " {$tmp_sep} {$key} >= '{$val2}'";
										break;

									case '-':
										if ($val2 !== '' && !inStr($key, $both))
											$$where_name .= " {$tmp_sep} {$key} <= '{$val2}'";
										break;

									default:
										if ($val2 !== '')
											$$where_name .= " {$tmp_sep} {$key} LIKE '%{$val2}%'";
								endswitch;
							endforeach;
						}
					}
			endforeach;
		}

	//// 검색 조건이 있을 떄
		if ($where || $where_and) {
			// and와 합침
			if ($where && $where_and) {
				$where = " and (".substr($where, 4)."){$where_and}";
			}
			else if (!$where && $where_and) {
				$where = $where_and;
			}

			if (!empty($is_key)) {
				// 다중 게시판 검색 시(총 게시물 수가 없어야 가능)
					if (!empty($_REQUEST['target']) && !$data['is_total']) {
						$tmp = array();
						$tmp = explode(",", trim($_REQUEST['target']));
						$tmp_where = '';

						foreach ($tmp as $key=>$val):
							$val = trim($val);
							if ($val && !preg_match("/[^0-9]/", $val)) {
								$tmp_where .= " or id={$val}";
							}
						endforeach;

						if ($tmp_where) $where = " and (".substr($tmp_where, 3).") and (".substr($where, 4).")";

					}
				
				// 단일 게시판 검색
					else if ($_REQUEST['id'] && (!$is_all || $data['is_total'])) {
						$where = " and id='{$mini['board']['no']}' and (".substr($where, 4).")";
					}
			}

			$data['where'] = "WHERE ".substr($where, 4);

			if (!empty($is_key) && !empty($_REQUEST[$param['andName']]) && !empty($count_val_arr)) {
				$data['where'] .= " GROUP BY num HAVING count(num) >= {$count_val_arr}";
				$data['is_group'] = 1;
			}
		}

	//// 검색 조건이 없으면 전체 검색을 지정한다
		else {
			$data['is_total'] = 1;
		}
} // END function


/** 정렬 처리
 * @class list
 * @param
		-name: 리스팅 배열 이름
		-fieldName: 필드명
		-table: 테이블 명
		-order: 기본 정렬
		-order_desc: 기본 역정렬
		-sortName: sort 변수 이름
 * @return 
 */
function setSort($param = '') {
	global $mini;
	$param = param($param);

	def($mini['board']['table'], '');
	def($param['order'], '');
	def($param['order_desc'], '');
	def($param['name'], 'default');
	def($param['fieldName'], 'num');
	def($param['table'], $mini['board']['table']);
	def($mini['set']['use_sort'], 1);
	def($mini['set']['use_sort_only_key'], 1);
	def($param['sortName'], 'sort');
	def($mini['list'][$param['name']], '');
	def($_REQUEST[$param['sortName']], '');
	
	$output = $output_desc = '';

	$data = &$mini['list'][$param['name']];
	def($data['key'], '');
	$data['fieldName'] = $param['fieldName'];
	$data['table'] = $param['table'];

	if($_REQUEST[$param['sortName']]){
		$tmp = array();
		$tmp = explode(",",trim($_REQUEST[$param['sortName']]));

		// INDEX 구하기
		$tmp2 = array();
		$keys = array();
		$tmp2 = sql("q:SHOW INDEX FROM {$data['table']}, mode:array");
		foreach ($tmp2 as $key=>$val):
			$keys[] = $val['Key_name'];
		endforeach;

		if (is_array($tmp))
		foreach ($tmp as $val):
			$val = trim($val);

			if ($mini['set']['use_sort_only_key']) {
				if (!in_array(str_replace("!", "", trim($val)), $keys))
					__error("[{$val}] 정렬할 수 없는 필드명 입니다.");
			}

			if (preg_match("/\!/is",$val)) {
				$output .= ",".str_replace("!", "", $val)." DESC";
				$output_desc .= ",".str_replace("!", "", $val)." ASC";
			}
			else {
				$output .= ",{$val} ASC";
				$output_desc .= ",{$val} DESC";
			}
		endforeach;

		if ($output) $output = "ORDER BY ".substr($output, 1);
		if ($output_desc) $output_desc = "ORDER BY ".substr($output_desc, 1);
	}

	// 기본 정렬 적용
	if (!$output || !$output_desc || ($data['key'] && $data['where']) || !$mini['set']['use_sort'] || ($param['order'] && $param['order_desc'])) {
		$data['is_order'] = 1;

		if ($param['order'] && $param['order_desc']) {
			$data['order'] = $param['order'];
			$data['order_desc'] = $param['order_desc'];
		}
		else {
			$data['order'] = "ORDER BY {$data['fieldName']}";			
			$data['order_desc'] = "ORDER BY {$data['fieldName']} DESC";
		}
	}
	else {
		$data['is_order'] = 0;
		$data['order'] = $output;
		$data['order_desc'] = $output_desc;
	}
} // END function


/** 리스트 준비
 * @class list
 * @param
		-table: 리스팅 할 테이블
		-total: 총 게시물 수
		-list: 한 페이지에 표시할 게시물 수
		-name: 리스팅 변수배열 이름
		-pageName: 페이지 변수 이름
		-divName: div 변수 이름
		-startName: start 변수 이름
		-fieldName: 뽑아올 필드 이름
		-keyTable: 검색 키 테이블 이름
		-other_query_after:
		-other_query_field:
 * @return Array 게시물
 */
function getList($param = '') {
	global $mini;
	$param = param($param);

	def($param['name'], 'default');
	iss($output);
	iss($mini['list'][$param['name']]);
	iss($mini['board']['table']);
	iss($mini['board']['list_count']);
	def($param['pageName'], 'page');
	def($param['divName'], 'div');
	def($param['startName'], 'start');
	def($param['fieldName'], 'num');
	def($param['table'], $mini['board']['table']);
	def($param['keyTable'], $mini['name']['search']);
	def($param['list'], $mini['board']['list_count']);
	def($mini['set']['search_limit'], 1000);
	def($mini['set']['sort_limit'], 5000);
	iss($param['total']);
	iss($_REQUEST[$param['pageName']]);
	iss($_REQUEST[$param['divName']]);
	iss($_REQUEST[$param['startName']]);

	$data = &$mini['list'][$param['name']];
	
	// request 값 설정
	iss($data['where']);
	def($data['order'], "ORDER BY {$param['fieldName']}");
	def($data['order_desc'], "ORDER BY {$param['fieldName']} DESC");
	iss($data['is_total']);
	iss($data['other_query_field']);
	iss($data['other_query_after']);
	iss($param['other_query_after']);
	iss($param['other_query_field']);
	iss($data['is_order']);
	def($data['is_more'], 0);
	iss($mini['board']['total']['default']);
	def($data['total'], $param['total']);
	if (!$data['where']) def($data['total'], $mini['board']['total']['default']);
	def($data['other_query_field'], $param['other_query_field']);

	$data['page'] = $_REQUEST[$param['pageName']];
	$data['div'] = $_REQUEST[$param['divName']];
	$data['start'] = $_REQUEST[$param['startName']];
	$data['list'] = $param['list'];
	$data['fieldName'] = $param['fieldName'];
	$data['pageName'] = $param['pageName'];
	$data['startName'] = $param['startName'];
	$data['divName'] = $param['divName'];
	$data['table'] = $param['table'];
	$data['keyTable'] = $param['keyTable'];
	$data['searchTable'] = $data['key'] ? $data['keyTable'] : $data['table'];
	$data['is_simple'] = 0;

	// 변수 체크
	if (!check($data['page'], "type: num") || $data['page'] < 0) 
		$data['page'] = 1;
	if (!check($data['total'], "type: num") || $data['total'] < 0)
		$data['total'] = 0;
	if (!check($data['div'], "type: num") || $data['div'] < 0)
		$data['div'] = '';
	if (!check($data['start'], "type: num") || $data['start'] < 0)
		$data['start'] = '';
	if (!check($data['list'], "type: num") || $data['list'] < 1)
		$data['list'] = 1;


	// 정렬 사용시 총 게시물 수 제한
	if ($data['total'] > $mini['set']['sort_limit']) {
		$data['is_more'] = 1;
		$data['total'] = $mini['set']['sort_limit'];
	}

	// 총 게시물 수를 구해야 할 떄
	if ($data['is_total'] && !$data['total'] && empty($data['is_only_category']) && !$param['total']) {
		$data['total'] = sql("SELECT COUNT(*) FROM {$data['table']} {$data['where']} {$data['other_query_after']}");

		if (is_array($data['total'])) {
			$data['total'] = count($data['total']);
/*
			$tmp_sum = 0;
			foreach ($data['total'] as $val):
				$tmp_sum += (int)(end($val));
			endforeach;
			unset($data['total']);
			$data['total'] = $tmp_sum;
*/
		}
	}
		
	// 총 게시물 수가 없을 떄
	if (!$data['is_total']) {
		iss($_REQUEST['total']);
		$data['total'] = $_REQUEST['total'] ? $_REQUEST['total'] : $data['total'];
		$tmp_data = array();

		// 총 게시물 수 구하기
		if (!$data['total']) {
			if (!empty($data['key']))
//				$data['debug_total'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['searchTable']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order']} LIMIT ".($mini['set']['search_limit']+1);
				$data['debug_total'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['searchTable']} {$data['where']} {$data['order']} LIMIT ".($mini['set']['search_limit']+1);
			else
//				$data['debug_total'] = "SELECT {$data['fieldName']} FROM {$data['searchTable']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order']} LIMIT ".($mini['set']['search_limit']+1);
				$data['debug_total'] = "SELECT {$data['fieldName']} FROM {$data['searchTable']} {$data['where']} {$data['order']} LIMIT ".($mini['set']['search_limit']+1);

			checkTime("query_total");
			$tmp_data = sql("
				q: {$data['debug_total']}
				mode: array
			");
			checkTime("query_total");
		
			// 검색 결과가 더 있으면
			$count = count($tmp_data);

			if ($count > $mini['set']['search_limit']) {
				$data['total'] = $_REQUEST['total'] = $count - 1;
				$data['is_more'] = 1;
			}
			else {
				$data['total'] = $_REQUEST['total'] = $count;
			}
		}
	}

	// 마지막 페이지 구하기
	$data['tp'] = $data['total'] ? ceil($data['total'] / $data['list']) : 1;
	if ($data['page'] > $data['tp']) $data['page'] = $data['tp'];

	// page 건너띄기 막기
	if ($data['is_order'] && ($data['page'] != $data['tp'] && (!$data['div'] || !$data['start']))) {
		$data['page'] = 1;
	}

	// 현재 DIV 구하기
	$data['nowDiv'] = ceil($data['page'] / 10);

	// 지정 정렬일 때는 오래 걸리는 방식을 취한다
	if ($data['is_order'] == 0) {
		$data['v'] = ($data['page'] - 1) * $data['list'];
		$data['debug'] = "SELECT *{$data['other_query_field']} FROM {$data['table']} {$data['where']} {$data['other_query_after']} {$data['order']} LIMIT {$data['v']}, {$data['list']}";

		$output = array();
		checkTime("query");
		$output = sql("
			q: {$data['debug']}
			mode: array
		");
		checkTime("query");
	}

	else {
		// 순서 구하기
		$data['v'] = (($data['page'] - (($data['nowDiv'] - 1) * 10)) - 1) * $data['list'];

		// 초기화(순차가 아닐 때, 값이 없을 때, div가 1일 때(새 글 때문에))
		if (!$data['start'] || !$data['div'] || $data['page'] == $data['tp'] || ($data['div'] == 1 && $data['div'] != $data['nowDiv']-1 && $data['div'] != $data['nowDiv'] + 1)) {

			// 뽑아올 순서 구하기
			$tmp_limit_page_count = floor($data['page']-1) / 10 * 10;
			$tmp_limit_count = $data['v'] + $data['total'] - ($tmp_limit_page_count * $data['list']);

			if ($tmp_limit_count == 0) {
				$tmp_limit = 10 * $data['list'] - 1;
			}
			else {
				$tmp_limit = $tmp_limit_count;
			}

			// 키 검색일 떄
			if ($data['key']) {
		//		$data['debug_init_last'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order_desc']} LIMIT {$tmp_limit}, 1";
		//		$data['debug_init_first'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order']} LIMIT 1";
				
				$data['debug_init_last'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} {$data['where']} {$data['order_desc']} LIMIT {$tmp_limit}, 1";
				$data['debug_init_first'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} {$data['where']} {$data['order']} LIMIT 1";
			}

			// 키 검색이 아닐 떄
			else {
		//		$data['debug_init_last'] = "SELECT {$data['fieldName']} FROM {$data['table']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order_desc']} LIMIT {$tmp_limit}, 1";
		//		$data['debug_init_first'] = "SELECT {$data['fieldName']} FROM {$data['table']} USE KEY ({$data['fieldName']}) {$data['where']} {$data['order']} LIMIT 1";

				$data['debug_init_last'] = "SELECT {$data['fieldName']} FROM {$data['table']} {$data['where']} {$data['order_desc']} LIMIT {$tmp_limit}, 1";
				$data['debug_init_first'] = "SELECT {$data['fieldName']} FROM {$data['table']} {$data['where']} {$data['order']} LIMIT 1";
			}

			// 마지막 페이지 초기화
			if ($data['page'] == $data['tp'] && $data['nowDiv'] > 1) {
				checkTime("query_init_last");
				$data['start'] = sql($data['debug_init_last']);
				checkTime("query_init_last");

				$data['page'] = $data['tp'];
			}

			// 첫 페이지 초기화
			else {
				checkTime("query_init_first");
				$data['start'] = sql($data['debug_init_first']);
				checkTime("query_init_first");

				if ($data['page'] > 10) $data['page'] = 1;
				$data['nowDiv'] = 1;

				// 순서 다시 구하기
				$data['v'] = (($data['page'] - (($data['nowDiv'] - 1) * 10)) - 1) * $data['list'];
			}
		}

		// 키 검색일 때는 따로 구하기
		if ($data['key'] && $data['start']) {
			$tmp_data = array();
			$tmp_query = '';
			
			checkTime("query_get_key");
			
			//use key 뺐다
			//$data['debug_key'] = "SELECT {$data['fieldName']}, id{$data['other_query_field']} FROM {$param['keyTable']} USE KEY ({$data['fieldName']}) WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} and ".str_replace("WHERE ", "", $data['where']).(empty($data['is_group']) ? " GROUP BY {$data['fieldName']}":"")." {$data['order']} LIMIT {$data['v']}, {$data['list']}";
			$data['debug_key'] = "SELECT {$data['fieldName']}, id{$data['other_query_field']} FROM {$param['keyTable']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} and ".str_replace("WHERE ", "", $data['where']).(empty($data['is_group']) ? " GROUP BY {$data['fieldName']}":"")." {$data['order']} LIMIT {$data['v']}, {$data['list']}";
			$tmp_data = sql("
				q: {$data['debug_key']}
				mode: array
			");

			checkTime("query_get_key");

			// 순서대로 뽑아서 쿼리하기
			$output = array();

			checkTime("query");
			for ($a=0; $a<$data['list']; $a++):
				iss($tmp_data[$a]);
				if ($tmp_data[$a]) {
					$output[] = sql("SELECT * FROM {$mini['name']['board']}{$tmp_data[$a]['id']} WHERE {$data['fieldName']}={$tmp_data[$a]['num']}");
				}
			endfor;
			checkTime("query");
		}

		else if ($data['total'] && $data['start']) {
			$output = array();

			$data['debug'] = "SELECT *{$data['other_query_field']} FROM {$data['table']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['other_query_after']} {$data['order']} LIMIT {$data['v']}, {$data['list']}";

			checkTime("query");
			$output = sql("
				q: {$data['debug']}
				mode: array
			");
			checkTime("query");
		}
	}

	// 변수 반환
	$_GET[$data['divName']] = $data['nowDiv'];
	$_GET[$data['startName']] = $data['start'];
	$_GET[$data['pageName']] = $data['page'];

	return $output;
} // END function


/** 리스트 준비 (간편 알고리즘)
 * @class list
 * @param
		-table: 대상 테이블명
		-list: 목록 수
		-pageName: 페이지 변수명
 * @return 
 */
 /*
function getListSimple($param = '') {
	global $mini;
	$param = param($param);

	def($param['name'], 'default');
	iss($output);
	iss($mini['list'][$param['name']]);
	def($param['pageName'], 'page');
	iss($_REQUEST[$param['pageName']]);

	$data = &$mini['list'][$param['name']];
	
	// request 값 설정
	iss($data['where']);

	$data['page'] = $_REQUEST[$param['pageName']];
	$data['list'] = $param['list'];
	$data['table'] = $param['table'];
	$data['pageName'] = $param['pageName'];
	$data['divName'] = $param['divName'];
	$data['startName'] = $param['startName'];
	$data['divName'] = $param['divName'];
	$data['is_simple'] = 1;

	// 변수 체크
	if (!check($data['page'], "type: num") || $data['page'] < 0) 
		$data['page'] = 1;
	if (!check($data['total'], "type: num") || $data['total'] < 0)
		$data['total'] = 0;
	if (!check($data['list'], "type: num") || $data['list'] < 1)
		$data['list'] = 1;

	// 총 게시물 수
		$data['total'] = sql("SELECT COUNT(*) FROM {$data['table']} {$data['where']}");

	// 마지막 페이지 구하기
		$data['tp'] = $data['total'] ? ceil($data['total'] / $data['list']) : 1;
		if ($data['page'] > $data['tp']) $data['page'] = $data['tp'];

	// 지정 정렬일 때는 오래 걸리는 방식을 취한다
		$data['v'] = ($data['page'] - 1) * $data['list'];
		$data['debug'] = "SELECT * FROM {$data['table']} {$data['where']} {$data['order']} LIMIT {$data['v']}, {$data['list']}";

		$output = array();
		checkTime("query");
		$output = sql("
			q: {$data['debug']}
			mode: array
		");
		checkTime("query");

	// 변수 반환
		$_GET['page'] = $data['page'];

	return $output;	
} // END function
*/

/** 페이지 목폭 출력
 * @class list
 * @param
		-skin: 일반 스킨 [page, link_page, url]
		-skin_now: 현재페이지 스킨 [page, link_page, url]
		-name: 리스팅 변수배열 이름
		-firtName: 첫페이지 가기 링크 텍스트
		-lastName: 마지막페이지 가기 링크 텍스트
		-is_viewLink: 첫,마지막페이지 링크 보이기
		-skip_page_no: 페이징 할 떄 no 를 스킵할지 여부
 * @return String 페이지목록
 */
function getPage($param='') {
	global $mini;
	$param = param($param);

	//// 기본설정
	def($param['name'], 'default');
	$data = &$mini['list'][$param['name']];

	def($param['firstName'], "FIRST");
	def($param['lastName'], "LAST");
	def($param['is_viewLink'], 1);
	def($param['skip_page_no'], 0);
	iss($data['start']);
	iss($data['is_order']);
	iss($data['nowDiv']);
	iss($output);
	$result = array();

	if (empty($data['is_notpage'])) {
		if (!empty($data['is_simple'])) {
			$startPage = ($data['page'] - ($data['page'] % 10)) + 1;
			$lastPage = ($data['tp'] >= $startPage + 9) ? $startPage + 9 : $data['tp'];
		}

		else {
			$startPage = ($data['nowDiv'] - 1) * 10 + 1;
			$lastPage = ($data['tp'] >= $startPage + 9) ? $startPage + 9 : $data['tp'];
		}

		$output = "";
		$outPage = array();
		if ($startPage < 1) $startPage = 1;
		
		//// 페이지 구하기	
		if ($param['is_viewLink'] && $data['page'] != 1) $outPage[] = "first";
		if ($data['page'] > 10) $outPage[] = "prev";
		for ($a=$startPage; $a<=$lastPage; $a++) $outPage[] = $a;
		if ($data['page'] < $data['tp'] && $lastPage < $data['tp']) $outPage[] = "next";
		if ($param['is_viewLink'] && $data['page'] != $data['tp']) $outPage[] = "last";

		if ($lastPage < 1 && count($outPage) == 0) $outPage[] = 1;

		//// 페이지 출력하기
		foreach ($outPage as $key => $val):
			$selectSkin = ($val == $data['page']) ? "skin_now" : "skin";
			$val_start = $val_div = '';
			$tmp_val = $val;
			
			switch ($val):
				// 첫 페이지
				case "first": 
					$val2 = 1; 
					$val = $param['firstName']; 
					break;

				// 마지막 페이지
				case "last": 
					$val2 = $data['tp']; 
					$val = $param['lastName'];
					break;

				// 이전 영역
				case "prev":
					$val = $val2 = $startPage - 1;
					if ($data['start'] && $data['is_order'] && !$data['is_simple']) {
						checkTime("query_prev");
						if ($data['key']) {
//							$data['debug_prev'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} USE KEY ({$data['fieldName']}) WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? ">=" : "<=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order_desc']} LIMIT ".($data['list'] * 10).", 1";
							$data['debug_prev'] = "SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? ">=" : "<=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order_desc']} LIMIT ".($data['list'] * 10).", 1";
						}
						else {
//							$data['debug_prev'] = "SELECT {$data['fieldName']} FROM {$data['table']} USE KEY ({$data['fieldName']}) WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? ">=" : "<=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order_desc']} LIMIT ".($data['list'] * 10).", 1";
							$data['debug_prev'] = "SELECT {$data['fieldName']} FROM {$data['table']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? ">=" : "<=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order_desc']} LIMIT ".($data['list'] * 10).", 1";
						}

						$val_start = sql($data['debug_prev']);
						checkTime("query_prev");
						
						$val_div = $data['nowDiv'] - 1;
					}
					else {
					}
					break;

				// 다음 영역
				case "next":
					$val = $val2 = $lastPage + 1;
					if ($data['start'] && $data['is_order'] && !$data['is_simple']) {
						checkTime("query_next");
						if ($data['key']) {
//							$val_start = sql("SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} USE KEY ({$data['fieldName']}) WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order']} LIMIT ".($data['list'] * 10).", 1");
							$val_start = sql("SELECT DISTINCT({$data['fieldName']}) FROM {$data['keyTable']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order']} LIMIT ".($data['list'] * 10).", 1");
						}
						else {
//							$val_start = sql("SELECT {$data['fieldName']} FROM {$data['table']} USE KEY ({$data['fieldName']}) WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order']} LIMIT ".($data['list'] * 10).", 1");
							$val_start = sql("SELECT {$data['fieldName']} FROM {$data['table']} WHERE {$data['fieldName']}".(preg_match("/order by [a-z0-9_]+ desc/i", $data['order']) ? "<=" : ">=")."{$data['start']} ".str_replace("WHERE ", "and ", $data['where'])." {$data['order']} LIMIT ".($data['list'] * 10).", 1");
						}
						checkTime("query_next");

						$val_div = $data['nowDiv'] + 1;
					}
					else {
					}
					break;

				default : 
					$val2 = $val;
					$val_start = $data['start'];
					$val_div = $data['nowDiv'];
			endswitch;

			// url 생성
			$url = "{$data['pageName']}={$val2}";
			if ($val_start) $url .= "&amp;{$data['startName']}={$val_start}";
			if ($val_div) $url .= "&amp;{$data['divName']}={$val_div}";
			$url .= getURI((!empty($data['skip_page_no']) ? "no, " : "") . "{$data['pageName']}, {$data['divName']}, {$data['startName']}");

			// 결과 배열에 저장
			if ($tmp_val == 'prev') $result[$outPage[$key+1] - 1] = str_replace("&amp;", "&", "{$_SERVER['PHP_SELF']}?$url");
			else if ($tmp_val == 'next') $result[$outPage[$key-1] + 1] = str_replace("&amp;", "&", "{$_SERVER['PHP_SELF']}?$url");
			else if ($tmp_val != 'first' && $tmp_val != 'last') $result[$tmp_val] = str_replace("&amp;", "&", "{$_SERVER['PHP_SELF']}?$url");

			$output .= str_replace(
				array(
					"[:page:]",		
					"[:pageNum:]",
					"[:link_page:]",
					"[:url:]"),
				array(
					$val,
					$val2,
					"href='{$_SERVER['PHP_SELF']}?{$url}'",
					"{$_SERVER['PHP_SELF']}?{$url}"
				), $param[$selectSkin]);
		endforeach;

		// prev, next 구하기
			if (!empty($data['page'])) {
				$data['url_prev'] = !empty($result[$data['page']-1]) ? $result[$data['page']-1] : $result[1];
				$data['url_next'] = !empty($result[$data['page']+1]) ? $result[$data['page']+1] : $result[$data['tp']];
			}
	}

	return $output;
} // END function


/** 코멘트 목록 뽑기
 * @class io
 * @param
		-name: 검색배열이름 (list_cmt)
		-id: 게시판아이디, board_data가 있으면 없어도 된다
		-page: 페이지 (마지막페이지)
		-target_post: 대상게시물번호. view가 있으면 없어도 된다
		$board_data: 게시판정보
		$view: 게시물정보
 * @return Array
 */
function getListCmt($param, $board_data = '', $view = '') {
	global $mini;
	$param = param($param);
	$output = Array();

	iss($param['page']);
	def($param['name'], 'list_cmt');
	def($_REQUEST['cPage'], $param['page']);
	def($_REQUEST['cPage'], 999999999999);

	//// 게시판정보
		if (empty($board_data) && !empty($param['id'])) {
			getBoard($param['id']);
			if (empty($mini['site']) || $mini['site']['no'] != $mini['board']['site']) getSite($mini['board']['site']);
		}
		else if (empty($board_data) && !empty($mini['board'])) {
			$board_data = $mini['board'];
		}

	//// 게시물정보
		if (empty($view)) {
			$view = sql("SELECT * FROM {$board_data['table']} WHERE no={$param['target_post']}");
			if (!is_array($view)) __error('게시물이 존재하지 않습니다'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');
			parsePost($view);

			$output['view'] = $view;
		}
		else {
			$param['target_post'] = $view['no'];
		}


	$is_comment_page = (!empty($board_data['use_comment_page']) && !empty($board_data['list_count_comment'])) ? 1 : 0;

		if (!$is_comment_page) {
			$board_data['list_count_comment'] = 9999999;
		}

	//// 공지사항 제외
		$is_first = 0;
		if ($is_comment_page || (empty($_REQUEST['cQuick']) && empty($_REQUEST['cS']) && empty($_REQUEST['cSort']))) {
			$_REQUEST['cS']['notice!'] = 0;
			$is_first = 1;
		}

		$_REQUEST['cAnd'] = 1;

	//// 검색 처리
		setSearch("
			name: {$param['name']}
			quickName: cQuick
			sName: cS
			andName: cAnd
			is_simple: 1
		");

	//// 기본 검색쿼리 지정
		check($param['target_post'], 'type:num, name:게시물번호');
		$tmp_trackback = (!empty($board_data['use_trackback_cmt']) ? '' : " and trackback=''");
		$mini['list'][$param['name']]['where'] = !empty($mini['list'][$param['name']]['where']) ? $mini['list'][$param['name']]['where']." and target_post={$param['target_post']}{$tmp_trackback}" : "WHERE target_post={$param['target_post']}{$tmp_trackback}";

	//// 공지사항 로드
		if ($is_first) {
			$notice = sql("q:SELECT * FROM {$board_data['table_cmt']} WHERE notice=1 and target_post={$param['target_post']} ORDER BY num, mode:array");
			$output['notice'] = $notice;
		}

	//// 정렬 처리
		setSort("
			name: {$param['name']}
			sortName: cSort
			table: {$board_data['table_cmt']}
			order: ORDER BY num, reply
			order_desc: ORDER BY num DESC, reply ASC
		");

	//// 리스트
		$data = getList("
			name: {$param['name']}
			list: {$board_data['list_count_comment']}
			table: {$board_data['table_cmt']}
			pageName: cPage
			divName: cDiv
			startName: cStart
		");
		$output['data'] = $data;

//		pr($mini['list'][$param['name']]);

	//// 트랙백 같이 뽑기
	/*
		$t_data = array();
		if (!empty($board_data['use_trackback_cmt'])) {
			$t_data = sql("q:SELECT * FROM {$board_data['table_cmt']} WHERE target_post={$view['no']} and trackback!='' ORDER BY num, mode:array");
			$output['data'] = array_merge($t_data, $output['data']);
			unset($t_data);
		}
	*/

	//// 트랙백 따로 뽑기
		if ($is_first && empty($board_data['use_trackback_cmt'])) {
			$t_data = sql("q:SELECT * FROM {$board_data['table_cmt']} WHERE target_post={$view['no']} and trackback!='' ORDER BY num, mode:array");
			$output['trackback'] = $t_data;
		}

	return $output;
} // END function

?>