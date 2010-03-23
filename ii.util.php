<?php

/** 자료 비교(diff), 줄바꿈 기준
 * @class Util
 * @param
		String $data_ex: 원본
		String $data: 비교대상
 * @return Array
 */
function str_diff($data_ex, $data) {
		$len_ex = strlen($data_ex);
		$len = strlen($data);
		$output = array();

		$count = $count_ex = 0;

		if ($data_ex != $data) {
			if ($len_ex) {
				$data_ex = str_replace("\r\n", "\n", $data_ex);
				$arr_ex = explode("\n", $data_ex);
				$count_ex = count($arr_ex);
			}

			if ($len) {
				$data = str_replace("\r\n", "\n", $data);
				$arr = explode("\n", $data);
				$count = count($arr);
			}

			$output['length_ex'] = $len_ex;
			$output['length'] = $len;
			$output['line_ex'] = $count_ex;
			$output['line'] = $count;
			$output['text_ex'] = $data_ex;
			$output['text'] = $data;
			$output['data'] = array();
			$output['data_ex'] = array();

			for ($i = 0; $i <= $count_ex; $i++):				
				$key = $i;

				if (isset($arr_ex[$i])) {
					$val = $arr_ex[$key];
					reset($arr);

					// 같다면 비교할 필요 없음
					if ($val == current($arr)) {
						foreach ($arr as $key2=>$val2):
							unset($arr[$key2]);
							break;
						endforeach;
						continue;
					}

					else {
						$tmp_key = false;
						if (is_array($arr)) $tmp_key = array_search($val, $arr);

						// 같은 줄이 있을 경우 tmp_key 전에 것 까지의 arr은 모두 추가된 것임
						if ($tmp_key !== false) {
							// arr 의 가장 작은 key 부터 시작
							reset($arr);
							foreach ($arr as $key2=>$val2):
								if ($key2 >= $tmp_key) {
									break;
								}
								else if (isset($arr[$key2])) {
									$output['data'][$key2] = $val2;
									unset($arr[$key2]);
								}
							endforeach;
							unset($arr[$tmp_key]);
							continue;
						}

						// 같은 줄이 없을 경우
						else {
							$output['data_ex'][$key] = $val;
							continue;
						}
					}
				}
			endfor;

			if (!empty($arr)) {
				reset($arr);
				foreach ($arr as $key=>$val):
					$output['data'][$key] = $val;
				endforeach;
			}

			if (empty($output['data'])) unset($output['data']);
			if (empty($output['data_ex'])) unset($output['data_ex']);

			return $output;
		}
		else
			return 0;
} // END function


?>