<div align="center">
  <img src="https://raw.githubusercontent.com/emilia-t/online-map-editor/main/other/img/map-log.png" alt="log" width="64"/>
</div>

<div align="center">
<h1>在线地图服务 onlineMapServer</h1>
<p><em>实时在线与您的同伴编辑地图Real time online editing of maps with your companions</em></p>
</div>

## 免责声明(Disclaimers)

本项目开源仅供学习使用，不得用于任何违法用途，否则后果自负，与本人无关。使用请保留项目地址，谢谢。

This project is open source for learning purposes only and cannot be used for any illegal purposes. Otherwise, the consequences will be borne by oneself and have nothing to do with myself. Please keep the project address for use, thank you.

<div align="center">

[![GitHub forks](https://img.shields.io/github/forks/emilia-t/online-map-editor.svg?abel=Fork&style=for-the-bright)](https://github.com/emilia-t/online-map-editor)
[![Workerman](https://img.shields.io/badge/Workerman-4.0.19-brightgreen)]()
[![PHP](https://img.shields.io/badge/PHP-7.3.31-brightgreen)]()
[![LICENSE](https://img.shields.io/badge/LICENSE-MIT-brightgreen)](https://github.com/emilia-t/online-map-editor/blob/main/LICENSE)


</div>

## 📝 项目简介(Project Introduction)

用于创建在线协作编辑地图svg图像的后端php程序

A backend PHP program for creating online collaborative editing map SVG images

若要您已有创建好的在线地图服务器，请前往此页并点击右上角 "+" 连接您的服务器 https://map.atsw.top

If you already have a created online map server, please go to this page https://map.atsw.top, then click the "+" in the upper right corner to connect.

## 🌎️ 一些截图(Some screenshots)

首页、服务器选择页面(Home page, server selection page)：

<img src="https://raw.githubusercontent.com/emilia-t/online-map-editor/main/other/img/START.png" alt="Screenshot 1" width="800"/>

编辑要素(Edit elements)：

<img src="https://raw.githubusercontent.com/emilia-t/online-map-editor/main/other/img/screenshot2.jpg" alt="Screenshot 2" width="800"/>

查看要素(View elements)：

<img src="https://raw.githubusercontent.com/emilia-t/online-map-editor/main/other/img/screenshot3.jpg" alt="Screenshot 3" width="800"/>

## 🆕 版本更新内容(Version update content)

+ 增加了部分指令：
+ Added some instruct：
+ 1.ping, pong 网络延迟测试指令
+ 1.ping, pong Network latency test instruct
+ 2.updateTemplateData 模板更新指令
+ 2.updateTemplateData Template update instruct
+ 3.拆解了$broadcast#updateLayerData指令
+ 3.Decomposed the $broadcast#updateLayerData instruct
+ 4.支持了图层数据本地缓存与编辑
+ 4.Supports local caching and editing of layer data
+ 修复了部分bug
+ Fixed some bugs

## 📥 下载项目(Download project)

项目结构(Project Structure)
```
root
└───
  ├───Workerman[Workerman程序(Dependent program)]
  ├───api[一些api，大部分都弃用了(Some APIs, most of which have been abandoned)]
  └───class[PHP类文件(PHP Class file)]
  └───tools[一些工具(some tool)]
  └───.gitignore[忽略文件列表(Ignore file list)]
  └───Aconst.php[一些常量(some constant)]
  └───AmapService.php[主文件，入口文件(Main file)]
  └───LICENSE[许可声明]
  └───README.md
  └───Server_config.php[服务器配置文件示例(Example of Server Configuration File)]
  └───docker-compose.yml[用于在doker中启动OMS服务(Used to start OMS service in Doker)]
  └───dockerfile[用于创建特殊的PHP镜像(Used to create special PHP images)]
  └───init.sql[用于初始数据库的sql(SQL during initialization)]
  └───start-docker.txt[以docker的方式启动服务的说明文件(Instructions for Starting Services with Docker)]
  └───start.bat[启动服务的脚本(Script to start the service)]
  └───start.sh[启动服务的脚本(Script to start the service)]
```



## 🔧 创建您的OMS服务(Create your OMS service)

<p style="color:red">请在启动服务之前确保给予了所有文件的读取、写入、执行的权限</p>

<p style="color:red">Please ensure that all files are granted read, write, and execute permissions before starting the service</p>

>1.复制空白配置文件
>
>1.Copy a blank configuration file
```
sudo mkdir config

sudo cp Server_config.php ./config/Server_config.php
```
>2.编写刚刚复制的配置文件
>
>2.Write a configuration file you just copied
```

sudo vim ./config/Server_config.php<br />

```
>3.Windows请运行start.bat，Linux请运行start.sh 
>
>3.Windows please Run start.bat, Linux please Run start.sh
```
PowerShell

start.bat

```
```
bash

sh start.sh

```
> 4.如果终端上显示“…All Done”，则表示在线地图服务已启动
>
> 4.If "... All Done" is displayed on the terminal, it means the online map service has been started
```
...All Done

----------------------- WORKERMAN ---------------------------------------
Workerman version:4.0.19          PHP version:7.3.31
------------------------ WORKERS ----------------------------------------
worker               listen                              processes status
none                 websocket://0.0.0.0:4433            1         [ok]

```

### 如果您希望使用Docker启动，您需要参考根目录中start-docker.txt文件的内容

### If you wish to start using Docker, you need to refer to the contents of the start-docker.txt file in the root directory

## 可能遇到的问题(common problem)

>1.客户端无法连接到服务器
>
>1.The client cannot connect to the server

a.请检查客户端配置的服务器地址是否正确。

a.Please check whether the server address configured by the client is correct<br />

b.请检查防火墙是否允许4433端口出入。

b.Please check if the firewall allows port 4433 to enter and exit

c.请检查是否启用了ssl，如果启用了请检查ssl配置是否正确

c.Please check if SSL is enabled. If so, please verify if SSL configuration is correct.
<br>
<br>
>2.启动服务失败
>
>2.Failed to start service

a.请检查php程序是否有执行权限，如果没有权限，请在项目根目录运行： 

a.Please check if the PHP program has execution permission. If not, please run it in the project root directory：
```
bash

chmod -R 777 Workerman
chmod -R 777 api
chmod -R 777 class
chmod -R 777 tool
chmod 777 AmapService.php
chmod 777 start.sh

```
<br>
<br>

>3.无法创建数据库或无法连接数据库
>
>3.Unable to create database or unable to connect to database

a. 请检查公共帐户和密码以及root密码是否正确

a. Please check whether the public account and password, as well as the root password, are correct

<br>
<br>

>其他问题请在issues内提出，我会尽快回复您
>
>Please raise any other questions in the issues section and I will reply to you as soon as possible

## 💪 感谢所有的贡献者(Thank you to all the contributors)
ALIMU

Emilia-t
