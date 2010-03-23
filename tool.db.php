<?php
if (!empty($_REQUEST['exec'])) {
	include "_inc.php";
	include "_inc.admin.php";
}

$install_table = array();

// -----------------------------------------------------------
$install_table['admin'] = array("게시판 관리자","
CREATE TABLE {$mini['name']['admin']} (
	# 기본
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT, #관리번호(모든 자료에는 id대신 관리번호가 들어갑니다)
	id varchar(255) not null default '',			#아이디
	name varchar(255) not null default '',			#이름
	site mediumint UNSIGNED not null default 0,		#그룹
	site_link text,									#그룹추가 [str]
	total text,										#*총게시물 수 *(처리표시)

	# 스킨 / 외형
	skin varchar(255) not null default '',			#스킨-게시판
	skin_login varchar(255) not null default '',	#로그인 스킨
	skin_join varchar(255) not null default '',		#회원가입 스킨
	skin_pass varchar(255) not null default '',		#비밀번호 스킨
	skin_file varchar(255) not null default '',		#파일 스킨
	skin_report varchar(255) not null default '',	#신고 스킨
	width varchar(255) not null default '',			#게시판 너비 [숫자px%]
	cut_title int(4) not null default 0,			#제목 자르기 [숫자]
	list_count int(4) not null default 15,			#목록 수 [숫자]
	list_count_relate int(2) not null default 10,	#이전/다음글 수 [숫자]
	list_count_comment int(4) not null default 60,	#댓글 목록 수 [숫자]
	cmt_skin varchar(255) not null default '',		#댓글 표시 스킨
	cmt_skin_new varchar(255) not null default '',	#댓글 최신 표시 스킨
	sort varchar(255) not null default '',			#기본 정렬 [필드명]
	sort_desc int(1) not null default 0,			#내림차순 여부 [0,1]
	width_image varchar(255) not null default '',	#이미지 너비 [숫자px] 없으면 제한 안함
	link_value int(4) not null default 0,			#링크 [숫자] 없으면 사용 안함
	date_list varchar(255) not null default 'Y/m/d',		#날짜형식 - 글목록
	date_view varchar(255) not null default 'Y/m/d H:i:s',	#날짜형식 - 글보기
	date_comment varchar(255) not null default 'm/d H:i',	#날짜형식 - 댓글
	header_url varchar(255) not null default '',	#header URL
	footer_url varchar(255) not null default '',	#footer URL
	header text,									#머리말
	footer text,									#꼬리말
	head text,										#<head>내용
	body varchar(255) not null default '',			#<body>내용
	
	#카테고리
	category text,									#카테고리

	#권한
	permit_list varchar(255) not null default '',	#목록
	permit_list_and int(1) not null default 0,
	permit_view varchar(255) not null default '',	#글보기
	permit_view_and int(1) not null default 0,
	permit_write varchar(255) not null default '',	#글쓰기
	permit_write_and int(1) not null default 0,
	permit_edit varchar(255) not null default '',	#글수정권한(아무나)
	permit_edit_and int(1) not null default 0,
	permit_comment varchar(255) not null default '',#댓글쓰기
	permit_comment_and int(1) not null default 0,
	permit_secret varchar(255) not null default '',	#비밀글
	permit_secret_and int(1) not null default 0,
	permit_notice varchar(255) not null default '',	#공지사항
	permit_notice_and int(1) not null default 0,
	permit_html varchar(255) not null default '',	#HTML쓰기
	permit_html_and int(1) not null default 0,
	permit_upload varchar(255) not null default '',	#업로드
	permit_upload_and int(1) not null default 0,
	permit_download varchar(255) not null default '',	#다운로드
	permit_download_and int(1) not null default 0,
	permit_search varchar(255) not null default '',	#검색
	permit_search_and int(1) not null default 0,
	permit_trackback varchar(255) not null default '',	#트랙백(큐브사용시 가능)
	permit_trackback_and int(1) not null default 0,
	permit_report varchar(255) not null default '',	#신고x
	permit_report_and int(1) not null default 0,

	#사용기능
	use_category int(1) not null default 1,			#카테고리
	use_tag int(1) not null default 1,				#태그
	use_cube int(1) not null default 1,				#큐브사용
	use_notice int(1) not null default 1,			#공지사항
	use_secret int(1) not null default 1,			#비밀글
	use_alert int(1) not null default 1,			#경고
	use_robot int(1) not null default 1,			#검색로봇
	use_comment int(1) not null default 1,			#댓글
	use_comment_page int(1) not null default 1,		#댓글 페이지
	use_feed int(1) not null default 1,				#RSS
	use_trackback int(1) not null default 1,		#트랙백
	use_trackback_rdf int(1) not null default 1,	#트랙백RDF
	use_trackback_cmt int(1) not null default 1,	#트랙백을 코멘트처럼
	use_trackback_limit int(1) not null default 1,	#트랙백길이제한
	use_trackback_cube int(1) not null default 1,	#트랙백큐브
	use_search int(1) not null default 1,			#검색
	use_unique_view int(1) not null default 1,		#조회기록
	use_filter int(1) not null default 1,			#내용필터
	use_mail_encode int(1) not null default 1,		#메일보호
	use_view_no int(1) not null default 1,			#가상번호
	use_key int(1) not null default 1,				#단축키
	use_cmt_field int(1) not null default 0,		#댓글 추가필드
	use_cmt_point_one int(1) not null default 0,	#댓글 포인트제한
	use_cmt_point int(1) not null default 0,		#댓글 점수주기
	use_cmt_award int(1) not null default 0,		#댓글 채택
	use_trash int(1) not null default 1,			#휴지통
	use_trash_edit int(1) not null default 0,		#수정기록
	use_file_admit int(1) not null default 0,		#파일승인
	use_file_unique int(1) not null default 0,		#파일중복체크
	use_count_bot int(1) not null default 0,		#봇조회수증가
	use_private_list int(1) not null default 0,		#자기글만보기
	use_add_edit int(1) not null default 0,			#수정사항추가
	use_firstview int(1) not null default 0,		#첫글보기
		#글읽기 중복방지 사용, 파일 포인트 판매 사용, 파일다운로드 전용 사용(확장자 필터는 무시되고, 이미지만 볼 수 있음), 회원정보 로드 사용, 글목록 댓글, 글목록 회원정보

	#파일
	file_value int(4) not null default 0,			#파일사용[숫자]
	file_limit varchar(255) not null default '',	#전체허용용량[숫자]
	file_limit_each varchar(255) not null default '',	#개별허용용량[숫자]
	image_width_limit smallint UNSIGNED not null default 0, #이미지너비제한[숫자]
	image_height_limit smallint UNSIGNED not null default 0, #이미지높이제한[숫자]
	image_width_auto smallint UNSIGNED not null default 0, #이미지너비자동변환[숫자]
	image_height_auto smallint UNSIGNED not null default 0, #이미지높이자동변환[숫자]
	thumb_width smallint UNSIGNED not null default 150, #썸네일너비[숫자]
	thumb_height smallint UNSIGNED not null default 150, #썸네일높이[숫자]
	thumb_type int(1) not null default 0,			#썸네일방식, 값이 있다면 이미지를 늘림
	dir varchar(255) not null default '',			#파일디렉토리명	

	#블로그
	feed_title varchar(255) not null default '',	#RSS제목
	feed_ment varchar(255) not null default '',		#RSS설명
	feed_date DATETIME not null default 0,			#RSS갱신시간

	#글관련 옵션
	point_post smallint not null default 10,			#글 쓸 때 포인트
	point_comment smallint not null default 1,			#댓글 쓸 때 포인트
	point_award smallint not null default 10,			#댓글 채택시 포인트
	point_vote smallint not null default 1,	#추천 받을 떄 포인트
	point_file smallint not null default 0,	#올린 파일 다운로드 포인트
	dobe int(5) not null default 30,					#도배방지(초)
	status_hit int(5) not null default 300,				#HOT상태-조회수 [숫자]
	status_vote int(5) not null default 50,				#유익상태-추천수 [숫자]
	status_hate int(5) not null default 10,				#유해상태-반대수 [숫자]
	status_new int(3) not null default 6,				#신규글상태-시간 [숫자]
	status_new_cmt int(3) not null default 6,			#신규댓글표시-시간 [숫자]
	limit_post int(2) not null default 5,				#연속글 제한(글) [숫자]
	limit_comment int(2) not null default 5,			#연속글 제한(댓글) [숫자]
	key_map text,										#단축키 [serialize]
	autosave int(4) not null default 60,				#자동저장 [숫자]
	reject_comment int(3) not null default 60,			#지난글 댓글제한 [숫자]
	limit_edit_post int(2) not null default 0,			#글수정시간제한 [숫자]
	limit_edit_comment int(2) not null default 0,		#댓글수정시간제한 [숫자]

	# 필터
	filter_ment text,									#내용 제한
	filter_mode enum('denied', 'block', 'opacity') not null default 'denied', #필터 처리모드
	filter_file text,									#확장자 제한
	filter_tag text,									#허용태그

	# 추가필드
	field text,											#추가필드[serialize]

	# 스킨옵션
	options text, #x

	date DATETIME not null default 0,			#날짜 - 생성

	KEY no(no),
	KEY site(site),
	KEY date(date),
	KEY id(id)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['member'] = array("회원","
CREATE TABLE {$mini['name']['member']} (
	#기본정보
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	uid varchar(255) not null default '',			#아이디
	name varchar(255) not null default '',			#닉네임
	id_mode enum('', 'mini-i') not null default '',	#회원구분
	site mediumint UNSIGNED not null default 0,		#그룹
	site_link text,									#그룹추가
	pass varchar(255) not null default '',			#비밀번호
	pass_q varchar(255) not null default '',		#비밀번호 찾기 질문
	pass_a varchar(255) not null default '',		#비밀번호 찾기 답변
	level int(2) not null default 1,				#레벨
	admin text,										#특별권한(다수지정가능)
	
	#신상정보
	real_name varchar(255) not null default '',		#실명
	jumin varchar(255) not null default '',			#주민등록번호
	confirm_jumin int(1) not null default 0,		#실명인증 여부
	mail varchar(255) not null default '',			#메일
	permit_mail int(1) not null default 1,			#동의 - 메일
	confirm_mail int(1) not null default 0,			#메일 인증
	cp varchar(255) not null default '',			#휴대전화번호
	permit_cp int(1) not null default 1,			#동의 - 휴대전화
	confirm_cp int(1) not null default 0,			#휴대전화 인증
	birth DATETIME not null default 0,				#생년월일
	tel varchar(255) not null default '',			#전화번호
	zipcode varchar(255) not null default '',		#우편번호
	address varchar(255) not null default '',		#주소
	homepage varchar(255) not null default '',		#홈페이지 - 다중입력
	sex enum('man','woman') not null default 'man',	#성별
	chat varchar(255) not null default '',			#메신져 - 다중입력....
	open text,										#공개여부
	ment text,										#자기소개
	sign text,										#서명

	co_num varchar(255) not null default '',		#사업자-사업자등록번호
	co_name varchar(255) not null default '',		#사업자-대표자명
	co_title varchar(255) not null default '',		#사업자-업체명
	co_cate1 varchar(255) not null default '',		#사업자-업종
	co_cate2 varchar(255) not null default '',		#사업자-업태
	co_address varchar(255) not null default '',	#사업자-업장주소
	co_zipcode varchar(255) not null default '',	#사업자-업장우편번호
	co_tel varchar(255) not null default '',		#사업자-전화번호
	co_fax varchar(255) not null default '',		#사업자-팩스번호

	#여부
	admit int(1) not null default 1,				#승인여부
	status varchar(255) not null default '',		#상태명

	#사업자 정보
	confirm_co enum('','request','ok','denied') not null default '', #사업자-사업자확인상태

	#관리정보
	point int not null default 0,					#포인트
	point_sum int not null default 0,				#포인트 - 누적
	money int not null default 0,					#적립금
	block text,										#차단 (쪽지, 게시글등)

	count_login mediumint UNSIGNED not null default 0, #로그인 회수
	count_vote mediumint UNSIGNED not null default 0, #추천받은 수
	count_post mediumint UNSIGNED not null default 0, #글쓴회수
	count_comment mediumint UNSIGNED not null default 0, #댓글쓴 회수
	count_recent_comment mediumint UNSIGNED not null default 0, #달린댓글 수
	count_alert mediumint UNSIGNED not null default 0, #경고수

	lock_login int(1) not null default 0,			#로그인 실패 회수
	history_admin text,								#관리용 기록
	history_login text,								#로그인 기록
	ip varchar(255) not null default '',			#로그인 아이피
	ip_join varchar(255) not null default '',		#가입 아이피
	
	key_find varchar(255) not null default '',		#비밀번호찾기/메일인증키
	key_sms varchar(255) not null default '',		#sms인증키
	key_login varchar(255) not null default '',		#자동로그인키
	
	date DATETIME not null default 0,			#날짜 - 가입
	date_login DATETIME not null default 0,		#날짜 - 마지막로그인
	date_punish DATETIME not null default 0,	#날짜 - 징계

	field text,										#추가필드
	ini text,										#쪽지설정

	etc1 varchar(255) not null default '',			#기타1
	etc2 varchar(255) not null default '',			#기타2
	etc3 varchar(255) not null default '',			#기타3
	etc4 varchar(255) not null default '',			#기타4

	KEY no(no),
	KEY site(site),
	KEY date(date),
	KEY point(point),
	KEY point_sum(point_sum),
	KEY confirm_co(confirm_co),
	KEY confirm_cp(confirm_cp),
	KEY confirm_mail(confirm_mail),
	KEY permit_cp(permit_cp),
	KEY permit_mail(permit_mail),
	KEY id_mode(id_mode),
	KEY etc1(etc1),
	KEY etc2(etc2),
	KEY etc3(etc3),
	KEY etc4(etc4),
	KEY admit(admit)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['site'] = array("그룹","
CREATE TABLE {$mini['name']['site']} (
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	name varchar(255) not null default '',			#이름
	site_link text,									#그룹연결
	mail varchar(255) not null default '',			#대표 메일
	cp varchar(255) not null default '',			#대표 휴대번호

	skin_member varchar(255) not null default '',	#스킨문자열-회원표시
	skin_login varchar(255) not null default '',	#스킨-로그인
	skin_join varchar(255) not null default '',		#스킨-가입
	skin_manage varchar(255) not null default '',	#스킨-게시물관리
	skin_mymenu varchar(255) not null default '',	#스킨-마이메뉴

	secure_pass enum('no', 'mysql', 'mysql_old', 'md5', 'sha1', 'mixed') not null default 'mixed', #비밀번호 보안

	header_url varchar(255) not null default '',	#header URL
	footer_url varchar(255) not null default '',	#footer URL
	header text,									#머리말
	footer text,									#꼬리말	
	head text,										#head스크립트

	lock_join int(1) not null default 0,			#가입제한 사용
	admit enum('', 'admin', 'mail', 'sms') not null default '',	#가입승인
	agreement text,									#가입약관
	privacy text,									#개인정보보호정책
	filter_mail text,								#가입제한 메일
	open text,										#공개여부
	use_icon int(1) not null default 1,				#아이콘 사용
	use_icon_name int(1) not null default 1,		#이미지닉네임 사용
	use_photo int(1) not null default 1,			#회원사진 사용
	use_cube int(1) not null default 1,				#큐브사용
	status text,									#회원상태 세탕
	point_login smallint not null default 0,		#로그인 포인트
	point_join smallint not null default 0,			#가입 포인트
	level_name varchar(255) not null default '',	#레벨 이름
	withdraw int(3) UNSIGNED not null default 0,	#재가입방지

	permit_icon varchar(255) not null default '',	#아이콘 권한
	permit_icon_name varchar(255) not null default '',	#아이콘 권한
	permit_photo varchar(255) not null default '',	#아이콘 권한
	permit_icon_and int(1) not null default 0,
	permit_icon_name_and int(1) not null default 0,
	permit_photo_and int(1) not null default 0,
	permit_memo varchar(255) not null default '',	#쪽지
	permit_memo_and int(1) not null default 0,
	
	date DATETIME not null default 0,				#날짜 - 생성

	field text,										#추가필드
	join_setting text,								#입력항목 설정
	template text,									#템플릿
	
	KEY no(no),
	KEY date(date)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['board'] = array("게시판","
CREATE TABLE [:table:] (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	num int UNSIGNED not null default 0,			#게시물번호
	category text,									#카테고리번호
	tag text,										#태그
	prev varchar(255) not null default '',			#이전글번호
	next varchar(255) not null default '',			#다음글번호
	
	target_member mediumint UNSIGNED not null default 0, #대상회원번호
	
	name varchar(255) not null default '',			#이름
	pass varchar(255) not null default '',			#게시물 비밀번호
	mail varchar(255) not null default '',			#메일
	title varchar(255) not null default '',			#제목
	ment text,										#내용

	secret int(1) not null default 0,				#비밀글 사용
	notice int(1) not null default 0,				#공지사항 사용
	alert int(1) not null default 0,				#경고 사용
	robot int(1) not null default 0,				#검색엔진거부
	memo int(1) not null default 1,					#댓글 쪽지알림 사용
	autobr int(1) not null default 1,				#자동BR
	popup int(1) not null default 0,				#팝업
	issue int(1) not null default 0,				#이슈글
	relate varchar(255) not null default '',		#관련글

	report int(1) not null default 0,				#신고 여부

	status varchar(255) not null default '',		#게시물 상태(19금, 논쟁글등)
	admit_file int(1) not null default 0,			#파일승인 여부 
	admit_post int(1) not null default 0,			#게시물승인 여부
	is_lock int(1) not null default 0,				#게시물잠금 사용(수정,삭제 안됨)
	trackback varchar(255) not null default '',		#트랙백받은주소
	pingback text,									#핑백보낸주소
	license varchar(255) not null default '',		#라이센스종류
	point smallint UNSIGNED not null default 0,		#점수(평균)
	point_count mediumint UNSIGNED not null default 0,	#점수(참여수)

	ip varchar(255) not null,						#글쓴이아이피

	date_comment DATETIME not null default 0,		#날짜 - 최신댓글
	name_comment varchar(255) not null default '',	#최신댓글 글쓴이
	count_comment smallint UNSIGNED not null default 0, #댓글수
	count_trackback smallint UNSIGNED not null default 0, #trackback count

	file text,
	link text,

	hit mediumint UNSIGNED not null default 0,		#읽음
	vote smallint UNSIGNED not null default 0,		#추천
	hate smallint UNSIGNED not null default 0,		#반대
	download mediumint UNSIGNED not null default 0,	#다운로드수
	scrap smallint UNSIGNED not null default 0,		#스크랩

	date DATETIME not null default 0,			#날짜 - 생성
	date_last DATETIME not null default 0,		#날짜 - 최종수정일
	date_expire DATETIME not null default 0,	#날짜 - 유효기간
	date_notice DATETIME not null default 0,	#날짜 - 공지
	date_popup DATETIME not null default 0,		#날짜 - 팝업
	date_issue DATETIME not null default 0,		#날짜 - 이슈

	history_vote text,								#추천기록
	history_hit text,								#읽음기록

	field text,										#추가필드
	etc1 varchar(255) not null default '',			#정렬 etc필드
	etc2 varchar(255) not null default '',			#정렬 etc필드
	etc3 varchar(255) not null default '',			#정렬 etc필드
	
	KEY num(num),
	KEY notice(notice),
	KEY etc1(etc1),
	KEY etc2(etc2),
	KEY etc3(etc3),
	KEY point(point),
	KEY target_member(target_member),
	KEY trackback(trackback),
	KEY status(status),
	KEY popup(popup),
	KEY issue(issue),
	KEY relate(relate),
	KEY report(report),
	KEY date(date),
	KEY date_last(date_last)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['cmt'] = array("댓글","
CREATE TABLE [:table:] (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	num int UNSIGNED not null default 0,			#정렬번호
	reply mediumint UNSIGNED not null default 0,	#답변글정렬번호
	parent text,									#부모글목록, str형식이고 제일 마지막이 제일 근접한 부모
	tag text,										#태그

	target_post int UNSIGNED not null default 0,	#대상게시물번호
	target_member mediumint UNSIGNED not null default 0, #대상회원번호
	
	name varchar(255) not null default '',			#이름
	pass varchar(255) not null default '',			#게시물 비밀번호
	mail varchar(255) not null default '',			#메일
	ment text,										#내용

	secret int(1) not null default 0,				#비밀글 사용
	notice int(1) not null default 0,				#공지사항 사용
	alert int(1) not null default 0,				#경고 사용
	trackback varchar(255) not null default '',		#트랙백받은주소
	memo int(1) not null default 0,					#댓글 쪽지알림 사용
	autobr int(1) not null default 1,				#자동BR
	admit_file int(1) not null default 0,			#파일승인 여부
	admit_post int(1) not null default 0,			#게시물승인 여부
	license varchar(255) not null default '',		#라이센스종류
	permit text,									#권한설정
	report int(1) not null default 0,				#신고 여부
	is_lock int(1) not null default 0,				#잠금 여부
	is_award int(1) not null default 0,				#채택 여부
	point smallint UNSIGNED not null default 0,		#점수

	ip varchar(255) not null,						#글쓴이아이피
	vote smallint UNSIGNED not null default 0,		#추천
	hate smallint UNSIGNED not null default 0,		#반대
	ment_advice text,								#어드바이스 기록
	file text,										#파일
	link text,										#링크

	download mediumint UNSIGNED not null default 0,	#다운로드수

	date DATETIME not null default 0,			#날짜 - 생성
	date_last DATETIME not null default 0,		#날짜 - 최종수정일

	field text,										#추가필드
	history_vote text,								#추천기록
	is_del int(1) not null default 0,				#삭제여부
	
	KEY notice(notice),
	KEY target_member(target_member),
	KEY num(num),									#타 DB에서 결합INDEX가 되는지 알아보기!
	KEY reply(reply),
	KEY target_post(target_post),
	KEY report(report),
	KEY trackback(trackback),
	KEY is_award(is_award),
	KEY point(point),
	KEY date(date)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['search'] = array("검색","
CREATE TABLE {$mini['name']['search']} (
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	mode enum('ment', 'title', 'name', 'tag', 'category') not null default 'ment', #종류
	num int UNSIGNED not null default 0,					#대상번호
	cmt_no int UNSIGNED not null default 0,					#댓글일 경우 댓글 번호
	id mediumint UNSIGNED not null default 0,						#대상게시판아이디
	ment varchar(255) not null default '',					#키워드
	target_member mediumint UNSIGNED not null default 0,	#대상회원번호
	ip varchar(255) not null default 0,						#아이피
	date DATETIME not null default 0,						#날짜 - 생성

	KEY mode(mode),
	KEY num(num),
	KEY cmt_no(cmt_no),
	KEY id(id),
	KEY target_member(target_member),
	KEY ip(ip),
	KEY date(date),
	KEY ment(ment)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['file'] = array("파일","
CREATE TABLE {$mini['name']['file']} (
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	id mediumint UNSIGNED not null default 0,				#대상게시판
	mode enum('post', 'comment', 'memo', 'box') not null default 'post', #첨부종류
	target_member mediumint UNSIGNED not null default 0,	#대상회원번호
	target_post int UNSIGNED not null default 0,			#대상게시물번호(댓글일떄만)
	target int UNSIGNED not null default 0,					#대상자료번호
	num int UNSIGNED not null default 0,					#순서
	title varchar(255) not null default '',					#간단설명
	name varchar(255) not null default '',					#원본 파일명
	url varchar(255) not null default '',					#저장된 경로 및 파일명
	size int UNSIGNED not null default 0,					#용량(byte)
	ext varchar(255) not null default '',					#확장자
	
	type enum('', 'image', 'music', 'movie', 'swf', 'flv') not null default '', #파일종류
	is_thumb int(1) not null default 0,						#썸네일
	is_admit int(1) not null default 1,						#파일승인

	width smallint UNSIGNED not null default 0,				#이미지너비
	height smallint UNSIGNED not null default 0,			#이미지높이
	hash varchar(255) not null default '',					#파일해쉬

	ip varchar(255) not null default '',					#IP
	date DATETIME not null default 0,						#날짜 - 생성
	hit mediumint UNSIGNED not null default 0,				#읽음
	history_hit text,										#읽음기록
	point int not null default 0,							#파일 포인트

	KEY no(no),
	KEY id(id),
	KEY mode(mode),
	KEY target_member(target_member),
	KEY target_post(target_post),
	KEY target(target),
	KEY type(type),
	KEY is_admit(is_admit),
	KEY name(name),
	KEY ip(ip),
	KEY date(date),
	KEY hit(hit),
	KEY size(size),
	KEY ext(ext),
	KEY hash(hash)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['session'] = array("세션","
CREATE TABLE {$mini['name']['ses']} (
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	id varchar(255) not null default '',			#세션ID
	ment text,										#내용
	ip varchar(255) not null default '',			#아이피
	server varchar(255) not null default '',		#서버 아이피
	special enum('guest', 'member', 'autologin') not null default 'guest', #특수세션
	date DATETIME not null default 0,				#날짜 - 생성

	KEY id(id),
	KEY ip(ip),
	KEY date(date),
	KEY special(special),
	KEY server(server)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['report'] = array("신고","
CREATE TABLE {$mini['name']['report']} (
	no mediumint UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	mode enum('post', 'comment', 'memo') not null default 'post', #종류
	id mediumint UNSIGNED not null default 0,		#대상게시판번호
	target int UNSIGNED not null default 0,			#대상번호

	target_member text,								#신고한 회원목록
	ment text,										#신고사유(다중)
	ment_admin text,								#처리사유
	status enum('wait', 'complete', 'delay', 'denied') not null default 'wait', #진행상황

	date DATETIME not null default 0,			#날짜 - 신고날짜
	date_result DATETIME not null default 0,	#날짜 - 처리날짜

	KEY no(no),
	KEY target(target),
	KEY id(id),
	KEY mode(mode),
	KEY status(status),
	KEY date(date)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['trash'] = array("휴지통","
CREATE TABLE {$mini['name']['trash']} (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	num int UNSIGNED not null default 0,			#게시물번호 (no)
	id  mediumint UNSIGNED not null default 0,		#대상게시판번호

	is_edit int(1) not null default 0,				#수정기록여부
	trackback int(1) not null default 0,			#트랙백 여부

	category text,									#카테고리번호 [STR]
	tag text,										#태그 [STR]

	target_post int UNSIGNED not null default 0,	#대상게시물번호
	target_member mediumint UNSIGNED not null default 0, #대상회원번호
	target_member_in mediumint UNSIGNED not null default 0, #이동회원번호
	child text,										#댓글 자식목록
	
	name varchar(255) not null default '',			#이름
	mail varchar(255) not null default '',			#메일
	title varchar(255) not null default '',			#제목
	ment text,										#내용
	
	ip varchar(255) not null,						#글쓴이아이피
	ip_in varchar(255) not null,					#이동자아이피

	date DATETIME not null default 0,				#날짜 - 생성
	date_in DATETIME not null default 0,			#날짜 - 이동일

	field mediumtext,								#그 외의 모든 데이터
	diff text,										#다른점

	KEY no(no),
	KEY num(num),
	KEY is_edit(is_edit),
	KEY trackback(trackback),
	KEY target_post(target_post),
	KEY target_member(target_member),
	KEY target_member_in(target_member_in),
	KEY date(date),
	KEY date_in(date_in),
	KEY ip(ip)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['log'] = array("기록","
CREATE TABLE {$mini['name']['log']} (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	mode varchar(255) not null default '',			#종류
	target_member int UNSIGNED not null default 0,	#회원번호
	field1 varchar(255) not null default '',		#필드1
	field2 varchar(255) not null default '',		#필드2
	field3 varchar(255) not null default '',		#필드3
	field4 varchar(255) not null default '',		#필드4
	field5 varchar(255) not null default '',		#필드5
	ment text,										#기록내용
	result int(1) not null default 1,				#결과
	ip varchar(255) not null default '',			#아이피
	date DATETIME not null default 0,				#날짜

	KEY no(no),
	KEY mode(mode),
	KEY target_member(target_member),
	KEY field1(field1),
	KEY field2(field2),
	KEY field3(field3),
	KEY field4(field4),
	KEY field5(field5),
	KEY result(result),
	KEY ip(ip),
	KEY date(date)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['memo'] = array("쪽지","
CREATE TABLE {$mini['name']['memo']} (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	target_member int UNSIGNED not null default 0,	#받는사람
	from_member int UNSIGNED not null default 0,	#보내는사람
	
	ment text,										#기록내용

	category varchar(255) not null default '',		#카테고리
	del_from int(1) not null default 0,				#삭제 - 보낸사람이
	del_target int(1) not null default 0,			#삭제 - 받는사람이
	save_from int(1) not null default 0,			#보관 - 보낸사람이
	save_target int(1) not null default 0,			#보관 - 받는사람이
	name_from varchar(255) not null default '',		#이름 - 보낸사람이
	name_target varchar(255) not null default '',	#이름 - 받는사람이
	auto varchar(255) not null default '',		#자동발송정보
	is_block int(1) not null default 0,				#차단여부
	report int(1) not null default 0,				#신고 여부
	
	ip varchar(255) not null default '',			#아이피
	
	date DATETIME not null default 0,				#날짜
	date_read DATETIME not null default 0,			#날짜 - 읽음

	KEY no(no),
	KEY target_member(target_member),
	KEY from_member(from_member),
	KEY category(category),
	KEY del_from(del_from),
	KEY del_target(del_target),
	KEY save_from(save_from),
	KEY save_target(save_target),
	KEY report(report),
	KEY ip(ip),
	KEY auto(auto),
	KEY date(date),
	KEY date_read(date_read)
) TYPE=MyISAM");


// -----------------------------------------------------------
$install_table['counter'] = array("카운터","
CREATE TABLE {$mini['name']['counter']} (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,

	id int UNSIGNED not null default 0,				#대상게시판
	agent varchar(255) not null default '',			#에이젼트
	referer varchar(255) not null default '',		#레퍼러
	url varchar(255) not null default '',			#현재url
	ip varchar(255) not null default '',			#아이피
	date DATETIME not null default 0,				#날짜

	lang char(2) not null default '',				#언어
	browser varchar(255) not null default '',		#브라우져
	
	
	KEY id(id),
	KEY agent(agent),
	KEY referer(referer),
	KEY url(url),
	KEY ip(ip),
	KEY date(date),
	KEY lang(lang),
	KEY browser(browser)
) TYPE=MyISAM");

// -----------------------------------------------------------
$install_table['counter_log'] = array("카운터기록","
CREATE TABLE {$mini['name']['counter_log']} (
	no int UNSIGNED not null PRIMARY KEY AUTO_INCREMENT,
	
	id int UNSIGNED not null default 0,				#대상게시판
	date DATETIME not null default 0,				#날짜
	pv int UNSIGNED not null default 0,				#페이지뷰
	uv int UNSIGNED not null default 0,				#방문자

	KEY id(id),
	KEY pv(pv),
	KEY uv(uv),
	KEY date(date)
) TYPE=MyISAM");


if (!empty($_REQUEST['exec'])) {
	@set_time_limit(0);
	head("style:css/style.css");
	getMember();
	checkAdmin("god");

	echo <<< END
	<div style='padding:15px; border-bottom:1px solid #ddd; margin-bottom:10px; font:12px dotum;'>
		<span class='word'><span style='font:bold 16px Georgia, tahoma'>the M</span></span>		
		<span style='font:14px Georgia;'>PatchMan</span><br />

		<div style='border:1px solid #e1e1e1; margin-top:10px; padding:5px; background-color:#f0f0f0; line-height:1.5;'>
			the M 의 새로운 DB내용과 이전 DB내용을 비교하여 변경점을 자동으로 수정해줍니다.<br />
			- <u>개인적으로 DB구조를 수정했을 경우 drop 될 수 있으니 주의해주세요.</u><br />
			- 패치에는 많은 시간이 필요할 수 있습니다.<br />
			- 최신버젼의 the M을 패치한 후에는 항상 패치맨을 실행해 주세요 :)
		</div>
	</div>
END;

	if (empty($_REQUEST['confirm'])) {
		echo <<< END
		<div style='padding:15px;'>
			<a href='_db.php?exec=1&amp;confirm=1' style='font:13px "Malgun Gothic", dotum;'>패치맨을 실행하여 미니보드를 최신버젼으로 패치합니다.</a>
		</div>
END;
		foot();
		exit;
	}

	echo "<ul>";

	//// 목록 만들기
	$arr = array();
	foreach($install_table as $key => $val):
		// 스킵
		if ($key == 'board' || $key == 'cmt') {
			$tmp = array();
			$tmp = sql("q:SHOW TABLES LIKE 'm_{$key}_%', mode:array");
			
			foreach ($tmp as $key2=>$val2):
				foreach ($val2 as $val3):
					$arr[$val3] = array($val3, str_replace("[:table:]", $val3, $val[1]));
				endforeach;
			endforeach;
		}
		else
			$arr[$key] = $val;
	endforeach;

	//// DB설치
	foreach($arr as $key => $val):
		// 변수 초기화
		unset($tablename);
		unset($data_new);
		unset($data_old);
		$query='';

		echo "<li style='margin-bottom:3px; font:14px \"Malgun Gothic\", dotum;'>{$val[0]} 테이블 수정 시작";
		flush(); 

		//// 테이블이 있을때 대조해서 고치기
		preg_match("/CREATE TABLE (.*) \(/im",$val[1],$tablename);
		if(sql("SHOW TABLES LIKE '{$tablename[1]}'")){

			// 새로운 테이블 만들기
			$temp_query = preg_replace("/CREATE TABLE (.*) \(/im","CREATE TABLE tmp_\\1 (",$val[1]);
			
			if(sql($temp_query)){
				// 테이블 정보 수집
				$q = sql("
					q: SHOW COLUMNS FROM {$tablename[1]}
					mode: nothing
				");
				$q_new = sql("
					q: SHOW COLUMNS FROM tmp_{$tablename[1]}
					mode: nothing
				");

				// 저장
				while ($data = sqlFetch($q)) $data_old[$data['Field']] = $data;
				while ($data = sqlFetch($q_new)) $data_new[$data['Field']] = $data;

				// 비교
				foreach($data_new as $key2=>$val2):
					iss($val2['Type']);
					iss($val2['Key']);
					iss($val2['Extra']);
					iss($val2['Default']);
					iss($val2['Null']);
				
					foreach($val2 as $key3=>$val3):
						iss($data_old[$key2]);
						iss($data_old[$key2][$key3]);
						iss($data_old[$key2]['Key']);

						// 새로 생긴 컬럼
						if(!$data_old[$key2] || $data_old[$key2][$key3] != $val3){
							$query_action = $query_pri = $query_def = $query_null = '';
							$query_action = ($data_old[$key2]['Field']) ? "CHANGE {$key2}" : "ADD";
							$query_null = ($val2['Null']=="NO") ? "not null" : "";
							$query_def = ($val2['Default']!=='') ? "default '".$val2['Default']."'" : "";
							$query_pri = ($val2['Key']=="PRI") ? "PRIMARY KEY" : "";
							$query .= ",{$query_action} {$key2} {$val2['Type']} {$query_null} {$query_def} {$query_pri} {$val2['Extra']}";

							if (!$query_pri && $val2['Key'] && !$data_old[$key2]['Key']) $query .= ",ADD KEY {$key2}({$key2})";
							break;
						}
					endforeach;
				endforeach;

				// 없어져야할 컬럼 확인
				foreach($data_old as $key2 => $val2):
					iss($data_new[$key2]);
					if (!$data_new[$key2]) $query .= ",DROP {$key2}";
				endforeach;
				
				if ($query){
					$query = substr($query, 1);
					$query = "ALTER TABLE {$tablename[1]} {$query}";
				}

				sql("DROP TABLE tmp_{$tablename[1]}");
			}
		}

		// 쿼리 - 기존에 있던것 수정
		if($query){
			$result2 = sql($query);
			if (!$result2) 
				echo "<br /><span style='color:red;font-weight:bold;'>alter error(!)</span> ".mysql_error()."<br />{$query}<br /><br />";
			else
				echo " ... alter complete!<br /><span style='font-size:7pt;font-family:verdana;color:gray;'>{$query}</span></li>";
			
			$query='';
		}

		// 쿼리 - 새로 추가
		elseif (!sql("SHOW TABLES LIKE '{$tablename[1]}'")) {
			$result = sql($val[1]);
			if (!$result)
				echo "<br /><span style='color:red;font-weight:bold;'>error(!)</span> ".mysql_error()."<br />{$query}<br /><br />";
			else
				echo " ... complete!</li>";
		}

		// 이미 있음
		else {
			echo " ... 변경사항 없음(skip)</li>";
		}
	endforeach;

	echo <<< END
	</ul>
	<div style='padding:15px;'>
		<span style='font:bold 14px "Malgun Gothic", dotum;'>DB 패치가 완료 되었습니다.</span>		
	</div>
END;

	echo <<< END
	<div style='padding:15px;'>
		<span style='font:bold 14px "Malgun Gothic", dotum;'>패치맨이 패치를 완료하였습니다 :) <a href='admin/'>[관리자모드로 바로가기]</a></span>
	</div>
END;

	foot();
}
?>