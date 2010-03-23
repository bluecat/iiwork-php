; <?php /*이 줄을 지우지 마세요

; 테스트 수정
; DB 계정정보

hostname = "localhost" ; DB Hostname
userid = "dev" ; DB id
userpass = "park" ; DB password
dbname = "dev" ; DB name

name = "m" ; 기본 테이블명


; 기본 설정

lang = "UTF-8" ; 언어설정
lang_from = "EUC-KR" ; 기본설정된 언어
debug = "0" ; 디버그모드
sql = "mysql" ; DB설정
session = "db" ; DB세션
session_interval = "2" ; 세션 허용일
key = "1234" ; 암호키 ; 암호 조합에 사용될 4자리 이상의 키값
mail = "i@iiwork.com" ; 관리자메일 ; 여러 상황에서 쓰이는 관리자 메일입니다
use_ob_start = "1" ; 페이지 로드 사용
skinmake = "0" ; 스킨제작모드 ; 모든 페이지를 볼때마다 자동으로 .mini를 변환합니다. 속도가 느려질 수 있습니다.
date_str = "4" ; 날짜표기시간 ; X일전, X시간전 등의 날짜 표기를 해주는 최대 일 수를 지정한다
ajax_max = "30" ; AJAX출력개수 ; AJAX의 출력 개수를 설정 합니다. 많을 수록 부하가 큽니다.
mysql_init = "" ; DB초기쿼리 ; set names utf8 같은 항상 날릴 쿼리를 설정 합니다.

; 파일 설정

file_expire = "24" ; 임시파일기간 ; 임시파일을 저장하는 시간 입니다. 단위는 시간 입니다.
use_file_referer = "1" ; 이전경로체크 ; 이전경로를 체크해서 무단링크를 방지합니다.
use_swfupload = "1" ; SWFUpload 사용 ; 플래쉬 멀티 업로더를 사용 합니다.
use_phpthumb_hard = "0" ; 이미지편집고급 ; 고급 이미지편집 기능을 사용합니다. 시스템에 큰 부하를 줍니다.

; 검색 설정

search_limit = "1000" ; 최대검색결과수 ; 0일 때 제한하지 않는다
use_sort = "1" ; 정렬 사용
sort_limit = "10000" ; 정렬 제한 ; 설정된 숫자보다 게시물이 많으면 설정된 숫자만큼만 보여집니다.
use_sort_only_key = "0" ; 정렬 키검색 ; 키값이 있는 정렬만 가능하게 합니다.
tag = "30" ; 태그 랜덤뽑기 ; 태그 랜덤뽑기에 출력되는 결과수를 지정합니다.

; 쪽지 설정

use_memo = "1" ; 쪽지 사용 ; 쪽지의 사용 여부를 표시합니다.
use_memo_realtime = "1" ; 실시간쪽지 사용 ; 쪽지 확인을 실시간으로 합니다.
use_friend_limit = "10" ; 친구수제한 ; 친구 수를 제한합니다.
memo_interval = "7" ; 쪽지보관일수
memo_realtime = "5" ; 실시간쪽지간격 ; 실시간으로 갱신되는 간격을 입력합니다. 짧아질 수록 부하가 걸릴 수 있습니다. (단위는 초)

; 카운터 설정

use_counter = "1" ; 카운터 사용
use_count_bot = "0" ; 카운터 봇허용 ; 봇의 접속도 카운터에 기록됩니다.

; 필터 설정

filter_agent = "" ; AGENT필터 ; USER-AGENT 에 해당 문자가 포함되어 있으면 접근을 거부합니다. (구분은 ,)
filter_ip = "" ; IP필터 ; IP에 해당 문자,숫자가 포함되어 있으면 접근을 거부합니다. (구분은 ,)


; 로그인 설정

login_time = "1800" ; 로그인적용시간 ; 해당시간(초)가 지나면 로그인 상태로 보지 않습니다. 로그인 유지는 됩니다
use_login_session = "1" ; 로그인세션사용 ; 로그인을 쿠키가 아닌 세션으로 합니다. 안정성이 향상되나 부하도가 늘어납니다
use_login_multi = "1" ; 다중로그인사용 ; 동시 로그인을 최대 3명까지 허용합니다. (자동로그인도 마찬가지)
use_guest_session = "1" ; 비회원집계사용 ; 로그인한 비회원의 수를 집계합니다
lock_login = "5" ; 로그인 실패허용 ; 지정한 횟수만큼 로그인을 실패 했을 때 해당 아이디를 잠급니다.
login_history_count = "10" ; 로그인기록개수 ; 로그인 기록의 개수를 지정합니다
login_interval = "300" ; 로그인갱신간격 ; 로그인 암호키를 해당시간(초)마다 갱신합니다


; 메일 설정

use_smtp = "1" ; SMTP 사용 ; SMTP(메일) 사용 여부를 표시합니다. 테스트를 통해 알 수 있습니다.
socket_host = "" ; SMTP 주소 ; SMTP 주소를 입력합니다. 값이 없으면 서버 설정대로 사용합니다.
socket_port = "" ; SMTP 포트 ; SMTP 포트를 입력합니다. 값이 없으면 서버 설정대로 사용합니다.


; SMS 설정

use_sms = "0" ; SMS 사용 ; SMS(문자메세지) 사용 여부를 표시합니다. 테스트를 통해 알 수 있습니다.
sms_id = "" ; SMS 아이디 ; mini-i.com 의 회원 아이디를 입력합니다.
sms_pass = "" ; SMS 암호 ; mini-i.com 에서 발급받은 SMS 연결 암호를 입력합니다.


; 회원 설정

punish_count = "3" ; 경고수징계 ; 경고수가 해당 숫자 이상일 경우 징계상태가 됩니다
use_address = "0" ; 주소검색 ; 주소 검색 사용여부를 선택합니다. zipcode DB 설치시 이용할 수 있습니다.


; RSS 설정

feed_count = "60" ; Feed개수제한 ; RSS, ATOM등의 출력 개수 제한
rss_version = "2.0" ; RSS버젼 ; 기본 RSS 버젼
feed_skip = "60" ; Feed스킵 ; 해당시간동안은 feed를 갱신하지 않습니다. 단위는 초
trackback_pass = "1234" ; 트랙백암호 ; 미니보드 끼리는 보낸 트랙백을 다시보내 수정할 수 있습니다. 그때 필요한 암호 입니다.
trackback_cube_limit = "5" ; 트랙백인증시간 ; 트랙백큐브 기능 사용시 발급받은 키에 대한 유효 시간 입니다. 단위는 분 입니다.


; 기록 설정

use_log_point = "1" ; 포인트기록 ; 포인트 기록을 남깁니다

; 기타 설정

syntax_rule = "php,ruby,java,cpp,csharp,css,delphi,jscript,python,sql,vb" ; 코드강조종류 ; 사용할 코드강조 종류를 기입합니다. (구분은 ,)<br />종류는 php,ruby,java,cpp,csharp,css,delphi,jscript,python,sql,vb 가 있습니다.


; CUBE 설정

use_cube = "1" ; CUBE 사용 ; 미니보드 스팸방지 시스템인 CUBE를 사용합니다. 비회원에게만 적용 됩니다.
cube_max = "4" ; CUBE 개수 ; sfile/cube에 있는 종류의 개수를 설정합니다.
cube_name = "[별,스타,동그랗지 않은 별,네모랑은 상관없는 별,별임. 하트말고][하트,사랑,원 아닌 하트,하트이고 원 아님][원,동글뱅이,동그라미,네모가 아닌 원][네모,사각형,사각형이고 동그라미 아님]" ; CUBE 이름 ; CUBE 에 들어가는 이름을 지정합니다. 순서대로 1번부터 입니다.<br />예) [이름1,이름1][이름2]
cube_time = "30" ; CUBE 제한시간 ; 이미지 선택에 실패하면 해당 시간동안 글을 쓸 수 없습니다. 단위는 초 입니다.


; 이 줄을 지우지 마세요 */ ?>