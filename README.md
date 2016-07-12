## 基于阿里云OSS的WordPress远程附件支持插件——阿里云附件(Aliyun Support)(修订版)

插件发布地址及说明：http://yii.im/posts/aliyun-oss-support-plugin-for-wordpress

> 请瞩目：ACE 已经跑路了，OSS 推出了回源选项，有兴趣的小伙伴可以试一下，有了回源这个插件就不是必要的了

#### 当前版本

2.4.1

### 插件简介

本插件主要为 Wordpress 提供基于阿里云 OSS 的远程附件存储功能，并且最大限度的依赖 wordpress 本身功能扩展来实现，以保证插件停用或博客搬迁时可以快速切换会原来的方式。插件采用静默工作方式，设置启用后会直接替换原生存储，无需增加任何额外操作。当然，缺点就是无法同时使用 本地 和 OSS 两边的资源，<del>或许稍微改下可以实现</del>（想想都好麻烦 ╮(╯▽╰)╭）

* * *

### 插件特色

1.  支持阿里云 OSS 的图片服务（—>这个图片服务是个神器啊）  

2.  支持设定文件在 OSS 上的存储路径  

3.  全格式附件支持，不仅仅是图片  

4.  可以设定本地文件是否保留  

5.  不使用图片服务时，会连缩略图一起上传  

6.  可以自定义域名（已绑定bucket的）（—> 这也算特色？） 

7.  支持 wordpress 4.4+ 新功能 srcset，在不同分辨率设备上加载不同大小图片

8.  最后，也是最重要的特色，它的代码看上去还算干净

* * *

### 插件使用

1.  下载  
    [Aliyun-OSS-Support](https://github.com/IvanChou/aliyun-oss-support/archive/master.zip)  

2.  安装并启用  
3.  按提示设置  
![](http://chou.oss-cn-hangzhou.aliyuncs.com/yii.im%2Fasset%2F549b11107969690548090000%2FFid_220-220_1900406608627700_2e0d2a0ca198570.png)

4.  试一下能不能用(=<sup>‥</sup>=)

* * *

### 关于设置的一些说明

1.  `img_server_url` 有值时，即代表开启了 OSS 的图片服务支持 

2.  图片服务开启时，只会上传原图到 OSS 上  

3.  OSS-Http-Url 留空的话，WordPress 会切换回使用本地资源的状态，但是 OSS 上传依旧会进行  

4.  Save path on OSS 不会影响本地存储路径，可是放心设置  

* * *

### 更新日志

https://github.com/IvanChou/aliyun-oss-support/blob/master/CHANGELOG.md

* * *

### 插件下载：
[OSS-Support.zip](https://github.com/IvanChou/aliyun-oss-support/archive/master.zip)

[发布地址](https://yii.im/post/aliyun-oss-support-plugin-for-wordpress)
