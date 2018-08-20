# 阿里云 OSS 支持插件 (Aliyun OSS For WordPress)

本插件主要为 Wordpress 提供基于阿里云 OSS 的远程附件存储功能，并且最大限度的依赖 Wordpress 本身功能扩展来实现，以保证插件停用或博客搬迁时可以快速切换回原来的方式。

## 当前版本

Stable: [`3.2.2`](https://github.com/IvanChou/aliyun-oss-support/releases/latest)

## 插件特色

1. 支持 Aliyun OSS 的图片服务（根据参数获得不同尺寸的图片）
2. 自定义文件在 Bucket 上的存储位置  
3. 支持 Https 站点
4. 支持阿里云内网和 VPC 网络
6. 全格式附件支持，不仅仅是图片
7. 支持 wordpress 4.4+ 新功能 srcset，在不同分辨率设备上加载不同大小图片
8. 支持在 WordPress 后台编辑图片
9. 支持预设图片样式，图片保护，自定义分割符
9. 中英文双语支持，方便使用英文为默认语言的同学
10. 支持在其他插件/主题中通过系统钩子调用插件功能
11. 代码遵循 PSR-4 规则编写

## 插件使用

关于插件使用方式的 Wiki: [Quick start](https://github.com/IvanChou/aliyun-oss-support/wiki/Quick-start)

### 下载

[latest release](https://github.com/IvanChou/aliyun-oss-support/releases/latest)

### 安装

将插件解压上传到 `/wp-content/plugins/` 或者通过 WordPress 插件中心上传安装

注意上传时 zip 包的名字,建议使用 `aliyun-oss.zip`

### 配置

启用插件 `Aliyun OSS`

进入设置页面 完成相关设置

![screenshot](https://github.com/IvanChou/aliyun-oss-support/blob/master/screenshot.png)

## 关于图片服务

阿里云 OSS 提供了根据 url 参数来获得各种尺寸的 `阿里云OSS图片处理服务（Image Service，简称 IMG）`, 相比起 WordPress 上传的时候生成各种尺寸的图片, 这是一种更优雅的解决方案, 占用的存储空间更小, 尺寸变更更灵活。

如何开启并配置图片服务, 请参见: [How to use Image Service](https://github.com/IvanChou/aliyun-oss-support/wiki/How-to-use-Image-Service)

另外还有几点需要你了解:

1. 开启图片服务时, 只有原图会被上传到 OSS, 缩略图本地依旧会生成但不会上传
2. 基于第 1 条, 建议开启图片服务后就不要关了, 关掉会导致之前上传的图片缩略图丢失
3. 基于第 2 条, 如果你一定要关或者不再使用本插件, 可以使用本地保留的缩略图做数据恢复, That's EZ.

## 关于不在本地服务器上保留文件

『不在本地服务器上保留文件』是一个看上去炫酷实则没什么用的功能, 没有特殊需求强烈建议不要开启, 理由如下:

1. 由于本地文件早早被删掉了, 导致从后台删除图片或附件时, OSS 里面的资源无法被删掉
2. 如果你同时开启了『图片服务』, 当你想停用这个插件的时候不可避免的会遇到缩略图丢失问题

****

## 题外

本插架由官方商店中 马文建(@mawenjian) 同学的[「阿里云附件」](https://github.com/mawenjian/aliyun-oss-support)插件拓展而来。由于马同学在曾经的某段时间里没能即时维护这个项目，也没有开源，于是我在修复 bug 并 rebuild 后，将这个野生的修订版发布到阿里云社区，意外获得了 ACE 社区官方管理组的推荐。

后来，马同学 release 了 2.0 版本并开源他的项目了，我就中止了这边的维护。但依旧是有网友提 Issue 或发邮件来询问，加上自己的需求，有时间的时候，也就修补一下大家反应的问题，也许还是会有人会用到。

由于插件沿用了马同学插件的名字，并 WordPress 官方不再允许在未经授权的情况下使用知名商标（如：Aliyun) 作为插件名称的一部分，所以这个插件并没有提交官方商店的计划。（重新想个名字对我来说太麻烦了~~(￣▽￣)）

## 更新日志

https://github.com/IvanChou/aliyun-oss-support/blob/master/CHANGELOG.md

## 冲突列表

- EvernoteSync
- ultimate member
- ~~BuddyPress~~
- WP-AutoPost
- ARMember
- minify

## 依赖

- https://github.com/aliyun/aliyun-oss-php-sdk

## 贡献代码

1. Fork 这个仓库
2. Clone 源码并安装到本地 WordPress 中
3. 完成你的修改并测试
4. 提交一个 Pull Request

## 开源协议

BSD

