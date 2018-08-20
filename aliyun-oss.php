<?php
/**
 * Plugin Name: Aliyun OSS
 * Description: 使用阿里云 OSS 作为附件的存储空间。 This is a plugin that used Aliyun OSS for attachments remote saving.
 * Author: Ivan Chou
 * Author URI: https://yii.im/
 * Version: 3.2.2
 * Updated_at: 2018-08-21
 */

/*  Copyright 2016  Ivan Chou  (email : yiichou@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('ALIYUN_OSS_PATH', dirname(__FILE__));
require(ALIYUN_OSS_PATH.'/autoload.php');

use OSS\WP\Config;
Config::init(ALIYUN_OSS_PATH);
new OSS\WP\Setting();

try {
    Config::$ossClient = new OSS\OssClient(Config::$accessKeyId, Config::$accessKeySecret, Config::$endpoint);
    new OSS\WP\Upload();
    new OSS\WP\Delete();
    new OSS\WP\UrlHelper();
} catch (OSS\Core\OssException $e) {
    register_activation_hook(__FILE__, function () {
        add_option('oss_options', Config::$originOptions, '', 'yes');
    });
}

require(ALIYUN_OSS_PATH.'/vendor/plugin-update-checker/plugin-update-checker.php');
Puc_v4_Factory::buildUpdateChecker(
    'https://chou.oss-cn-hangzhou.aliyuncs.com/aliyun-oss/plugin.json',
    __FILE__,
    Config::$pluginPath
);
