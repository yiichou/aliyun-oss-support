=== Aliyun OSS support ===
Contributors: ichou
Tags: Aliyun,阿里云,OSS,storage
Requires at least: 3.5.0
Tested up to: 4.5.3
Stable tag: 2.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a plugin that used Aliyun Cloud Storage(Aliyun OSS) for attachments remote saving.

== Description ==

本插件主要为 Wordpress 提供基于阿里云 OSS 的远程附件存储功能，并且最大限度的依赖 wordpress 本身功能扩展来实现，以保证插件停用或博客搬迁时可以快速切换会原来的方式。插件采用静默工作方式，设置启用后会直接替换原生存储，无需增加任何额外操作。当然，缺点就是无法同时使用 本地 和 OSS 两边的资源，<del>或许稍微改下可以实现</del>（想想都好麻烦 ╮(╯▽╰)╭）

插件特色:

1.  支持阿里云 OSS 的图片服务（—>这个图片服务是个神器啊）

2.  支持设定文件在 OSS 上的存储路径

3.  全格式附件支持，不仅仅是图片

4.  可以设定本地文件是否保留

5.  不使用图片服务时，会连缩略图一起上传

6.  可以自定义域名（已绑定bucket的）（—> 这也算特色？）

7.  支持 wordpress 4.4+ 新功能 srcset，在不同分辨率设备上加载不同大小图片

8.  最后，也是最重要的特色，它的代码看上去还算干净

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
    将插件上传到 `/wp-content/plugins/plugin-name` 或者通过 WordPress 插件中心安装

2. Activate the plugin through the 'Plugins' screen in WordPress
    激活插件

3. Use the Settings->Oss Setting screen to configure the plugin
    在 Settings->Oss Setting 中配置相关参数

== Frequently Asked Questions ==

1.  `img_server_url` 有值时，即代表开启了 OSS 的图片服务支持

2.  图片服务开启时，只会上传原图到 OSS 上

3.  OSS-Http-Url 留空的话，WordPress 会切换回使用本地资源的状态，但是 OSS 上传依旧会进行

4.  Save path on OSS 不会影响本地存储路径，可是放心设置

== Screenshots ==

== Changelog ==

= 2.5 =
* Submitting the plugin to the WordPress Plugin Directory

