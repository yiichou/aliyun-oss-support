<?php
/**
 * Plugin Name: Aliyun OSS
 * Description: 使用阿里云 OSS 作为附件的存储空间。 This is a plugin that used Aliyun OSS for attachments remote saving.
 * Author: Ivan Chou
 * Author URI: https://yii.im/
 * Version: 3.4.2
 * Updated_at: 2023-01-14
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
define('ALIYUN_OSS_MATEDATA_URL', 'https://chou.oss-cn-hangzhou.aliyuncs.com/aliyun-oss/plugin.json');
require(ALIYUN_OSS_PATH . '/autoload.php');

use OSS\WP\Config;

Config::init(ALIYUN_OSS_PATH);

if (Config::$staticHost) {
    new OSS\WP\UrlHelper();
}

function init() {
    Config::initOssClient();

    if (is_admin()) {
        new OSS\WP\Setting();
    }

    if (Config::$ossClient) {
        Config::$disableUpload || new OSS\WP\Upload(Config::$ossClient);
        new OSS\WP\Delete(Config::$ossClient);
    }
}


add_filter('admin_init', 'init');
add_filter('rest_api_init', 'init', 800);