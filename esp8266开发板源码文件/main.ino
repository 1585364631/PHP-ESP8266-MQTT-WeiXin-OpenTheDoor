#include <ESP8266WiFi.h>                        // 本程序使用ESP8266WiFi库
#include <Servo.h>
#include <PubSubClient.h>
#include <PubSubClientTools.h>
#include <Thread.h>             // https://github.com/ivanseidel/ArduinoThread
#include <ThreadController.h>

Servo myservo;  // 定义Servo对象来控制
int pos = 0;    // 角度存储变量

                       
void setup() {
  Serial.begin(115200);                         // 初始化串口通讯波特率为115200
  start_wifi("GDPT-Student","");
  start_mqtt();
}

void light(int status){
  pinMode(LED_BUILTIN, OUTPUT);
  if(status==0){
    digitalWrite(LED_BUILTIN, HIGH);
  }else{
    digitalWrite(LED_BUILTIN, LOW);
  }
}

void start_wifi(char* ssid,char* password){
  WiFi.mode(WIFI_STA);                          // 设置Wifi工作模式为STA,默认为AP+STA模式
  WiFi.begin(ssid, password);                   // 通过wifi名和密码连接到Wifi
  Serial.print("\r\n开始连接到无线网络 ");          // 串口监视器输出网络连接信息
  Serial.println(ssid);                         // 显示NodeMCU正在尝试WiFi连接
  int i = 0;                                    // 检查WiFi是否连接成功
  while (WiFi.status() != WL_CONNECTED)         // WiFi.status()函数的返回值是由NodeMCU的WiFi连接状态所决定的。 
  {                                             // 如果WiFi连接成功则返回值为WL_CONNECTED
    delay(1000);                                // 此处通过While循环让NodeMCU每隔一秒钟检查一次WiFi.status()函数返回值
    Serial.print("无线网络连接中 ");                          
    Serial.print(++i); Serial.println("秒");       
  }                                             
  Serial.println("");                           // WiFi连接成功后
  Serial.println(ssid);
  Serial.println("连接成功！");                    // NodeMCU将通过串口监视器输出"连接成功"信息。
  Serial.print("IP地址: ");                       // 同时还将输出NodeMCU的IP地址。这一功能是通过调用
  Serial.println(WiFi.localIP());                // WiFi.localIP()函数来实现的。该函数的返回值即NodeMCU的IP地址。
}

#define MQTT_SERVER "10.107.116.10"
WiFiClient espClient;
PubSubClient client(MQTT_SERVER, 1883, espClient);
PubSubClientTools mqtt(client);
ThreadController threadControl = ThreadController();
Thread thread = Thread();
const String s = "";
void start_mqtt(){
  Serial.println(s + "正在连接 MQTT: "+MQTT_SERVER+" ... ");
  if (client.connect("ESP8266Client1-229door")) {
    Serial.println("连接成功");
    mqtt.subscribe("door",  open_door);
  } else {
    Serial.println("连接失败，状态码："+client.state());
  }
}

void open_door(String topic, String message) {
  Serial.println(s+ "主题：" + topic + "\n内容：" +message);
  if(message.compareTo("开门") == 0){
    Serial.println("自动开关门中");
    door();
  }
}

void send_mqtt(String topic, String message){
  mqtt.publish(topic, message);
}


void start_zhuan(int start_po,int end_po,int timer){
  myservo.attach(2,500,2500);
  if(start_po<end_po){
    for (pos = start_po; pos <= end_po; pos ++) {
      myservo.write(pos);
      delay(timer);
    }
  }else{
    for (pos = start_po; pos >= end_po; pos --) {
      myservo.write(pos);
      delay(timer);
    }
  }
}

void door(){
  myservo.attach(2,500,2500);
  light(1);
//  start_zhuan(0,180,0);
  myservo.write(180);
  delay(2000);
  // myservo.write(0);
  start_zhuan(180,0,0);
  light(0);
}
 
void loop() {
  client.loop();
}
