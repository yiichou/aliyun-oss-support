# 阿里云 OSS 支持插件 (Aliyun OSS For WordPress)

本插件主要为 Wordpress 提供基于阿里云 OSS 的远程附件存储功能，并且最大限度的依赖 Wordpress 本身功能扩展来实现，以保证插件停用或博客搬迁时可以快速切换回原来的方式。

## 当前版本

Stable: [`3.0.0`](https://github.com/IvanChou/aliyun-oss-support/releases/latest)

## 插件特色

1. 支持 Aliyun OSS 的图片服务（根据参数获得不同尺寸的图片）
2. 自定义文件在 Bucket 上的存储位置  
3. 支持 Https 站点
4. 全格式附件支持，不仅仅是图片
5. 支持 wordpress 4.4+ 新功能 srcset，在不同分辨率设备上加载不同大小图片
6. 支持在 WordPress 后台编辑图片
7. 图片服务支持预设图片样式，可用于图片打水印的需求
8. 中英文双语支持，方便使用英文为默认语言的同学
9. 代码遵循 PSR-4 规则编写，并使用 phar 文件作为 release 版本

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

## 启用图片服务

关于插件中图片服务的 Wiki: [How to use Image Service](https://github.com/IvanChou/aliyun-oss-support/wiki/How-to-use-Image-Service)

通常使用中,你不需要特别的去了解它。如果你对插件预设的图片处理规则不满意,或者你需要给图片加水印,可以参看这篇说明

## 题外

本插架由官方商店中 马文建(@mawenjian) 同学的[「阿里云附件」](https://github.com/mawenjian/aliyun-oss-support)插件拓展而来。由于马同学在曾经的某段时间里没能即时维护这个项目，也没有开源，于是我在修复 bug 并 rebuild 后，将这个野生的修订版发布到阿里云社区，意外获得了 ACE 社区官方管理组的推荐。

后来，马同学 release 了 2.0 版本并开源他的项目了，我就中止了这边的维护。但依旧是有网友提 Issue 或发邮件来询问，加上自己的需求，有时间的时候，也就修补一下大家反应的问题，也许还是会有人会用到。

由于插件沿用了马同学插件的名字，并 WordPress 官方不再允许在未经授权的情况下使用知名商标（如：Aliyun) 作为插件名称的一部分，所以这个插件并没有提交官方商店的计划。（重新想个名字对我来说太麻烦了~~(￣▽￣)）

## 更新日志

https://github.com/IvanChou/aliyun-oss-support/blob/master/CHANGELOG.md

## 贡献代码

1. Fork 这个仓库
2. Clone 源码并安装到本地 WordPress 中
3. 完成你的修改并测试
4. 提交一个 Pull Request

## 开源协议

BSD

