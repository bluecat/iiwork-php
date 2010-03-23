<?php

/** 최근게시물
 * @class io 
 * @param
		-id: 게시판아이디
		-table: 임의지정테이블
		-mode: 최근게시물모드 [!|issue|writer|relate|popup|memo]
		-order: 임의정렬쿼리
		-where: 임의검색쿼리
		-count: 출력수
		-cut_title: 제목길이
		-cut_ment: 내용길이
		-skin: 게시물 스킨
		-skin_not: 게시물이 없을 떄 스킨
		-skin_first: 첫 게시물 스킨
		-debug: debug모드
		-is_key: 키테이블 사용여부
		-type: 종류 [post!|cmt|memo|member]
 * @return Array 스킨이 있을 경우엔 리턴 안됨
 */
function mhot($param) {
	global $mini;
	$param = param($param);
	def($param['cut_title'], 0);
	def($param['cut_ment'], 0);
	def($param['count'], 5);
	def($param['type'], 'post');

	// 설정 기본값
	def($mini['setting']['issue_interval'], 48);
	
	if (preg_match("/[^0-9]/", $mini['setting']['issue_interval'])) $mini['setting']['issue_interval'] = 48;
	if (preg_match("/[^0-9\,]/", $param['count'])) __error('출력개수에는 숫자와 ,만 입력 가능합니다'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');
	if ($param['count'] > 100) __error('출력개수는 100개를 초과할 수 없습니다'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');

	$where = $order = $board_data = $table = '';
	$data = $sel_board = array();


	// 복수 게시판 지정(keyTable 이 사용된다)
		if (!empty($param['id']) && (strpos($param['id'], '[') !== false || $param['id'] == '*')) {
			$is_multi = 1;

			// 게시판 나눠넣기
			if (strpos($param['id'], '[') !== false) {
				$sel_board = getStr($param['id']);
			}
		}

	// 테이블 임의지정
		if (!empty($param['table'])) $table = $param['table'];

	// 키테이블 지정
		if (!empty($param['is_key'])) $table = $mini['name']['search'];

	// 아이디로 테이블 지정
		if (!$table && !empty($param['id']) && $param['id'] != '*') {
			if (!empty($mini['board']) && !empty($mini['board']['id']) && $mini['board']['id'] == $param['id'])
				$board_data = &$mini['board'];
			else
				$board_data = getBoard($param['id'], 1);
		
			$table = $param['type'] == 'post' ? $board_data['table'] : $board_data['table_cmt'];
		}

	// 키테이블인데 글, 댓글이 아닐 경우 에러
		if (!empty($param['is_key']) && $param['type'] != 'post' && $param['type'] != 'cmt') __error('검색테이블을 참조할 때는 글, 댓글 형식만 사용하실 수 있습니다'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');

	// 특별 모드
		if (!empty($param['mode'])) {
			switch ($param['mode']):
				case 'issue':
					$where .= " and (issue=1 or (date>=DATE_ADD('{$mini['date']}', INTERVAL -{$mini['setting']['issue_interval']} HOUR) and date<=DATE_ADD('{$mini['date']}', INTERVAL 1 DAY)))";
					$order .= ",issue*999999 + hit + vote*10 desc";
					break;

				case 'writer':
					if (empty($mini['setting']['writer_no'])) return 0;
					$where .= " and target_member={$mini['setting']['writer_no']}";
					$order .= ",no desc";
					break;

				case 'relate':
					if (empty($mini['setting']['relate'])) return 0;
					$where .= " and ".sqlSel(explode(",", $mini['setting']['relate']));
					$order .= ",no desc";
					break;

				case 'popup':
					$where .= " and popup=1";
					$order .= ",no desc";
					break;

				case 'memo':
					$table = $mini['name']['memo'];
					$param['count'] = 20;
					$param['type'] = 'memo';

					if (!empty($mini['log'])) {
						$where .= " and target_member={$mini['member']['no']} and date_read=0 and del_target=0";
						$order .= ",no";
					}
					break;
			endswitch;
		}

	// 여러 테이블 검색시 게시판 정보 로드 및 쿼리 설정
	if (!empty($is_multi)) {
		$q_admin = '';
		if (!empty($param['id']) && !empty($sel_board)) {
			$q_admin .= 'WHERE '.sqlSel($sel_board);
//			$where .= " and ".sqlSel($sel_board, 'id');
		}

		$data_board = sql("
			q: SELECT * FROM {$mini['name']['admin']} {$q_admin}
			mode: array
		");

		$board_name = $board_data_arr = array();

		if (!empty($data_board)) {
			foreach ($data_board as $key=>$val):
				$board_name[$val['no']] = $val['name'];
				parseBoard($val);
				$board_data_arr[$val['no']] = $val;
			endforeach;
			unset($data_board);
		}
	}

	// 키 테이블 검색시 종류에 따라 쿼리 설정
	if (!empty($param['is_key'])) {
		$where .= ($param['type'] == 'post' ? " and cmt_no=0" : " and cmt_no!=0");
	}

	// 테이블명이 지정되지 않았다면 에러
	if (empty($param['id']) && empty($param['table']) && empty($table)) __error('게시판 아이디나 테이블명을 입력해주세요'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');

	// 임의 설정 추가
	if (!empty($param['where'])) {
		if (!preg_match("/^ ?(and|or)/i", $param['where'])) $param['where'] = " and ({$param['where']})";
		$where .= $param['where'];
	}
	if (!empty($param['order'])) {
		if (!preg_match("/^\,/i", $param['order'])) $param['order'] = ",{$param['order']}";
		$order .= $param['order'];
	}
	else if (empty($order)) $order = ",date desc";

	// 쪽지인데 로그인이 안되어 있다면 넘김
	if (!empty($param['mode']) && $param['mode'] == 'memo' && empty($mini['log'])) {
		return false;
	}

	// 출력
	else {
		// 쿼리날림
			if ($where) $where = "WHERE ".substr($where, 4);
			if ($order) $order = "ORDER BY ".substr($order, 1);

			// 복수 게시판일 경우
			if (!empty($is_multi)) {
				// 전체 게시판일 때
				if (empty($sel_board) && $param['id'] == '*') {
					$sel_board = array_keys($board_data_arr);
				}

				if (!empty($sel_board)) {
					$tmp_data = array();
					$tmp_data_order = array();
					$order_name = $order_type = '';
					$order_data = array();
					$data = array();
		
					// order 분석
					if (count(explode(",", $order)) > 1) __error('정렬 기준은 한개만 가능합니다'.' ('.__FILE__.' line '.__LINE__.' in '.__FUNCTION__.')');
					$tmp_order = explode(" ", str_replace("ORDER BY ", "", $order));
					$order_name = $tmp_order[0];
					$order_type = empty($tmp_order[1]) ? 'asc' : $tmp_order[1];
					unset($tmp_order);

					foreach ($sel_board as $val):
						// 키테이블 사용시
						if (!empty($param['is_key'])) {
							$tmp_where = !empty($where) ? $where." and id={$val}" : "WHERE id={$val}";
							$table = $mini['name']['search'];
						}
						// 일반 테이블 검색시
						else {
							$tmp_where = $where;
							$table = $param['type'] == 'post' ? $mini['name']['board'].$val : $mini['name']['cmt'].$val;
						}
						
						$tmp_data = array_merge($tmp_data, sql(array(
							'q' => "SELECT * FROM {$table} {$tmp_where} ".(!empty($param['is_key']) ? "GROUP BY num" : "")." {$order} LIMIT {$param['count']}",
							'mode' => 'array',
							'extra_name' => 'id',
							'extra_value' => $val
						)));
					endforeach;

					// 정렬 기준에 따라서 나눔
					foreach ($tmp_data as $key => $val):
						$order_data[$key] = $val[$order_name];
					endforeach;

					// 정렬
					if ($order_type == 'asc')
						asort($order_data);
					else
						arsort($order_data);
				
					// 정렬한 순서대로 data 정의
					$i = 0;
					foreach ($order_data as $key=>$val):
						if ($i >= $param['count']) break;
						$data[$i] = $tmp_data[$key];
						++$i;
					endforeach;
					unset($tmp_data);
					unset($order_data);
				}
			}

			// 한개의 게시판일 경우
			else {
				$data = sql(array(
					'q' => "SELECT * FROM {$table} {$where} ".(!empty($param['is_key']) ? "GROUP BY num" : "")." {$order} LIMIT {$param['count']}",
					'mode' => 'array'
				));
			}

			if (!empty($data)) {
				$a = 0;
				$count_data = count($data);
				foreach ($data as $key=>$val):
					// 여러 게시판 사용시 게시판 정보 입력
					if (!empty($is_multi)) {
						if (!empty($board_name[$data[$key]['id']])) $data[$key]['board_name'] = $board_name[$data[$key]['id']];
						if (!empty($data[$key]['id'])) $data[$key]['url_board'] = "{$mini['dir']}mini.php?id={$data[$key]['id']}";
						if (!empty($board_data_arr)) $mini['board_data'] = $board_data_arr[$data[$key]['id']];
					}
					else {
						$mini['board_data'] = $board_data;
					}

					if (!empty($param['is_key'])) {
						// 키 테이블 사용 시 진짜 자료 로드
						$val2 = sql("SELECT * FROM ".(!empty($data[$key]['cmt_no']) ? $mini['name']['cmt'] : $mini['name']['board'])."{$data[$key]['id']} WHERE ".(!empty($data[$key]['cmt_no']) ? "no={$data[$key]['cmt_no']}" : "num={$data[$key]['num']}"));
						$data[$key] = array_merge($data[$key], $val2);
					}

					// title 은 포함된 변수가 많기 때문에 먼저 잘라준다
					if (!empty($param['cut_title']) && !empty($data[$key]['title'])) $data[$key]['title'] = strCut($data[$key]['title'], $param['cut_title']);

					// 가공 함수 실행
					$tmp_func = "parse".($param['type'] == 'cmt' ? 'comment' : $param['type']);					
					
					if ($count_data == 1) {
						$tmp_func($data[$key], 'view');
					}
					else {
						$tmp_func($data[$key], '');
					}

					// 내용 자르기
					if (!empty($param['cut_ment']) && !empty($data[$key]['ment'])) {
						$data[$key]['ment'] = strCut($data[$key]['ment'], $param['cut_ment']);
						$data[$key]['ment_notag'] = strCut($data[$key]['ment_notag'], $param['cut_ment']);
					}

//					if (!empty($param['debug'])) {
//						echo nl2br(print_r($data[$key], 1));
//						exit;
//					}

					if (!empty($param['skin'])) {
						$skin = '';
						if ($a == 0 && !empty($param['skin_first'])) {
							$skin = $param['skin_first'];
						}
						else {
							$skin = $param['skin'];
						}

						// 논리문
						$preg_left = $preg_right = array();
						$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+):\]/ie";
						$preg_right[] = "\$data[$key]['\\1']['\\2']['\\3']";
						$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+):\]/ie";
						$preg_right[] = "\$data[$key]['\\1']['\\2']";
						$preg_left[] = "/\[:([a-z0-9_]+):\]/ie";
						$preg_right[] = "\$data[$key]['\\1']";
						$skin = preg_replace($preg_left, $preg_right, $skin);
						
						echo $skin;
					}

					$a++;
				endforeach;

				if (empty($param['skin'])) {
					if (count($data) == 1 && $param['count'] == 1)
						return current($data);
					else
						return $data;
				}
			}

			// 자료가 없을 떄
			else {
				if (!empty($param['skin_not'])) echo $param['skin_not'];
			}
	}
} // END function
?>