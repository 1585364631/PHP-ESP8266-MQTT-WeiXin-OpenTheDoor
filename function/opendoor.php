<?php
require "phpMQTT.php";
require "mysql.php";
require "mytool.php";

class opendoor{
    public $server = '127.0.0.1';     // 服务器IP
    public $port = 1883;            // 服务器端口
    public $username = 'admin';              // 用户名
    public $password = 'public';              // 密码
    public $client_id = 'php_opendoor';
    public $master = 'oKMn3wXdaRXlE0rIUaybrdx2-tqk';
    private static $instance = null;
    public $filepath = "time.txt";

    public function open_door($topic,$content,$qos): string
    {
        $mqtt = new Bluerhinos\phpMQTT($this->server, $this->port, $this->client_id);
        if ($mqtt->connect(true, NULL, $this->username, $this->password))
        {
            $cachetime = mytool::getMillisecond();
            $time = mytool::get_file($this->filepath);
            if (!$time){
                mytool::write_file($this->filepath,$cachetime);
                $time = 0;
            }
            if ($cachetime - $time <= 15000){
                return "两次开门间隔小于15秒";
            }
            $mqtt->publish($topic, $content, $qos);
            $mqtt->close();
            mytool::write_file($this->filepath,$cachetime);
            return '开门成功';
        }
        return '开门失败';
    }

    public function gettext($xml){
        if($xml->Content == "查看令牌"){
            $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$xml->FromUserName);
            die($res);
        }

        if($xml->Content == "开门"){
            $db = mysql::getInstance();
            $data = $db->query("select * from opendoor where token = '{$xml->FromUserName}'");
            if ($data == []){
                $newdata = "没有权限";
            }else{
                $newdata = $this->open_door("door","开门",0);
            }
            $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$newdata);
            die($res);
        }

        if($xml->Content == "令牌列表"){
            $db = mysql::getInstance();
            $data = $db->query("select * from opendoor");
            if ($data == []){
                $newdata = "没有内容";
            }else{
                $newdata = json_encode($data);
            }
            $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$newdata);
            die($res);
        }

        if (preg_match("/^添加令牌*/",$xml->Content)){
            if(trim($xml->FromUserName) == trim($this->master)){
                $token = explode("添加令牌 ",$xml->Content)[1];
                $db = mysql::getInstance();
                $b = '添加失败';
                if ($db->insert("opendoor",["token"=>$token])==1){
                    $b = '添加成功';
                }
                $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$b);
            }else{
                $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),"无权限");
            }
            die($res);
        }

        if (preg_match("/^删除令牌*/",$xml->Content)){
            if(trim($xml->FromUserName) == trim($this->master)){
                $token = explode("删除令牌 ",$xml->Content)[1];
                $db = mysql::getInstance();
                $b = '删除失败';
                if ($db->delete("opendoor","token = '$token' or id = '$token'")==1){
                    $b = '删除成功';
                }
                $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),$b);
            }else{
                $res = sprintf(mytool::$send_info_array['text'],$xml->FromUserName,$xml->ToUserName,time(),"无权限");
            }
            die($res);
        }

    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 禁止被实例化
    private function __construct()
    {

    }

    // 禁止clone
    private function __clone()
    {

    }
}
//opendoor::getInstance()->open_door('door','开门',0)