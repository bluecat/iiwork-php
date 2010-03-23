<?php
/** 메일 보내기
 * @class notification
 * @param
		-mode: 메일 보내는 방법 (auto)
			#auto 자동인식
			#text
			#html
			#both
		-file: 첨부파일, array 가능, _FILES array 를 넘겨야 함
		-ment: 내용
		-title: 제목
		-from_name: 보내는사람 이름
		-from_mail: 보내는사람 메일
		-to_name: 받는사람 이름
		-to_mail: 받는사람 메일
		-error_mail: 에러났을때 받을메일
		-is_base: base64사용
		-is_socket: socket 전송 사용
		-socket_host:
		-socket_port:
 * @return 
 */
function send_mail($param) {
	global $mini;
	$param = param($param);

	def($param['mode'], 'auto');
	def($param['is_base'], '1');
	def($param['is_socket'], '1');

	$header = $type = $ment_file = '';
	$eof = "\r\n";
	$param['ment'] = trim($param['ment']);
	$param['title'] = trim($param['title']);
	$title = $param['title'];

	// 인코딩
	if ($param['is_base']) {
		if ($param['to_name']) $param['to_name'] = "=?{$mini['set']['lang']}?B?".base64_encode($param['to_name'])."?= ";
		if ($param['from_name']) $param['from_name'] = "=?{$mini['set']['lang']}?B?".base64_encode($param['from_name'])."?= ";
		if ($param['title']) $param['title'] = "=?{$mini['set']['lang']}?B?".base64_encode($param['title'])."?="; else $param['title'] = 'No.title';
	}

	if (!empty($param['file'])) {
		foreach ($param['file'] as $key=>$val):
			if ($val['size']) {
				$fp = fopen($val['tmp_name'], "rb");
				if ($fp) {
					$ment_file .= "{$eof}--------------010504050703010207050203{$eof}";
					$ment_file .= "Content-Type: application/octet-stream; charset={$mini['set']['lang']}{$eof}";
					$ment_file .= "Content-Transfer-Encoding: base64{$eof}";
					
					if ($param['is_base'])
						$ment_file .= "Content-Disposition: attachment; filename=\"=?{$mini['set']['lang']}?B?".base64_encode($val['name'])."?=\"{$eof}{$eof}";
					else
						$ment_file .= "Content-Disposition: attachment; filename=\"{$val['name']}\"{$eof}{$eof}";

					$ment_file .= trim(chunk_split(base64_encode(fread($fp, $val['size']))));
					fclose($fp);
					$param['mode'] = 'both';
				}
			}		
		endforeach;
	}

	// 자동 선택
	if ($param['mode'] == 'auto') {
		if (eregi("^\<", $param['ment']))
			$param['mode'] = 'html';
		elseif (strpos($param['ment'], '<') !== false) 
			$param['mode'] = 'both';
		else 
			$param['mode'] = 'text';
	}

	switch ($param['mode']):
		case 'text':
			$type = "text/plain";
			break;

		case 'html':
			$type = "text/html";
			break;

		case 'both':
			$type = "multipart/mixed";
			//$param['ment'] = nl2br($param['ment']);
			//$param['ment'] = chunk_split(trim($param['ment']));
			break;

		default:
			__error('send_mail 함수 mode 값 에러');
	endswitch;

	//// 헤더 정보 생성
	$header .= "Return-Path: {$param['from_mail']}{$eof}"; // 리턴
	if (!empty($param['is_notice'])) $header .= "Disposition-Notification-To: <{$param['from_mail']}>{$eof}"; // 수신확인
	$header .= "Date: ".date("D, j M Y H:i:s O")."{$eof}"; // 시간
	$header .= "From: {$param['from_name']}<{$param['from_mail']}>{$eof}"; // 보내는사람
	$header .= "MIME-Version: 1.0{$eof}";
	$header .= "X-Mailer: the M Mailer beta{$eof}";
	if (!empty($param['error_mail'])) $header .= "Errors-To: <{$param['error_mail']}>{$eof}";
	$header .= "Content-Type: {$type}";

	//$header .= "To: {$param['to_name']}<{$param['to_mail']}>{$eof}"; // 보내는사람
	//$header .= "Subject: {$param['title']}{$eof}";
	
	if ($param['mode'] == 'both') 
		$header .= ";{$eof} boundary=\"------------010504050703010207050203\"{$eof}";
	else {
		$header .= "; charset={$mini['set']['lang']}{$eof}";
		$header .= "Content-Transfer-Encoding: 8bit{$eof}";
	}

	$header .= "Status:";
	$ment_tmp = '';

	if ($param['mode'] == 'both')
		$ment_tmp .= "This is a multi-part message in MIME format.{$eof}";
	else
		$ment_tmp .= $param['ment'].$eof;
	
	// 복합 출력
	if ($param['mode'] == 'both') {
		$ment_tmp .= "--------------010504050703010207050203{$eof}";
		$ment_tmp .= "Content-Type: text/html; charset={$mini['set']['lang']}{$eof}";
		$ment_tmp .= "Content-Transfer-Encoding: 8bit{$eof}{$eof}";
		if ($param['ment']) $ment_tmp .= $param['ment'].$eof.$eof;
		if ($ment_file) $ment_tmp .= $ment_file;

		//$ment_tmp .= "--------------20070101--{$eof}";
	}

	if (!$param['is_socket']) {
		return mail($param['to_mail'], $param['title'], $ment_tmp, $header);
	}
	else {
		if (!empty($mini['set']['socket_host'])) def($param['socket_host'], $mini['set']['socket_host']);
		if (!empty($mini['set']['socket_port'])) def($param['socket_port'], $mini['set']['socket_port']);
		def($param['socket_host'], ini_get("SMTP"));
		def($param['socket_port'], ini_get("smtp_port"));

		$fp = @fsockopen($param['socket_host'], $param['socket_port'], $errno, $errstr, 5);
		if ($fp) {
			$rcv = fgets($fp, 1024);
			fputs($fp, "HELO {$_SERVER['SERVER_NAME']}{$eof}"); 
			$rcv = fgets($fp, 1024);
			fputs($fp, "MAIL FROM:{$param['from_mail']}{$eof}");
			$rcv = fgets($fp, 1024);
			fputs($fp, "RCPT TO:{$param['to_mail']}{$eof}");
			$rcv = fgets($fp, 1024);
			fputs($fp, "DATA{$eof}");
			fputs($fp, "Subject: {$param['title']}{$eof}");
			fputs($fp, "{$header}{$eof}{$eof}");
			fputs($fp, "{$ment_tmp}{$eof}");
			fputs($fp, ".{$eof}");
			$rcv = fgets($fp, 1024);

			fputs($fp, "QUIT{$eof}");
			fclose($fp);
			
			return 1;
		}
		else
			return 0;
	}
} // END function
?>