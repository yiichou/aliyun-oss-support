<?php
//防止有人恶意访问此文件，所以在没有 WP_UNINSTALL_PLUGIN 常量的情况下结束程序
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

$options = get_option('oss_options', []);

$options['keep_settings'] || delete_option('oss_options');
?>