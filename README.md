##基于阿里云OSS的WordPress远程附件支持插件——阿里云附件(Aliyun Support)(修订版)

原插件地址：http://mawenjian.net/p/977.html

由于原插件作者没有持续更新，已经无法使用（或无法兼容最新版本环境），故对此进行一些小修正
修正日期：2014-8-27
版本号：1.1

###修订项目：
1. 插件年久失修，其内部调用的 Aliyun OSS php SDK 已升级
2. WordPress 3.5以后 设置->多媒体 中没有路径配置，导致配置不便
3. Aliyun OSS 可以绑定自己的域名，插件中不能简单的设置

###修订内容：
1. 升级 Aliyun-OSS-SDK 到 1.1.6 版本 (2014-06-25更新)
2. 设置中可直接配置访问路径 Url，支持已绑定到 OSS 的独立域名
3. 支持自定义 OSS 上文件的存放目录 （不影响本地存储，中途若修改请手动移动 OSS 上文件，否则可能链接不到之前的资源）
4. 修正原插件 bug 若干

###TODO:
原作者的代码在 github 上托管了一份，是不是应该联系原作者进行更新

###插件下载：
[OSS-Support.zip](http://ichou.cn/files/53fea643b1fc2dbd4a00000a)
[发布地址](http://ichou.cn/posts/ji-yu-a-li-yun-ossde-wordpressyuan-cheng-fu-jian-zhi-chi-cha-jian-a-li-yun-fu-jian-aliyun-support-xiu-ding-ban/edit)