<?php
/*
암튼 적당히 테스트 할 페이지 위쪽에 인클루드 시키고 적당히 위쪽에 
PerformanceChecker::Run(false); 
declare (ticks=1); 
써주시면 됩니다. 
첫번째 인자에 true 하시면 오비가 페이지의 다른 출력을 먹어버립니다. 

제작자: 무화, 출처: phpschool, 라이센스: GPL
*/

    if($_SERVER['QUERY_STRING'] === 'source'){
        highlight_file(__FILE__);die;
    }
?><?php

class PerformanceChecker
{

    var $store = array();
    var $last_function = null;
    var $now = 0;

    function run($use_ob = false){
        new PerformanceChecker($use_ob);
    }

    function PerformanceChecker($use_ob){
        $this->now = time()+microtime();

        register_tick_function(array(&$this, 'check'), true);
        register_shutdown_function(array(&$this, "dump"));

        if($use_ob)
            ob_start(array(&$this, 'ob'));

    }

    function check(){

        $trace = debug_backtrace();

        if($trace[1]['function'] == 'unknown'){
            $func = basename($trace[1]['file']).':<b>__GlobalScope</b>';
        }else if($trace[2]['class'] == 'performancechecker'){
            $func = '';
        }else{
            $func = basename($trace[1]['file']).':<b>'.(($trace[2]['class']) ? $trace[2]['class'].$trace[2]['type'].$trace[1]['function'] : $trace[1]['function']).'</b>';
        }

        if ($func){
            if($func !=  $this->last_function){
                if($this->last_function){
                    $this->store[$this->last_function] += (time()+microtime()) - $this->now;
                }
                $this->now = time()+microtime();
            }

            $this->last_function = $func;

        }else{
            $this->last_function = null;
        }

    }

    function ob(){
        return '';
    }

    function dump(){
        echo '<pre>';

        arsort($this->store);
        print_r(array_map(array(& $this, 'friendly_store'), $this->store));

        echo "\n".'Total : '.array_sum($this->store);

        echo '</pre>';
    }

    function friendly_store($f){
        static $max = 0;
        if(!$max)
            $max = $f;

        $c = $f / $max;

        return sprintf('%f ... <span style="color:#%00x0000"> %01.2f </span>', $f, $c * 155 + 100, $c);
    }

}

?> 