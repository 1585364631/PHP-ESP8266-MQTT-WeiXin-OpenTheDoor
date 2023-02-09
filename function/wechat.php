<?php
require "opendoor.php";
class Wechat{

    // 微信公众平台基本信息
    private $token = 'wkyd';

    // 用户信息
    public $id = '';
    public $username = '';
    public $number = '';
    public $reg_time = '';
    public $domain = "";

    // 数据库信息
    public $host = 'localhost';
    public $database = 'wechat';
    public $user = 'root';
    public $password = 'root';
    public $mysqli = '';


    public function __construct(){
        $this->get_chat();
    }

    // 接受用户消息
    // 接收用户对公众号发送的信息
    function get_chat(){
        // 接收xml数据 $GLOBALS['HTTP_RAW_POST_DATA']，如果用不了就使用file_get_contents('php://input')
        // 或者找到php.ini配置文件，修改配置为always_populate_raw_post_data = On
        // $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xml = file_get_contents('php://input');
        // 如果$xml有数据才执行
        if(!empty($xml)){
            // 防止xml注入，禁止xml实体解析
            libxml_disable_entity_loader(true);
            // 使用simpleXML解析该xml字符串
            $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $cache_fp = fopen("error.txt","w+");
            fwrite($cache_fp,json_encode($xml));
            fclose($cache_fp);
            // 通过元素MsgType来判断该微信服务器转发的消息类型
            switch ($xml->MsgType){
                // 接收到事件消息
                case 'event':
                    // 公众号订阅事件
                    if ($xml->Event=='subscribe'){
                        $this->user_subscribe($xml);
                    }
                    // 公众号取消订阅事件
                    if ($xml->Event=='unsubscribe'){
                        $this->user_unsubscribe($xml);
                    }
                    break;
                // 接收到文本消息
                case 'text':
                    // 判断为文本消息后，跳转函数
                    $this->get_text_message($xml);
                    break;
                // 接收到图片消息
                case 'image':
                    break;
                //接收到语音消息
                case 'voice':
                //接收到视频消息
                case 'video':
                //接收到短视频消息
                case 'shortvideo':
                //接收到位置消息
                case 'location':
                //接收到链接消息
                case 'link':
            }
        }
    }

    // 得到文本消息
    public function get_text_message($xml){
        opendoor::getInstance()->gettext($xml);
    }

    // 发送文本消息
    public function send_text_message($xml,$text){
        $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$text);
        die($res);
    }

    // 验证签名
    public function check(){
        if(!empty($_GET['echostr'])){
            // 微信通信请求字段
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];
            $echostr = $_GET['echostr'];
            // 将token，timestamp，nonce三个字段合为一个数组，并且按照字典排序
            $arr = array($this->token, $timestamp, $nonce);
            sort($arr,SORT_STRING);
            // 将数组内容拼接为一个字符串
            $str = implode($arr);
            // 将拼接后的字符串内容进行sha1加密
            $sign = sha1($str);
            // 与微信服务器请求的signature字段内容进行对比，则证明是来自微信服务器的消息
            if($sign == $signature){
                //将echoStr字段的值返回给微信
                echo $echostr;
            }else{
                // 内容不一样则退出
                $cache_fp = fopen("error.txt","w+");
                fwrite($cache_fp,"验证签名失败");
                fclose($cache_fp);
                return false;
            }
        }
    }

    // 定义用户关注公众号函数
    function user_subscribe($xml){
        // 引用全局变量模板
        $username = $xml->FromUserName;
        $res_time = $xml->CreateTime;
        $cont = "此乃缘，妙，不可言";
        $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$cont);
        die($res);
    }

    // 定义用户取消关注公众号函数
    function user_unsubscribe($xml){
        // 引用全局变量模板
        $username = $xml->FromUserName;
        $cont = "天下分久必合，合久必分，无不散之筵席，有缘再相见";
        $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$cont);
        die($res);
    }

}