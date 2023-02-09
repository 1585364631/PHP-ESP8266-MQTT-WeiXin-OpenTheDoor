<?php

class mytool{
    // 全局变量定义消息发送模板
    static public $send_info_array = array(
        // %s 代表变量，之后可以使用spritf传值
        // 文本消息的发送模板
        "text" => "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>"
    );

    // 时间戳转时间
    function strintime($time){
        return date("Y-m-d H:i:s",strval($time));
    }

    // 取当前毫秒时间戳
    static function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    static function write_file($path,$text){
        $cache_fp = fopen($path,"w+");
        fwrite($cache_fp,$text);
        fclose($cache_fp);
    }

    static function get_file($path){
        if(file_exists($path)){
            return file_get_contents($path);
        }else{
            return false;
        }
    }
}

