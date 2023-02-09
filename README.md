# PHP-ESP8266-MQTT-WeiXin-OpenTheDoor
后端使用PHP对接微信公众号，并且使用内网穿透将内网中的微信公众号php文件穿透出来，使微信公众号能正常对接，开发板使用esp8266和90度舵机，使用mqtt通信，实现自动开门

## 部署步骤
### 1.内网服务器docker运行mqtt容器、npc容器，外网服务器运行nps容器
### 2.内网部署php环境，修改相关参数并且部署项目网站
### 3.外网服务器内网穿透映射80端口到内网php项目端口
### 4.esp8266开发板连接舵机，修改main.ino中配置信息，并且使用arduino ide编译文件

## 实战视频演示
http://static.000081.xyz/video/wx.mp4
