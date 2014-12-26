## 基于阿里云OSS的WordPress远程附件支持插件——阿里云附件(Aliyun Support)(修订版)

插件发布地址及说明：http://yii.im/posts/aliyun-oss-support-plugin-for-wordpress

原插件地址：http://mawenjian.net/p/977.html

由于原插件作者没有持续更新，已经无法使用（或无法兼容最新版本环境），故对此进行一些小修正

____________________

#### 插件已被 ACE 官方加入推荐使用： 

http://bbs.aliyun.com/read/169542.html?spm=0.0.0.0.d6eYLP

____________________

### 版本号：2.1.2

修正日期：2014-12-26

#### 修订内容：

修正在非当前月份文章中上传图片时，由于缩略图无法上传导致错误的 bug.

##### 感谢：网友 sjw(at)cnsjw.cn 协助修订

____________________

### 版本号：2.1.1

修正日期：2014-12-25

#### 修订内容：

处理 OSS 自定义存储 path 为空时出现的多斜线 Bug

##### 感谢：网友 sjw(at)cnsjw.cn 反馈

____________________

### 版本号：2.1

修正日期：2014-11-7

#### 修订内容：

在某些环境中，启用插件时提示

```
这个插件启用过程中产生了3个字符的**异常输出**。如果您遇到了……
```

原因为 sdk.class.php 这个文件编码问题，已修复

若仍有这个报错，请尝试转换文件编码与您的网站环境编码一致。

##### 感谢：网友 tang6818(at)foxmail.com 反馈

____________________

### 版本号：2.0

修正日期：2014-9-1

#### 修订内容：
1. 完全重构，优化代码
2. 支持 Aliyun OSS 的图片服务
3. 改变钩子嵌入机制，支持所有附件（以前的版本只上传图片，而且启用时其他附件完全不可用了，坑！！）
4. 添加卸载、不残留
5. 支持 Aliyun ACE （ 切换到 ACE 分支 ）


____________________

### 版本号：1.1

修正日期：2014-8-27

#### 修订项目：
1. 插件年久失修，其内部调用的 Aliyun OSS php SDK 已升级
2. WordPress 3.5以后 设置->多媒体 中没有路径配置，导致配置不便
3. Aliyun OSS 可以绑定自己的域名，插件中不能简单的设置

#### 修订内容：
1. 升级 Aliyun-OSS-SDK 到 1.1.6 版本 (2014-06-25更新)
2. 设置中可直接配置访问路径 Url，支持已绑定到 OSS 的独立域名
3. 支持自定义 OSS 上文件的存放目录 （不影响本地存储，中途若修改请手动移动 OSS 上文件，否则可能链接不到之前的资源）
4. 修正原插件 bug 若干
5. 优化代码 ~~（移除所有 Notice 级报错）~~

____________________

### TODO:
~~原作者的代码在 github 上托管了一份，是不是应该联系原作者进行更新~~

配置页面需要添加英文支持

查探 wordpress 开启 debug 时，插件为何有报错信息

### 插件下载：
[OSS-Support.zip](https://github.com/IvanChou/aliyun-oss-support/archive/master.zip)

[OSS-Support-ACE.zip](https://github.com/IvanChou/aliyun-oss-support/archive/Aliyun-ACE.zip) - 阿里云 ACE 适用版本 

[发布地址](http://yii.im/posts/aliyun-oss-support-plugin-for-wordpress)