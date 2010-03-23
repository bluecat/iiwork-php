<?php
global $mini;

# .mini 스킨 컨버팅

/**
 * 스킨변환
 * @class skin.convert
 * @param
		$url: 스킨 경로
		$mode: str일 경우 url을 스킨 변수로 본다
  */
function skinConv($url, $mode = 'url') {
	global $mini;
	$str_left = array();
	$str_right = array();
	$preg_left = array();
	$preg_right = array();

	if ($mode == 'url') {
		if (!file_exists($url) || !is_writable($url)) {
			if (empty($mini['error_msg'])) $mini['error_msg'] = '';
			$mini['error_msg'] .= "[{$url}] 파일이 없거나 쓰기 퍼미션이 없습니다.<br />";
			return false;
		}

		//// 파일 열기
			if (!preg_match("/\.mini$/i", basename($url)))
				__error('.mini 만 스킨변환이 가능합니다');

			if (file_exists($url)) {
				$fp = fopen($url, 'r');
				if (!$fp)
					__error("[{$url}] 를 읽을 수 없습니다");
				else {
					$size = filesize($url);
					if ($size) $output = fread($fp, filesize($url));
					fclose($fp);
				}
			}
			else
				__error("[{$url}] 파일이 없습니다.");
	}
	else {
		$output = $url;
	}

	if (!empty($output)) {
		//// 세팅(먼저 치환)
			$output = preg_replace("/\[set\:([a-z0-9_]+)\=(.*)\]/i", "<?php \$mini['setting']['\\1'] = \"\\2\"; ?>", $output);

		//// 함수(먼저 치환)
			$output = preg_replace("/\[\%([a-z0-9_]+):([^\%]*)\%\]/is", "<?php echo \\1(\\2); ?>", $output);



//// 구간
switch ($mini['filename']):



# 목록보기, 글읽기, 댓글
case 'mini.php':
case 'cmt.php':
case 'write.php':
case 'head.php':
case 'foot.php':
case 'widget.php':

// 목록 시작(폼)
	$str_left[] = "[LIST]";
	$str_right[] = "
	<!-- [LIST] -->
	<?php if (!empty(\$mini['member']['level_admin'])) { ?>
	<form id='form_list' name='form_list' action='' method='get'>
	<input type='hidden' name='mode' />
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='pageKey' value='<?php echo \$_SESSION['pageKey']; ?>' />
	<input type='hidden' name='completeMode' value='ajax,reload.parent' />
	<?php } ?>
	";
	$str_left[] = "[/LIST]";
	$str_right[] = "
	<?php if (!empty(\$mini['member']['level_admin'])) { ?>
	</form>
	<?php } ?>
	<!-- [/LIST] -->";

// 목록 루프
	$str_left[] = "[LIST_LOOP]";
	$str_right[] = "
	<?php
	global \$data, \$view, \$is_first, \$notice;

	if (!empty(\$data) && is_array(\$data) && count(\$data) >= 1)
		foreach (\$data as \$key=>\$val):
			parsePost(\$val);
			\$mini['skin']['data'] = &\$val;
			
			// 이전, 다음글 지정
			if (!empty(\$_REQUEST['no'])) {
				if (\$key-1 >= 0 && \$view['no'] == \$val['no'] && !empty(\$data[\$key-1])) {
					\$mini['skin']['url_view_prev'] = \"mini.php?no={\$data[\$key-1]['no']}\".getURI('no', '&');
					\$mini['skin']['prev'] = \$data[\$key-1];
				}
				else if (\$key-1 < 0 && \$view['no'] == \$val['no'] && !empty(\$notice)) {
					\$mini['skin']['url_view_prev'] = \"mini.php?no={\$notice[count(\$notice)-1]['no']}\".getURI('no', '&');
					\$mini['skin']['prev'] = end(\$notice);
				}

				if (\$view['no'] == \$val['no'] && !empty(\$data[\$key+1])) {
					\$mini['skin']['url_view_next'] = \"mini.php?no={\$data[\$key+1]['no']}\".getURI('no', '&');
					\$mini['skin']['next'] = \$data[\$key+1];
				}
			}
	?>";

	$str_left[] = "[/LIST_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	?>";

// 목록 공지사항 루프
	$str_left[] = "[NOTICE_LOOP]";
	$str_right[] = "
	<?php
	global \$data, \$view, \$is_first, \$notice;
	if (!empty(\$notice) && is_array(\$notice) && count(\$notice) >= 1)
		foreach (\$notice as \$key=>\$val):
			parsePost(\$val);
			\$mini['skin']['notice'] = &\$val;

			// 이전, 다음글 지정
				if (!empty(\$_REQUEST['no'])) {
					if (\$key-1 >= 0 && \$view['no'] == \$val['no'] && !empty(\$notice[\$key-1])) {
						\$mini['skin']['url_view_prev'] = \"mini.php?no={\$notice[\$key-1]['no']}\".getURI('no', '&');
						\$mini['skin']['prev'] = \$notice[\$key-1];
					}
					if (\$view['no'] == \$val['no'] && !empty(\$notice[\$key+1])) {
						\$mini['skin']['url_view_next'] = \"mini.php?no={\$notice[\$key+1]['no']}\".getURI('no', '&');
						\$mini['skin']['next'] = \$notice[\$key+1];
					}
					if (\$view['no'] == \$val['no'] && empty(\$notice[\$key+1]) && !empty(\$data)) {
						\$i = 0;
						while (\$i < \$mini['board']['list_count']):
							if (!empty(\$data[\$i]) && empty(\$data[\$i]['notice'])) {
								\$mini['skin']['url_view_next'] = \"mini.php?no={\$data[\$i]['no']}\".getURI('no', '&');
								\$mini['skin']['next'] = \$data[\$i];
								break;
							}
							\$i++;
						endwhile;
					}
				}
	?>";

	$str_left[] = "[/NOTICE_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 인기글 뽑기
	$str_left[] = "[ISSUE]";
	$str_right[] = "
	<?php
	global \$issue, \$mini;
	def(\$mini['setting']['issue_interval'], 48);
	def(\$mini['setting']['cache_count'], 5);
	\$issue = mhot(\"
		id: {\$mini['board']['id']}
		mode: issue
		count: {\$mini['setting']['cache_count']}
	\");

	\$mini['skin']['issue'] = !empty(\$issue);
	?>";

	$str_left[] = "[/ISSUE]";
	$str_right[] = "";

// 인기글 루프
	$str_left[] = "[ISSUE_LOOP]";
	$str_right[] = "
	<?php
	global \$issue;
	if (!empty(\$issue) && is_array(\$issue) && count(\$issue) >= 1)
		foreach (\$issue as \$key=>\$val):
			\$mini['skin']['issue'] = &\$val;
	?>";

	$str_left[] = "[/ISSUE_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 작성자글 뽑기
	$str_left[] = "[WRITER]";
	$str_right[] = "
	<?php
	global \$writer;
	if (!empty(\$mini['skin']['view']['target_member'])) \$mini['setting']['writer_no'] = \$mini['skin']['view']['target_member'];
	def(\$mini['setting']['cache_count'], 5);
	\$writer = mhot(\"
		id: {\$mini['board']['id']}
		mode: writer
		count: {\$mini['setting']['cache_count']}
	\");

	\$mini['skin']['writer'] = !empty(\$writer);
	?>";

	$str_left[] = "[/WRITER]";
	$str_right[] = "";

// 작성자글 루프
	$str_left[] = "[WRITER_LOOP]";
	$str_right[] = "
	<?php
	global \$writer;
	if (!empty(\$writer) && is_array(\$writer) && count(\$writer) >= 1)
		foreach (\$writer as \$key=>\$val):
			\$mini['skin']['writer'] = &\$val;
	?>";

	$str_left[] = "[/WRITER_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 관련글 뽑기
	$str_left[] = "[RELATE]";
	$str_right[] = "
	<?php
	global \$relate;
	def(\$mini['setting']['cache_count'], 5);
	def(\$mini['setting']['relate'], \$mini['skin']['view']['relate']);
	\$relate = mhot(\"
		id: {\$mini['board']['id']}
		mode: relate
		count: {\$mini['setting']['cache_count']}
	\");

	\$mini['skin']['relate'] = !empty(\$relate);
	?>";

	$str_left[] = "[/RELATE]";
	$str_right[] = "";

// 관련글 루프
	$str_left[] = "[RELATE_LOOP]";
	$str_right[] = "
	<?php
	global \$relate;
	if (!empty(\$relate) && is_array(\$relate) && count(\$relate) >= 1)
		foreach (\$relate as \$key=>\$val):
			\$mini['skin']['relate'] = &\$val;
	?>";

	$str_left[] = "[/RELATE_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 최근 댓글 뽑기
	$str_left[] = "[RECENT_CMT]";
	$str_right[] = "
	<?php
	global \$recent_cmt;
	def(\$mini['setting']['recent_cmt_only'], '');
	def(\$mini['setting']['recent_cmt_count'], 5);
	def(\$mini['setting']['recent_cmt_cut'], 60);
	\$recent_cmt = mhot(\"
		id: {\$mini['board']['id']}
		type: cmt
		where: \".(!empty(\$mini['setting']['recent_cmt_only']) ? \"trackback=''\" : \"\").\"
		count: {\$mini['setting']['recent_cmt_count']}
	\");

	\$mini['skin']['recent_cmt'] = !empty(\$recent_cmt);
	?>";

	$str_left[] = "[/RECENT_CMT]";
	$str_right[] = "";

// 최근 댓글 루프
	$str_left[] = "[RECENT_CMT_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$recent_cmt) && is_array(\$recent_cmt) && count(\$recent_cmt) >= 1)
		foreach (\$recent_cmt as \$key=>\$val):
			if (!empty(\$mini['setting']['recent_cmt_cut_ment'])) {
				\$val['ment'] = strCut(\$val['ment'], \$mini['setting']['recent_cmt_cut_ment']);
				\$val['ment_notag'] = strCut(\$val['ment_notag'], \$mini['setting']['recent_cmt_cut_ment']);
			}
			\$mini['skin']['recent'] = &\$val;
	?>";

	$str_left[] = "[/RECENT_CMT_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 최근 트랙백 뽑기
	$str_left[] = "[RECENT_TRACKBACK]";
	$str_right[] = "
	<?php
	global \$recent_trackback;
	def(\$mini['setting']['recent_trackback_count'], 5);
	\$recent_trackback = mhot(\"
		id: {\$mini['board']['id']}
		type: cmt
		count: {\$mini['setting']['recent_trackback_count']}
		where: trackback!=''
	\");

	\$mini['skin']['recent_trackback'] = !empty(\$recent_trackback);
	?>";

	$str_left[] = "[/RECENT_TRACKBACK]";
	$str_right[] = "";

// 최근 트랙백 루프
	$str_left[] = "[RECENT_TRACKBACK_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$recent_trackback) && is_array(\$recent_trackback) && count(\$recent_trackback) >= 1)
		foreach (\$recent_trackback as \$key=>\$val):
			if (!empty(\$mini['setting']['recent_trackback_cut_ment'])) {
				\$val['ment'] = strCut(\$val['ment'], \$mini['setting']['recent_trackback_cut_ment']);
				\$val['ment_notag'] = strCut(\$val['ment_notag'], \$mini['setting']['recent_trackback_cut_ment']);
			}
			\$mini['skin']['recent'] = &\$val;
	?>";

	$str_left[] = "[/RECENT_TRACKBACK_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 최근 글 뽑기
	$str_left[] = "[RECENT_POST]";
	$str_right[] = "
	<?php
	global \$recent_post;
	def(\$mini['setting']['recent_post_count'], 5);
	\$recent_post = mhot(\"
		id: {\$mini['board']['id']}
		type: post
		count: {\$mini['setting']['recent_post_count']}
	\");

	\$mini['skin']['recent_post'] = !empty(\$recent_post);
	?>";

	$str_left[] = "[/RECENT_POST]";
	$str_right[] = "";

// 최근 글 루프
	$str_left[] = "[RECENT_POST_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$recent_post) && is_array(\$recent_post) && count(\$recent_post) >= 1)
		foreach (\$recent_post as \$key=>\$val):
			if (!empty(\$mini['setting']['recent_post_cut_title'])) {
				\$val['title'] = strCut(\$val['title'], \$mini['setting']['recent_post_cut_title']);
			}
			if (!empty(\$mini['setting']['recent_post_cut_ment'])) {
				\$val['ment'] = strCut(\$val['ment'], \$mini['setting']['recent_post_cut_ment']);
				\$val['ment_notag'] = strCut(\$val['ment_notag'], \$mini['setting']['recent_post_cut_ment']);
			}
			\$mini['skin']['recent'] = &\$val;
	?>";

	$str_left[] = "[/RECENT_POST_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 프로필 뽑기
	$str_left[] = "[PROFILE]";
	$str_right[] = "
	<?php
	\$mini['skin']['profile'] = mhot(\"
		id: {\$mini['board']['id']}
		type: post
		is_key: 1
		where: mode='tag' and ment='@profile'
		count: 1
	\");
	?>";

	$str_left[] = "[/PROFILE]";
	$str_right[] = "";

// 유저 설정 - 메뉴
	$str_left[] = "[CONFIG_MENU]";
	$str_right[] = "
	<?php
		if (!empty(\$mini['skin']['user']['menu'])) {
			foreach (getStr(\$mini['skin']['user']['menu']) as \$key=>\$val):
				\$mini['skin']['config_menu'] = explode('&#124;', trim(\$val));

				\$mini['skin']['config_menu']['title'] = \$mini['skin']['config_menu'][0];
				\$mini['skin']['config_menu']['url'] = amp(\$mini['skin']['config_menu'][1], 'encode');
				\$mini['skin']['config_menu']['link'] = \"href='{\$mini['skin']['config_menu']['url']}'\".(!empty(\$mini['skin']['config_menu'][2]) ? \" target='_blank'\" : \"\");
	?>";

	$str_left[] = "[/CONFIG_MENU]";
	$str_right[] = "
	<?php endforeach; } ?>";


// 팝업글 뽑기
	$str_left[] = "[POPUP]";
	$str_right[] = "
	<?php
	global \$popup;
	def(\$mini['setting']['cache_count'], 5);
	\$popup = mhot(\"
		id: {\$mini['board']['id']}
		mode: popup
		count: {\$mini['setting']['cache_count']}
		order: no asc
	\");

	\$mini['skin']['popup'] = !empty(\$popup);
	?>";

	$str_left[] = "[/POPUP]";
	$str_right[] = "";

// 팝업글 루프
	$str_left[] = "[POPUP_LOOP]";
	$str_right[] = "
	<?php
	global \$popup;
	if (!empty(\$popup) && is_array(\$popup) && count(\$popup) >= 1)
		foreach (\$popup as \$key=>\$val):
			\$val['depth'] = \$key+1;
			\$val['is_cookie'] = !empty(\$_COOKIE[\"popup_{\$_REQUEST['id']}_{\$val['no']}\"]);
			if (!\$val['is_cookie']) \$mini['skin']['is_popup'] = 1;
			\$mini['skin']['popup'] = &\$val;
			
	?>";

	$str_left[] = "[/POPUP_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 팝업글 스크립트
	$str_left[] = "[POPUP_SCRIPT]";							
	$str_right[] = "
	<?php if (!empty(\$mini['skin']['is_popup'])) { ?>
	<script type='text/javascript'>
	//<![CDATA[
		\$\$('div[id^=popup]').each(function (item) {
			item.addEvent('dblclick', function () {
				popupClose(this);
			});
		});

		// 배경 생성
		var wcWidth = window.getScrollWidth();
		var wcHeight = window.getScrollHeight();
		
		if (window.ie6) {
			wcWidth -= 20;
			wcHeight -= 4;
		}

		new Element('div', {
			'id': 'backPopup',
			'styles': {
				width: wcWidth.px(),
				height: wcHeight.px(),
				position: 'absolute',
				top: 0,
				left: 0,
				opacity: 0.25,
				zindex: '1',
				backgroundColor: '#000000'
			}
		}).inject(document.body);
	//]]>
	</script>
	<?php } ?>";

	$str_left[] = "[/POPUP_SCRIPT]";
	$str_right[] = "";

// 쪽지 뽑기
	$str_left[] = "[MEMO]";
	$str_right[] = "
	<?php
	global \$memo;
	def(\$mini['setting']['cache_count'], 5);
	def(\$mini['setting']['cut_ment'], 0);
	\$memo = mhot(\"
		mode: memo
	\");

	\$mini['skin']['memo'] = !empty(\$memo);
	?>";

	$str_left[] = "[/MEMO]";
	$str_right[] = "";

// 쪽지 루프
	$str_left[] = "[MEMO_LOOP]";
	$str_right[] = "
	<?php
	global \$memo;
	if (!empty(\$memo) && is_array(\$memo) && count(\$memo) >= 1)
		foreach (\$memo as \$key=>\$val):
			\$val['depth'] = \$key+1;
			\$val['js_friend'] = \"onclick='view_member.action(\\\"friend\\\", { target_member: \\\"{\$val['from_member']}\\\" });'\";
			\$val['js_memo_block'] = \"onclick='view_member.action(\\\"memo_block\\\", { target_member: \\\"{\$val['from_member']}\\\" });'\";
			\$val['js_memo_save'] = \"onclick='view_member.action(\\\"memo_save\\\", { memo_no: \\\"{\$val['no']}\\\" }); memoAction(\\\"read\\\", \\\"{\$val['no']}\\\");'\";
			\$val['js_memo_del'] = \"onclick='view_member.action(\\\"memo_del\\\", { memo_no: \\\"{\$val['no']}\\\" }); memoAction(\\\"next\\\", \\\"{\$val['no']}\\\");'\";
			\$mini['skin']['memo'] = &\$val;
		
		echo \"<div id='memoDiv{\$mini['skin']['memo']['no']}' class='iiMemo' style='z-index:{\$mini['skin']['memo']['depth']}; position:absolute; left:50px; top:50px; width:200px; height:300px; border:10px solid #545454; padding:10px 15px; line-height:1.5; background:#fff url(\\\"{\$mini['skin']['dir']}image/title_back.gif\\\") repeat-x 0 -2px;' title='더블클릭하면 닫힙니다.'>\";
	?>";

	$str_left[] = "[/MEMO_LOOP]";
	$str_right[] = "
	</div>
	<?php endforeach; ?>";

// 쪽지 스크립트
	$str_left[] = "[MEMO_SCRIPT]";
	$str_right[] = "
	<script type='text/javascript'>
	//<![CDATA[
		\$\$('.iiMemo').each(function (item) {
			var no = item.id.toString().replace(/^memoDiv/i, '');

			item.addEvent('dblclick', function () {
				this.remove();
			});

			var tool = \$('memoMove' + no);
			if (\$chk(tool)) {
				item.makeDraggable({
					handle: tool
				});
			}
		});

		function memoAction(mode, no) {
			switch (mode) {
				case 'read':
					new Ajax(miniDir + 'ajax.php', {
						onComplete: function (item) {
							if (\$chk(item)) {
								data = setJSON(item);

								if (data['error'] == 1) {
									error(data['msg']);
								}
								else {
									\$('memoDiv' + no).remove();
								}

							}
							else {
								error('데이터 전송에 실패했습니다');
							}					
						},
						onfailure: function () {
							error('데이터 전송에 실패했습니다');
						}
					}).send(miniDir + 'ajax.php', Object.toQueryString({
						'mode': 'memo_read',
						'no': no
					}));
					break;

				case 'read_all':
					new Ajax(miniDir + 'ajax.php', {
						onComplete: function (item) {
							if (\$chk(item)) {
								data = setJSON(item);

								if (data['error'] == 1) {
									error(data['msg']);
								}
								else {
									\$\$('div[id^=memoDiv]').each(function(item) {
										item.remove();
									});
								}

							}
							else {
								error('데이터 전송에 실패했습니다');
							}					
						},
						onfailure: function () {
							error('데이터 전송에 실패했습니다');
						}
					}).send(miniDir + 'ajax.php', Object.toQueryString({
						'mode': 'memo_read'
					}));
					break;

				case 'next':
					\$('memoDiv' + no).remove();
					break;

				case 'close':
					\$\$('div[id^=memoDiv]').each(function(item) {
						item.remove();
					});
					break;

				default:
					error('정의되지 않은 모드 입니다');					
			}
		}
	//]]>
	</script>";

	$str_left[] = "[/MEMO_SCRIPT]";
	$str_right[] = "";

// 카테고리 보기
	$str_left[] = "[VIEW_CATEGORY]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['view']['category']) && is_array(\$mini['skin']['view']['category']))
		foreach(\$mini['skin']['view']['category'] as \$key => \$val):
			\$mini['skin']['category']['no'] = \$val;
			\$mini['skin']['category']['name'] = \$mini['board']['category_name'][\$val];
			\$mini['skin']['category']['is_first'] = (!\$key);
			\$mini['skin']['category']['url_view'] = \"mini.php?id={\$_REQUEST['id']}&amp;category=\".urlencode(\$val);
			\$mini['skin']['category']['link_view'] = \"href='{\$mini['skin']['category']['url_view']}'\";
	?>";

	$str_left[] = "[/VIEW_CATEGORY]";
	$str_right[] = "
	<?php endforeach; ?>";

// 카테고리 목록 뽑기
	$str_left[] = "[CATEGORY_LIST]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['category']))
		foreach(\$mini['board']['category'] as \$key => \$val):
			\$mini['skin']['category'] = \$val;
			\$mini['skin']['category']['is_now'] = !empty(\$_REQUEST['category']) && \$_REQUEST['category'] == \$val['no'];
			\$mini['skin']['category']['url_view'] = \"{\$mini['dir']}mini.php?id={\$_REQUEST['id']}&amp;category={\$val['no']}\".getURI(\"category,id,div,start,page\");
			urlToLink(\$mini['skin']['category']);
	?>";

	$str_left[] = "[/CATEGORY_LIST]";
	$str_right[] = "
	<?php endforeach; ?>";

// 태그
	$str_left[] = "[VIEW_TAG]";
	$str_right[] = "
	<?php 
	if (!empty(\$mini['skin']['view']['tag']) && is_array(\$mini['skin']['view']['tag'])) {
		foreach(\$mini['skin']['view']['tag'] as \$key => \$val):
			\$mini['skin']['tag']['name'] = \$val;
			\$mini['skin']['tag']['is_first'] = (!\$key);
			\$mini['skin']['tag']['url_view'] = \"mini.php?id={\$_REQUEST['id']}&amp;s[tag]=\".urlencode(\$val);
			\$mini['skin']['tag']['link_view'] = \"href='{\$mini['skin']['tag']['url_view']}'\";
	?>";

	$str_left[] = "[/VIEW_TAG]";
	$str_right[] = "
	<?php endforeach; } ?>";

// 파일
	$str_left[] = "[VIEW_FILE]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['view']['file_data']) && !empty(\$mini['board']['file_value']))
		foreach (\$mini['skin']['view']['file_data'] as \$key=>\$val):
			parseFile(\$val);
			\$mini['skin']['file'] = \$val;
			\$mini['skin']['file']['num'] = \$key + 1;
	?>";

	$str_left[] = "[/VIEW_FILE]";
	$str_right[] = "
	<?php endforeach; ?>";

// 링크
	$str_left[] = "[VIEW_LINK]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['view']['link']) && !empty(\$mini['board']['link_value']))
		foreach (\$mini['skin']['view']['link'] as \$key=>\$val):
			\$mini['skin']['link']['no'] = \$key;
			\$mini['skin']['link']['value'] = \$val;
	?>";

	$str_left[] = "[/VIEW_LINK]";
	$str_right[] = "
	<?php endforeach; ?>";

// 추가필드
	$str_left[] = "[VIEW_FIELD]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['field']) && !empty(\$mini['skin']['view']['field']))
		foreach (\$mini['skin']['view']['field'] as \$key=>\$val):
			\$mini['skin']['field']['name'] = empty(\$mini['board']['field'][\$key]['name']) ? \$key : \$mini['board']['field'][\$key]['name'];
			\$mini['skin']['field']['value'] = \$val;
			\$mini['skin']['field']['is_array'] = is_array(\$val);
	?>";

	$str_left[] = "[/VIEW_FIELD]";
	$str_right[] = "
	<?php endforeach; ?>";

// 추기필드 멀티
	$str_left[] = "[VIEW_FIELD_ARRAY]";
	$str_right[] = "
	<?php
	if (is_array(\$val)) {
		foreach (\$val as \$key2=>\$val2):
			\$mini['skin']['field']['value'] = \$val2;
	?>";

	$str_left[] = "[/VIEW_FIELD_ARRAY]";
	$str_right[] = "
	<?php endforeach; } ?>";

// 검색
	$str_left[] = "[SEARCH]";
	$str_right[] = "
	<!-- [SEARCH] -->
	<?php if (getPermit(\"name:search\") && !empty(\$mini['board']['use_search'])) { ?>
	<form id='form_search' name='form_search' action='mini.php' method='get'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<?php if (!empty(\$_REQUEST['skinmake'])) { ?><input type='hidden' name='skinmake' value='<?php echo \$_REQUEST['skinmake']; ?>' /><?php } ?>";

	$str_left[] = "[/SEARCH]";
	$str_right[] = "
	</form>
	<?php } ?>
	<!-- [/SEARCH] -->";

//+ 댓글임시
	$str_left[] = "[CMT2]";
	$str_right[] = "
	<?php 
	if (\$mini['board']['use_comment']) {
		echo \"<iframe id='comment' name='comment' src='{\$mini['dir']}cmt.php?id={\$_REQUEST['id']}&amp;no={\$_REQUEST['no']}\".(!empty(\$_REQUEST['pass_encode']) ? \"&amp;pass_encode={\$_REQUEST['pass_encode']}\" : \"\").getURI(\"id, no, start, div, sort, s, quick, and, is_cmt, page\").\"' frameborder='0' style='border:0; width:100%;'></iframe>\";
	?>";
	
	$str_left[] = "[/CMT2]";
	$str_right[] = "
	<?php } ?>";

// 댓글 목록 폼
	$str_left[] = "[CMT_LIST_FORM]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['use_comment'])) { 
	?>
	<div id='commentDiv'>
	<form id='form_cmt' name='form_cmt' action='cmt.x.php<?php echo getURI(\"id, no, mode, reply, target_post, script, formMode\", \"?\"); ?>' method='post'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='target_post' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='reply' />
	<input type='hidden' name='mode' />
	<input type='hidden' name='pageCmtKey' value='<?php if (!empty(\$_SESSION['pageCmtKey'])) echo \$_SESSION['pageCmtKey']; ?>' />
	<input type='hidden' name='sel' />";

	$str_left[] = "[/CMT_LIST_FORM]";
	$str_right[] = "
	</form>
	</div>
	<?php } ?>";

// 댓글 루프
	$str_left[] = "[CMT_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['cmt']['data']) && is_array(\$mini['skin']['cmt']['data']))
		foreach (\$mini['skin']['cmt']['data'] as \$key=>\$val):
			parseComment(\$val);
			\$mini['skin']['data'] = &\$val;
	?>";

	$str_left[] = "[/CMT_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 댓글 루프
	$str_left[] = "[CMT_ALL_LOOP]";
	$str_right[] = "
	<?php
	\$tmp_data = array();
	if (!empty(\$mini['skin']['cmt']['notice'])) \$tmp_data = array_merge(\$tmp_data, \$mini['skin']['cmt']['notice']);
	if (!empty(\$mini['skin']['cmt']['trackback'])) \$tmp_data = array_merge(\$tmp_data, \$mini['skin']['cmt']['trackback']);
	if (!empty(\$mini['skin']['cmt']['data'])) \$tmp_data = array_merge(\$tmp_data, \$mini['skin']['cmt']['data']);

	if (!empty(\$tmp_data))
		foreach (\$tmp_data as \$key=>\$val):
			parseComment(\$val);
			\$mini['skin']['data'] = &\$val;
	?>";

	$str_left[] = "[/CMT_ALL_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 댓글 공지사항 루프
	$str_left[] = "[CMT_NOTICE_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['cmt']['notice']) && is_array(\$mini['skin']['cmt']['notice']))
		foreach (\$mini['skin']['cmt']['notice'] as \$key=>\$val):
			parseComment(\$val);
			\$mini['skin']['data'] = &\$val;
	?>";

	$str_left[] = "[/CMT_NOTICE_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 댓글 엮인글 루프
	$str_left[] = "[CMT_TRACKBACK_LOOP]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['cmt']['trackback']) && is_array(\$mini['skin']['cmt']['trackback']))
		foreach (\$mini['skin']['cmt']['trackback'] as \$key=>\$val):
			parseComment(\$val);
			\$mini['skin']['data'] = &\$val;
	?>";

	$str_left[] = "[/CMT_TRACKBACK_LOOP]";
	$str_right[] = "
	<?php endforeach; ?>";

// 댓글 입력 폼
	$str_left[] = "[CMT_FORM]";
	$str_right[] = "
	<?php if (getPermit(\"name:comment\")) { ?><div id='write_comment_div'>";

	$str_left[] = "[/CMT_FORM]";
	$str_right[] = "
	</div><?php } ?>";

// 댓글 링크
	$str_left[] = "[CMT_LINK]";
	$str_right[] = "
	<?php
	if (!empty(\$val['link']) && !empty(\$mini['board']['link_value']))
		foreach (\$val['link'] as \$key2=>\$val2):
			\$mini['skin']['link']['no'] = \$key2;
			\$mini['skin']['link']['value'] = \$val2;
	?>";

	$str_left[] = "[/CMT_LINK]";
	$str_right[] = "
	<?php endforeach; ?>";

// 댓글 추가필드
	$str_left[] = "[CMT_FIELD]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['field']) && !empty(\$val['field']) && !empty(\$mini['board']['use_cmt_field']))
		foreach (\$val['field'] as \$key2=>\$val2):
			\$mini['skin']['field']['name'] = empty(\$mini['board']['field'][\$key2]['name']) ? \$key2 : \$mini['board']['field'][\$key2]['name'];
			\$mini['skin']['field']['value'] = \$val2;
	?>";

	$str_left[] = "[/CMT_FIELD]";
	$str_right[] = "
	<?php endforeach; ?>";

// 태그
	$str_left[] = "[CMT_TAG]";
	$str_right[] = "
	<?php
	if (is_array(\$val['tag']))
		foreach(\$val['tag'] as \$key2 => \$val2):
			\$mini['skin']['tag']['name'] = \$val2;
			\$mini['skin']['tag']['is_first'] = (!\$key2);
			\$mini['skin']['tag']['url_view'] = \"mini.php?id={\$_REQUEST['id']}&amp;is_cmt=1&amp;s[tag]=\".urlencode(\$val2);
			\$mini['skin']['tag']['link_view'] = \"href='{\$mini['skin']['tag']['url_view']}' target='_parent'\";
	?>";

	$str_left[] = "[/CMT_TAG]";
	$str_right[] = "
	<?php endforeach; ?>";

// 추가필드 입력
	$str_left[] = "[CMT_FORM_FIELD]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['field']) && !empty(\$mini['board']['use_cmt_field']))
		foreach (\$mini['board']['field'] as \$key=>\$val):
			switch (\$val['mode']):
				case 'text':
					\$val['form'] = \"<input type='text' name='field[{\$key}]' class='fieldText' />\";
					break;

				case 'select':
					\$val['form'] = \"<select name='field[{\$key}]' class='fieldSelect'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;

				case 'checkbox':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='checkbox' name='field[{\$key}]' value='[:value:]' /><label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'radio':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='radio' name='field[{\$key}]' value='[:value:]' /> <label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'textarea':
					\$val['form'] = \"<textarea name='field[{\$key}]' class='fieldTextarea' cols='60' rows='5'></textarea>\";
					break;

				case 'select-multiple':
					\$val['form'] = \"<select name='field[{\$key}]' multiple='multiple' class='fieldSelectMulti'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;
			endswitch;

			\$mini['skin']['field'] = \$val;
	?>";

	$str_left[] = "[/CMT_FORM_FIELD]";
	$str_right[] = "
	<?php endforeach; ?>";

// 링크
	$str_left[] = "[CMT_FORM_LINK]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['link_value']))
		for (\$i=1; \$i<=\$mini['board']['link_value']; \$i++):
			\$mini['skin']['link']['no'] = \$i;
			\$mini['skin']['link']['name'] = \"link[{\$i}]\";
	?>";

	$str_left[] = "[/CMT_FORM_LINK]";
	$str_right[] = "
	<?php endfor; ?>";

// 트랙백 보내기
	$str_left[] = "[TRACKBACK_FORM]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['use_trackback'])) { 
	?>
	<form id='form_trackback' name='form_trackback' action='<?php echo \$mini['dir']; ?>ajax.php?url=<?php echo url(); ?>' method='post'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='mode' value='trackback' />
	<input type='hidden' name='pageKey' value='<?php if (!empty(\$_SESSION['pageKey'])) echo \$_SESSION['pageKey']; ?>' />";

	$str_left[] = "[/TRACKBACK_FORM]";
	$str_right[] = "
	</form>
	<div id='comment_end' style='clear:both;'></div>
	<?php } ?>";

// 글쓰기 폼
	$str_left[] = "[WRITE_FORM]";
	$str_right[] = "
	<form id='form_write' name='form_write' action='write.x.php<?php echo getURI(\"id, no, mode\", \"?\"); ?>' method='post' enctype='multipart/form-data'>
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='pageKey' value='<?php echo \$_SESSION['pageKey']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />";

	$str_left[] = "[/WRITE_FORM]";
	$str_right[] = "
	</form>";

// 글쓰기 추가필드
	$str_left[] = "[WRITE_FIELD]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['field']))
		foreach (\$mini['board']['field'] as \$key=>\$val):
			if (\$val['is_admin'] && empty(\$mini['member']['level_admin'])) continue;

			switch (\$val['mode']):
				case 'text':
					\$val['form'] = \"<input type='text' name='field[{\$key}]' class='formText' />\";
					break;

				case 'select':
					\$val['form'] = \"<select name='field[{\$key}]' class='formSelect'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;

				case 'checkbox':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='checkbox' name='field[{\$key}]' value='[:value:]' /><label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'radio':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='radio' name='field[{\$key}]' value='[:value:]' /> <label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'textarea':
					\$val['form'] = \"<textarea name='field[{\$key}]' class='formTextarea' style='width:80%;' cols='60' rows='5'></textarea>\";
					break;

				case 'select-multiple':
					\$val['form'] = \"<select name='field[{\$key}][]' multiple='multiple' class='formSelect'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;
			endswitch;

			\$mini['skin']['field'] = \$val;
	?>";

	$str_left[] = "[/WRITE_FIELD]";
	$str_right[] = "
	<?php endforeach; ?>";

// 글쓰기 링크
	$str_left[] = "[WRITE_LINK]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['board']['link_value']))
		for (\$i=1; \$i<=\$mini['board']['link_value']; \$i++):
			\$mini['skin']['link']['no'] = \$i;
			\$mini['skin']['link']['name'] = \"link[{\$i}]\";
	?>";

	$str_left[] = "[/WRITE_LINK]";
	$str_right[] = "
	<?php endfor; ?>";

// 글쓰기 핑백
	$str_left[] = "[WRITE_PINGBACK]";
	$str_right[] = "
	<?php 
	if (!empty(\$mini['skin']['data']['pingback_arr']) && is_array(\$mini['skin']['data']['pingback_arr'])) {
		foreach(\$mini['skin']['data']['pingback_arr'] as \$key => \$val):
			\$mini['skin']['pingback']['url'] = \$val;
			\$mini['skin']['pingback']['is_first'] = (!\$key);
	?>";

	$str_left[] = "[/WRITE_PINGBACK]";
	$str_right[] = "
	<?php endforeach; } ?>";

// 글쓰기 파일업로드
	$str_left[] = "[FILE_SWF]";
	$str_right[] = '
		<?php
			def($mini[\'setting\'][\'file_name\'], \'iiFile\');
			def($mini[\'setting\'][\'file_button_width\'], \'60\');
			def($mini[\'setting\'][\'file_button_height\'], \'20\');
			def($mini[\'setting\'][\'file_button_bgcolor\'], \'#d1d1d1\');
		?>

		<?php if (!empty($mini[\'skin\'][\'data\'][\'is_file\'])) { ?>
		<script type=\'text/javascript\'>
		//<![CDATA[
		window.addEvent(\'load\', function () {
			toggleMenu("bottomContent_file", "", $("bottomMenu_file"));
			$(\'bottomContent_tag\').toggle(\'hide\');
		});
		//]]>
		</script>
		<?php } ?>

		<!-- swfupload 설정 -->
		<script type=\'text/javascript\' src=\'<?php echo $mini[\'skin\'][\'rdir\']; ?>addon/iiUpload/iiUpload.js\'></script>
		<script type=\'text/javascript\' src=\'<?php echo $mini[\'skin\'][\'rdir\']; ?>js/mini.file.js\'></script>
		<script type=\'text/javascript\'>
		//<![CDATA[
		<?php
		$limitsize = getByte(get_cfg_var("upload_max_filesize"), \'decode\');
		?>

		var <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload = new MiniFileUpload({
			name: \'<?php echo $mini[\'setting\'][\'file_name\']; ?>\',
			target: $(\'fileUpload\'),
			form: $(\'<?php echo $mini[\'setting\'][\'file_name\'] == \'iiFile\' ? \'form_write\' : \'form_cmt\'; ?>\'),

			flash_url: \'<?php echo $mini[\'skin\'][\'rdir\']; ?>addon/iiUpload/iiUpload.swf\',
			use_swfupload: \'<?php echo !empty($mini[\'set\'][\'use_swfupload\']) ? 1 : 0; ?>\',
			button_url: \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/button_upload.png\',
			upload_url: \'<?php echo dirname($_SERVER[\'PHP_SELF\']); ?>/upload.php\',
			exec_url: \'<?php echo $mini[\'skin\'][\'rdir\']; ?>file.x.php?id=<?php echo $_REQUEST[\'id\']; ?>&no=<?php echo $_REQUEST[\'no\']; ?>\',
			width: <?php echo $mini[\'setting\'][\'file_button_width\']; ?>,
			height: <?php echo $mini[\'setting\'][\'file_button_height\']; ?>,
			bgcolor: \'<?php echo $mini[\'setting\'][\'file_button_bgcolor\']; ?>\',

			params: {
				"sid": "<?php echo session_id(); ?>",
				"id" : "<?php echo $_REQUEST[\'id\']; ?>",
				"mode" : "<?php echo $mini[\'setting\'][\'file_name\'] == \'iiFile\' ? \'post\' : \'comment\'; ?>",
				"swf" : "1",
				"pageURL" : "<?php echo url(); ?>",
				"no" : "<?php echo $mini[\'setting\'][\'file_name\'] == \'iiFile\' ? $_REQUEST[\'no\'] : \'\'; ?>",
				<?php echo $mini[\'setting\'][\'file_name\'] == \'iiFile\' ? \'\' : "\\"target_post\\" : \\"{$_REQUEST[\'no\']}\\","; ?>
				"pass_encode" : "<?php if (!empty($_REQUEST[\'pass_encode\'])) echo $_REQUEST[\'pass_encode\']; ?>"
			},
			onSelectEach: function (no, name, size) {
				var limit = <?php echo !empty($mini[\'board\'][\'file_limit_each\']) ? $mini[\'board\'][\'file_limit_each\'] : "0"; ?>;
				if (limit && limit * 1048576 < size) {
					error(\'파일 용량이 \' + (limit * 1048576) + \'bytes를 초과했습니다\');
					return false;
				}

				return true;
			},
			onSelect: function (count, size) {
				var limit = <?php echo !empty($mini[\'board\'][\'file_limit\']) ? min($mini[\'board\'][\'file_limit\'], $limitsize) : $limitsize; ?>;
				if (count + parseInt($(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Count\').innerHTML) > <?php echo $mini[\'board\'][\'file_value\']; ?>) {
					error(\'파일은 <?php echo $mini[\'board\'][\'file_value\']; ?>개까지만 올릴 수 있습니다\');
					return false;
				}
				if (size + parseInt($(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Size\').innerHTML) > limit * 1048576) {
					error(\'총 업로드 용량이 <?php echo $mini[\'board\'][\'file_limit\']; ?>M를 초과했습니다\');
					return false;
				}

				return true;
			},
			onEnd: function (no, item) {
				if ($chk(item)) {
					var data = setJSON(item);
					if ($chk(data[\'error\']) && (data[\'error\'] == \'1\' || data[\'error\'] == 1)) {
						if (data[\'mode\'].toString().match(/goto/)) {
							__script(data);
						}
						else {
							error(data[\'msg\']);
						}
					}
					else {
						<?php echo $mini[\'setting\'][\'file_name\']; ?>AddItem(data[\'data\']);
					}
				}
			},
			onError: function (msg) { alert(\'파일 업로드 실패: \' + msg); }
		});
		//]]>
		</script>

		<script type=\'text/javascript\'>
		//<![CDATA[
		// 파일추가
		function <?php echo $mini[\'setting\'][\'file_name\']; ?>AddItem(data) {
			// 값 수정
			if (!$chk(data["point"])) data["point"] = 0;
			if (!$chk(data["hit"])) data["hit"] = 0;
			if (!$chk(data["download"])) data["download"] = 0;
			
			var output = \'\';

			// 파일 종류에 따른 썸네일 선택
				var thumb = \'\';
				switch (data[\'type\']) {
					case \'image\':
						thumb = \'download.php?mode=view&no=\' + data["no"];
						break;
					case \'music\':
						thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/music.gif\';
						break;
					case \'movie\':
						thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/movie.gif\';
						break;
					case \'swf\':
					case \'flv\':
						thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/flv.gif\';
						break;
					default:
						// 추가 타입
						switch (data[\'ext\']) {
							case \'rar\':
							case \'zip\':
							case \'alz\':
							case \'tar\':
							case \'gz\':
							case \'bz\':
							case \'7z\':
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/zip.gif\';
								break;
							case \'doc\':
							case \'docx\':
							case \'xls\':
							case \'xlsx\':
							case \'ppt\':
							case \'pptx\':
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/office.gif\';
								break;
							case \'hwp\':
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/hwp.gif\';
								break;
							case \'txt\':
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/word.gif\';
								break;
							case \'psd\':
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/psd.gif\';
								break;
							default:
								thumb = \'<?php echo $mini[\'skin\'][\'dir\']; ?>image/ext/other.gif\';
						}
				}

			// html 삽입
			output =
			"<li id=\'<?php echo $mini[\'setting\'][\'file_name\']; ?>_" + data["no"] + "\' style=\'list-style-type:none; margin-bottom:5px;\'>"
			+ "<table border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'width:95%;\'><tr>"
			+ "<td width=\'30\' rowspan=\'2\'><input type=\'checkbox\' name=\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Sel[]\' value=\'" + data["no"] + "\' /></td>"
			+ "<td width=\'70\' rowspan=\'2\'><img src=\'" + thumb + "\' style=\'width:50px; height:35px; border:5px solid #ccc; cursor:pointer;\' alt=\'미리보기\' onclick=\'window.open(\""+(data["type"] == "image" ? miniDir + "addon/phpthumb/preview.php?no=" + data["no"] : miniDir + "download.php?mode=view&no=" + data["no"]) + "\",\"ie_preview\",\"width=640, height=480, resizable=1\");\' /></td>"
			+ "<td>"
				+ "<span style=\'font-size:10px; font-family:verdana;\'>" + data["no"] + "</span> <span style=\'font-size:11px; font-weight:bold; color:#333; font-family:dotum;\'>" + data["name"] + "</span> <span style=\'font-size:10px; font-family:tahoma;\'>(" + data["size_out"] + ")</span> "
			+ "</td>"
			+ "</tr>"
			+ "<tr>"
				+ "<td><span class=\'word kor_s\'>설명&nbsp;&nbsp;<input type=\'text\' name=\'" + <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting["name"] + "Title[" + data["no"] + "]\' value=\'" + data["title"] + "\' class=\'formText\' style=\'width:50%;\' /></span><span class=\'kor_s\'>포인트&nbsp;&nbsp; <input type=\'text\' name=\'" + <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting["name"] + "Point[" + data["no"] + "]\' value=\'" + data["point"] + "\' class=\'formText\' style=\'width:50px; text-align:right;\' /></span>"
				+ "&nbsp;&nbsp;&nbsp;<img src=\'<?php echo $mini[\'skin\'][\'dir\']; ?>image/icon/edit.gif\' border=\'0\' style=\'vertical-align:middle; cursor:pointer;\' alt=\'수정\' onclick=\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Edit(" + data["no"] + ");\' />"
				+ " <img src=\'<?php echo $mini[\'skin\'][\'dir\']; ?>image/icon/x_gray.gif\' border=\'0\' style=\'vertical-align:middle; cursor:pointer;\' alt=\'삭제\' onclick=\'<?php echo $mini[\'setting\'][\'file_name\']; ?>DelItem(" + data["no"] + ");\' />"
				+ "</td>"
			+ "</tr></table>"
			+ "</li>";

			$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>\').innerHTML += output;
			
			<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.addFile(data);
			$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Count\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.count;
			$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Size\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.size;
			<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.addMent(data[\'no\']);
		};

		// 파일삭제
		function <?php echo $mini[\'setting\'][\'file_name\']; ?>DelItem(no, mode) {
			if (mode || confirm("파일을 삭제하시겠습니까?")) {
				if ($chk($(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>_\' + no))) $(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>_\' + no).remove();
			
				ajaxForm({
					url: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'exec_url\'] + \'&mode=del&target=\' + no,
					onComplete: function (data) {
						<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.delFile(no);
						$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Count\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.count;
						$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Size\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.size;
					}
				});
			}
		};

		// 선택파일 삭제
		function <?php echo $mini[\'setting\'][\'file_name\']; ?>DelItems() {
			var data = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.getQueryString(\'del\');
			
			if (data) {
				if (confirm("선택한 파일들을 삭제하시겠습니까?")) {
					ajaxForm({
						url: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'exec_url\'] + \'&mode=del\',
						values: data,
						onComplete: function (item) {
							var matches = data.toString().match(/sel\[\]=([0-9]+)/g);
							for (var i=0; i < matches.length; i++) {
								<?php echo $mini[\'setting\'][\'file_name\']; ?>DelItem(parseInt(matches[i].toString().replace(/[^0-9]+/g, \'\')), 1);
							}
							$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Count\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.count;
							$(\'<?php echo $mini[\'setting\'][\'file_name\']; ?>Size\').innerHTML = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.size;
						}
					});
				}
			}
			else {
				error(\'파일을 선택해 주세요\');
			}
		};

		// 설명수정
		function <?php echo $mini[\'setting\'][\'file_name\']; ?>Edit(no) {
			var ins = {
				title: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'form\'].elements[<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'name\'] + \'Title[\' + no + \']\'].value,
				point: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'form\'].elements[<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'name\'] + \'Point[\' + no + \']\'].value
			};				

			ajaxForm({
				url: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'exec_url\'] + \'&mode=text&target=\' + no,
				values: ins,
				onComplete: function (data) {
					__script(data);
				}
			});
		};

		// 선택 설명수정
		function <?php echo $mini[\'setting\'][\'file_name\']; ?>Edits() {
			var data = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.getQueryString();
			var sel = {};
			var no = 0;

			var obj = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.getCheckbox();
			for (var i = 0; i < obj.length; i++) {
				no = obj[i].value;
				sel[\'sel[\' + no + \']\'] = no;
				sel[\'title[\' + no + \']\'] = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'form\'].elements[<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'name\'] + \'Title[\' + no + \']\'].value;
				sel[\'point[\' + no + \']\'] = <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'form\'].elements[<?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'name\'] + \'Point[\' + no + \']\'].value;
			}
			
			if (data) {
				ajaxForm({
					url: <?php echo $mini[\'setting\'][\'file_name\']; ?>Upload.setting[\'exec_url\'] + \'&mode=text\',
					values: sel,
					onComplete: function (item) {
						__script(item);
					}
				});
			}
			else {
				error(\'파일을 선택해 주세요\');
			}
		};

		// 기존값 적용
		<?php
		if ($mini[\'setting\'][\'file_name\'] == \'iiFile\' && !empty($mini[\'skin\'][\'data\'][\'files\'])) {
			foreach ($mini[\'skin\'][\'data\'][\'files\'] as $key=>$val):
				unset($val[\'history_hit\']);
				parseFile($val);
				echo "{$mini[\'setting\'][\'file_name\']}AddItem(".setJSON($val).");\n";
			endforeach;
		}
		?>
		//]]>
		</script>';
	$str_left[] = "[/FILE_SWF]";
	$str_right[] = "";
break;



#회원가입
case 'member.php':
case 'agree.php':

// 폼					
	$str_left[] = "[FORM]";
	$str_right[] = "
	<form id='form_join' name='form_join' action='member.x.php<?php echo getURI(\"mode, no\", \"?\"); ?>' method='post' enctype='multipart/form-data'>
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='completeMode' value='ajax,alert,reload.parent' />";

	$str_left[] = "[/FORM]";
	$str_right[] = "
	</form>";

// 추가필드
	$str_left[] = "[FIELD]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['site']['field']))
		foreach (\$mini['site']['field'] as \$key=>\$val):
			switch (\$val['mode']):
				case 'text':
					\$val['form'] = \"<input type='text' name='field[{\$key}]' class='formText' />\";
					break;

				case 'select':
					\$val['form'] = \"<select name='field[{\$key}]' class='formSelect'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;

				case 'checkbox':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='checkbox' name='field[{\$key}]' value='[:value:]' /><label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'radio':
					\$val['form'] = getOption(\"
						str: {\$val['items']}
						skin: <input id='field{\$key}[:rand:]' type='radio' name='field[{\$key}]' value='[:value:]' /> <label for='field{\$key}[:rand:]'>[:key:]</label>
					\");
					break;

				case 'textarea':
					\$val['form'] = \"<textarea name='field[{\$key}]' class='formTextarea' cols='60' rows='5'></textarea>\";
					break;

				case 'select-multiple':
					\$val['form'] = \"<select name='field[{\$key}]' multiple='multiple' class='formSelect'>\".getOption(\"str:{\$val['items']}\").\"</select>\";
					break;
			endswitch;

			\$mini['skin']['field'] = \$val;
			\$mini['skin']['field']['join_setting'] = !empty(\$mini['site']['join_setting'][\"field_{\$key}\"]);
			\$mini['skin']['field']['join_check'] = !empty(\$mini['site']['join_check'][\"field[{\$key}]\"]);

			if (\$mini['skin']['field']['join_setting']) {
	?>";

	$str_left[] = "[/FIELD]";
	$str_right[] = "
	<?php } endforeach; ?>";

// 약관 폼
	$str_left[] = "[AGREE]";
	$str_right[] = "
	<form id='form_agree' name='form_agree' action='<?php echo str_replace(\"&\", \"&amp;\", urldecode(url())); ?>' method='post'>";

	$str_left[] = "[/AGREE]";
	$str_right[] = "
	</form>";
break;


#파일
case 'file.php':


// 업로드 폼
	$str_left[] = "[UPLOAD]";
	$str_right[] = "
	<form id='form_upload' name='form_upload' action='upload.php' method='post' enctype='multipart/form-data'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='swf' value='<?php echo !empty(\$mini['set']['use_swfupload']) ? 1 : 0; ?>' />
	<input type='hidden' name='pass_encode' value='<?php if (!empty(\$_REQUEST['pass_encode'])) echo \$_REQUEST['pass_encode']; ?>' />";

	$str_left[] = "[/UPLOAD]";
	$str_right[] = "
	</form>";

// 파일목록 폼
	$str_left[] = "[FORM]";
	$str_right[] = "
	<form id='form_file' name='form_file' action='file.x.php' method='post'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />";

	$str_left[] = "[/FORM]";
	$str_right[] = "
	</form>";

// 툴 폼
	$str_left[] = "[FORM_TOOL]";
	$str_right[] = "
	<form id='form_tool' name='form_tool' method='post'>
	<input type='hidden' name='target' />";

	$str_left[] = "[/FORM_TOOL]";
	$str_right[] = "
	</form>";

	break;



#마이메뉴
case 'mymenu.php':

// 메신져
	$str_left[] = "[VIEW_CHAT]";
	$str_right[] = "
	<?php
	if (!empty(\$mini['skin']['data']['chat'])) {
		foreach (\$mini['skin']['data']['chat'] as \$key => \$val):
			\$mini['skin']['chat']['mode'] = \$val['mode'];
			\$mini['skin']['chat']['value'] = \$val['value'];
	?>";

	$str_left[] = "[/VIEW_CHAT]";
	$str_right[] = "
	<?php endforeach; } ?>";

// 쪽지
	$str_left[] = "[MEMO_FORM]";
	$str_right[] = "
	<form id='form_memo_list' name='form_memo_list' action='mymenu.x.php' method='post'>
	<input type='hidden' name='mode' />
	<input type='hidden' name='mode2' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='url' value='<?php echo url(); ?>' />
	<input type='hidden' name='completeMode' value='ajax,reload' />";

	$str_left[] = "[/MEMO_FORM]";
	$str_right[] = "
	</form>";

// 쪽지 목록
	$str_left[] = "[MEMO_LOOP]";
	$str_right[] = "
	<?php
	global \$data;
	if (!empty(\$data) && is_array(\$data) && count(\$data) >= 1)
		foreach (\$data as \$key=>\$val):
			parseMemo(\$val);
			\$val['ment_notag'] = strCut(\$val['ment_notag'], 30, '...');
			if (\$val['target_member'] == \$mini['member']['no']) {
				\$val['name_him'] = \$val['name_from'];
				\$val['js_send_him'] = \$val['js_send_from'];
			}
			if (\$val['from_member'] == \$mini['member']['no']) {
				\$val['name_him'] = \$val['name_target'];
				\$val['js_send_him'] = \$val['js_send_target'];
			}
			\$mini['skin']['data'] = &\$val;

	?>";

	$str_left[] = "[/MEMO_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	?>";

// 친구목록
	$str_left[] = "[MEMO_FRIEND_LOOP]";
	$str_right[] = "
	<?php
	global \$data;
	if (!empty(\$data) && is_array(\$data) && count(\$data) >= 1)
		foreach (\$data as \$key=>\$val):
			\$mini['skin']['data'] = &\$val;

	?>";

	$str_left[] = "[/MEMO_FRIEND_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	?>";

// 포인트기록 목록
	$str_left[] = "[LOG_POINT_LOOP]";
	$str_right[] = "
	<?php
	global \$data;
	if (!empty(\$data) && is_array(\$data) && count(\$data) >= 1)
		foreach (\$data as \$key=>\$val):
			\$mini['skin']['data'] = &\$val;

	?>";

	$str_left[] = "[/LOG_POINT_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	?>";
break;



#쪽지쓰기
case 'memo.write.php':

// 쪽지보내기
	$str_left[] = "[MEMO_WRITE_FORM]";
	$str_right[] = "
	<form id='form_memo_write' name='form_memo_write' action='ajax.php' method='post'>
	<input type='hidden' name='mode' value='send_memo' />
	<input type='hidden' name='target_member' value='<?php echo \$_REQUEST['no']; ?>' />";

	$str_left[] = "[/MEMO_WRITE_FORM]";
	$str_right[] = "
	</form>";
break;



#신고
case 'report.php':

// 폼
	$str_left[] = "[FORM]";
	$str_right[] = "
	<form id='form_report' name='form_report' action='report.x.php' method='post'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />";

	$str_left[] = "[/FORM]";
	$str_right[] = "
	</form>";
break;



#비밀번호
case 'pass.php':

// 폼
	$str_left[] = "[FORM]";
	$str_right[] = "
	<form id='form_pass' name='form_pass' action='<?php echo \$data['path']; ?>' method='get'<?php if (!empty(\$_REQUEST['target'])) echo \" target='{\$_REQUEST['target']}'\"; ?>>
	<?php if (!empty(\$data['form'])) echo \$data['form']; ?>";

	$str_left[] = "[/FORM]";
	$str_right[] = "
	</form>";
break;



#자료관리
case 'manage.php':

// 폼
	$str_left[] = "[FORM]";
	$str_right[] = "
	<form id='form_manage' name='form_manage' action='manage.x.php' method='post'>
	<input type='hidden' name='id' value='<?php echo \$_REQUEST['id']; ?>' />
	<input type='hidden' name='mode' value='<?php echo \$_REQUEST['mode']; ?>' />
	<input type='hidden' name='report' value='<?php echo (!empty(\$_REQUEST['report']) ? 1 : 0); ?>' />
	<?php
		foreach (\$_REQUEST['no'] as \$val):
			echo \"<input type='hidden' name='no[]' value='{\$val}' />\n\";
		endforeach;
	?>";

	$str_left[] = "[/FORM]";
	$str_right[] = "
	</form>";

// 신고자 목록
	$str_left[] = "[REPORTER_LOOP]";
	$str_right[] = "
	<?php
	global \$target_name, \$report;
	
	\$tmp_ment = getStr(\$report['ment']);

	if (!empty(\$target_name) && is_array(\$target_name) && count(\$target_name) >= 1)
		foreach (\$target_name as \$key=>\$val):
			\$mini['skin']['name'] = \$val;
			\$mini['skin']['ment'] = \$tmp_ment[\$key];
	?>";

	$str_left[] = "[/REPORTER_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	?>";

// 신고사유 목록
	$str_left[] = "[REPORTER_MENT_LOOP]";
	$str_right[] = "
	<?php
	global \$report;

	if (!empty(\$report['ment'])) {
		foreach ( as \$key=>\$val):
			\$mini['skin']['ment'] = \$val;
	?>";

	$str_left[] = "[/REPORTER_MENT_LOOP]";
	$str_right[] = "
	<?php
		endforeach;
	}
	?>";
break;



#아이디/비밀번호 찾기
case 'login.find.php':

// 찾기
	$str_left[] = "[FIND]";
	$str_right[] = "
	<form id='form_find' name='form_find' action='login.find.php<?php echo getURI(\"mode\", \"?\"); ?>' method='post'>
	<input type='hidden' name='mode' />
	<input type='hidden' name='site' value='<?php echo \$mini['site']['no']; ?>' />
	";

	$str_left[] = "[/FIND]";
	$str_right[] = "
	</form>";

// 비밀번호 변경
	$str_left[] = "[FIND_PASS]";
	$str_right[] = "
	<form id='form_find_pass' name='form_find_pass' action='login.find.php<?php echo getURI(\"mode\", \"?\"); ?>' method='post'>
	<input type='hidden' name='mode' value='pass_ok' />
	<input type='hidden' name='no' value='<?php echo \$_REQUEST['no']; ?>' />
	<input type='hidden' name='site' value='<?php echo \$mini['site']['no']; ?>' />
	<input type='hidden' name='answer' value='<?php if (!empty(\$_REQUEST['qna_answer'])) echo \$_REQUEST['qna_answer']; ?>' />
	";

	$str_left[] = "[/FIND_PASS]";
	$str_right[] = "
	</form>";
break;
endswitch;


#모든곳
// 로그인
	$str_left[] = "[LOGIN]";
	$str_right[] = "
	<form id='form_login' name='form_login' action='login.php<?php echo getURI(\"mode\", \"?\"); ?>' method='post'>
	<input type='hidden' name='mode' value='login' />
	<input type='hidden' name='completeMode' value='ajax,reload.parent' />
	<input type='hidden' name='completeScript' value='login_autosave' />";

	$str_left[] = "[LOGIN_NORMAL]";
	$str_right[] = "
	<form id='form_login' name='form_login' action='login.php<?php echo getURI(\"mode\", \"?\"); ?>' method='post'>
	<input type='hidden' name='mode' value='login' />";

	$str_left[] = "[/LOGIN]";
	$str_right[] = "
	</form>";

// 툴 레이어
	$str_left[] = "[TOOL /]";
	$str_right[] = "
	<div id='tool' style='display:none;' class='tool'>
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/x_gray.gif' border='0' class='hand' style='vertical-align:middle;' alt='삭제' title='삭제' <?php echo \$mini['skin']['js_post_del']; ?> />
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/alert.gif' border='0' class='hand' style='vertical-align:middle;' alt='관리' title='관리' <?php echo \$mini['skin']['js_pop_post_manage']; ?> />
	</div>
	";

// 툴 댓글 레이어
	$str_left[] = "[TOOL_CMT /]";
	$str_right[] = "
	<div id='tool_cmt' style='display:none;' class='tool'>
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/x_gray.gif' border='0' class='hand' style='vertical-align:middle;' alt='삭제' title='삭제' <?php echo \$mini['skin']['js_cmt_del']; ?> />
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/alert.gif' border='0' class='hand' style='vertical-align:middle;' alt='관리' title='관리' <?php echo \$mini['skin']['js_pop_cmt_manage']; ?> />
	</div>";

// 툴 쪽지 친구목록 레이어
	$str_left[] = "[TOOL_MEMO /]";
	$str_right[] = "
	<div id='tool' style='display:none;' class='tool'>
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/against.gif' border='0' class='hand' style='vertical-align:middle;' alt='차단'1 title='차단' <?php echo \$mini['skin']['js_memo_block']; ?> />
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/x_gray.gif' border='0' class='hand' style='vertical-align:middle;' alt='삭제' title='삭제' <?php echo \$mini['skin']['js_friend']; ?> />
	</div>";

// 툴 쪽지 목록 레이어
	$str_left[] = "[TOOL_MEMO_LIST /]";
	$str_right[] = "
	<div id='tool' style='display:none;' class='tool'>
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/paper-clip.gif' border='0' class='hand' style='vertical-align:middle;' alt='보관' title='보관' <?php echo \$mini['skin']['js_memo_save_action']; ?> />
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/x_gray.gif' border='0' class='hand' style='vertical-align:middle;' alt='삭제' title='삭제' <?php echo \$mini['skin']['js_del']; ?> />
	</div>";

// 툴 쪽지 보관함 레이어
	$str_left[] = "[TOOL_MEMO_SAVE /]";
	$str_right[] = "
	<div id='tool' style='display:none;' class='tool'>
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/paper-clip.gif' border='0' class='hand' style='vertical-align:middle;' alt='보관' title='보관' <?php echo \$mini['skin']['js_memo_save_action']; ?> />
		<img src='<?php echo \$mini['skin']['dir']; ?>image/icon/x_gray.gif' border='0' class='hand' style='vertical-align:middle;' alt='삭제' title='삭제' <?php echo \$mini['skin']['js_del']; ?> />
	</div>";


		
		//// 목록 호출
			if (strpos($output, "[LIST_INC]") !== false) {
				$fp = fopen("{$mini['sdir']}list.php", "r");
				$tmp_include_data = '';
				while (!feof($fp)):
					$tmp_include_data .= fgets($fp, 4096);
				endwhile;
				fclose($fp);

				unset($mat);
				preg_match("/\<\!\-\- \[LIST\] \-\-\>(.+)\<\!\-\- \[\/LIST\] \-\-\>/sU", $tmp_include_data, $mat);

				$str_left[] = "[LIST_INC]";
				$str_right[] = $mat[1];
				$str_left[] = "[/LIST_INC]";
				$str_right[] = "";
				unset($tmp_include_data);
				unset($mat);
			}

		//// 검색 호출
			if (strpos($output, "[SEARCH_INC]") !== false) {
				$fp = fopen("{$mini['sdir']}list.php", "r");
				$tmp_include_data = '';
				while (!feof($fp)):
					$tmp_include_data .= fgets($fp, 4096);
				endwhile;
				fclose($fp);

				unset($mat);
				preg_match("/\<\!\-\- \[SEARCH\] \-\-\>(.+)\<\!\-\- \[\/SEARCH\] \-\-\>/sU", $tmp_include_data, $mat);

				$str_left[] = "[SEARCH_INC]";
				$str_right[] = $mat[1];
				$str_left[] = "[/SEARCH_INC]";
				$str_right[] = "";
				unset($tmp_include_data);
				unset($mat);
			}

		//// 댓글 목록 호출
			if (strpos($output, "[CMT_INC /]") !== false) {
				$str_left[] = "[CMT_INC /]";
				$str_right[] = "
				<?php 
				if (!empty(\$mini['board']['use_comment'])) {
					include \"{$mini['sdir']}cmt.php\";
				}
				?>";
			}

		//// 치환
			$output = str_replace($str_left, $str_right, $output);
			$str_left = $str_right = array();

		//// PHP 구문은 먼저 뺀다
			preg_match_all("/\<\?(.+)\?\>/isU", $output, $mat);
			foreach ($mat[0] as $key=>$val):
				$output = str_replace($val, "<!--exchange-miniboard-{$key}-->", $output);
			endforeach;

		//// 특별
			// depth 증가
			$preg_left[] = "/\[depth:([^\\]]*)\]/i";
			$preg_right[] = "<?php echo !preg_match(\"/[^0-9]/\", \"\\1\") ? (int)(\"\\1\" * \$val['depth']) : str_repeat(\"\\1\", \$val['depth']); ?>";
			$preg_left[] = "/\[depth0:([^\\]]*)\]/i";
			$preg_right[] = "<?php echo !preg_match(\"/[^0-9]/\", \"\\1\") ? (int)(\"\\1\" * \$val['depth']-1) : str_repeat(\"\\1\", \$val['depth']-1); ?>";


			// include
			$preg_left[] = "/\[include:([^\]]+)\]/i";
			$preg_right[] = "<?php include \"{$mini['dir']}\\1\"; ?>";

			// 스킨경로가 포함된 include
			if (!empty($mini['sdir'])) {
				$preg_left[] = "/\[sinclude:([^\]]+)\]/i";
				$preg_right[] = "<?php include \"{$mini['sdir']}\\1\"; ?>";
			}

			// 주석
			$preg_left[] = "/^\s*?\#\#.+$/m";
			$preg_right[] = "";

		//// 논리문
			$preg_left[] = "/\[if:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (!empty(\$mini['skin']['\\1']['\\2']['\\3']['\\4'])) { ?>";
			$preg_left[] = "/\[if:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (!empty(\$mini['skin']['\\1']['\\2']['\\3'])) { ?>";
			$preg_left[] = "/\[\!if:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (empty(\$mini['skin']['\\1']['\\2']['\\3'])) { ?>";
			$preg_left[] = "/\[if:([a-z0-9_]+)\.([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (!empty(\$mini['skin']['\\1']['\\2'])) { ?>";
			$preg_left[] = "/\[\!if:([a-z0-9_]+)\.([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (empty(\$mini['skin']['\\1']['\\2'])) { ?>";
			$preg_left[] = "/\[if:([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (!empty(\$mini['skin']['\\1'])) { ?>";
			$preg_left[] = "/\[\!if:([a-z0-9_]+)\]/i";
			$preg_right[] = "<?php if (empty(\$mini['skin']['\\1'])) { ?>";

			$str_left[] = "[endif]";
			$str_right[] = "<?php } ?>";

		//// 변수
			$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+):\]/i";
			$preg_right[] = "<?php echo \$mini['skin']['\\1']['\\2']['\\3']['\\4']; ?>";
			$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+):\]/i";
			$preg_right[] = "<?php echo \$mini['skin']['\\1']['\\2']['\\3']; ?>";
			$preg_left[] = "/\[:([a-z0-9_]+)\.([a-z0-9_]+):\]/i";
			$preg_right[] = "<?php echo \$mini['skin']['\\1']['\\2']; ?>";
			$preg_left[] = "/\[:([a-z0-9_]+):\]/i";
			$preg_right[] = "<?php echo \$mini['skin']['\\1']; ?>";

		//// 변환
			$output = str_replace($str_left, $str_right, $output);
			$output = preg_replace($preg_left, $preg_right, $output);

		//// PHP 구문을 붙인다
			$output = preg_replace("/\<\!\-\-exchange\-miniboard\-([0-9]+)\-\-\>/e", '$mat[0][\\1]', $output);

		if ($mode == 'url') {
			//// 쓰기
				$fp = fopen(preg_replace("/\.mini$/i", ".php", $url), "w+");
				flock($fp, LOCK_EX);
				if (!$fp || !fwrite($fp, $output))
					__error("[{$url}] 파일 쓰기를 실패 했습니다. 파일 권한을 확인해 보세요");
				flock($fp, LOCK_UN);
				fclose($fp);
		}
		else {
			$output = preg_replace_callback("/\<\?(php)? echo ([^;]+)\; \?\>/i",
				create_function(
					'$matches',
					"global \$mini; return eval(\"return \".stripslashes(\$matches[2]).\";\");"
				), $output
			);

			return $output;
		}
	}
} // END function
?>