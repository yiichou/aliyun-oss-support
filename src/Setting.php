<?php

namespace OSS\WP;

class Setting
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
        add_filter('plugin_action_links', [$this, 'pluginActionLink'], 10, 2);
        load_plugin_textdomain('aliyun-oss', false , Config::$pluginPath.'/languages');

        if ( !(Config::$bucket && Config::$accessKeyId && Config::$accessKeySecret))
            (isset($_GET['page']) && $_GET['page'] == 'aliyun-oss') || add_action('admin_notices', [$this, 'warning']);
    }

    /**
     * Registers a new settings page under Settings.
     */
    public function adminMenu()
    {
        add_options_page(
            __('Aliyun OSS', 'aliyun-oss'),
            __('Aliyun OSS', 'aliyun-oss'),
            'manage_options',
            'aliyun-oss',
            [$this, 'settingsPage']
        );
    }

    /**
     * 添加设置页面入口连接
     *
     * @param $links
     * @param $file
     * @return array
     */
    function pluginActionLink( $links, $file )
    {
        if ( $file == Config::$pluginPath.'/aliyun-oss.php' )
            array_unshift($links, '<a href="'.Config::$settingsUrl.'">'.__('Settings').'</a>');

        return $links;
    }

    public function warning()
    {
        $html = "<div id='oss-warning' class='updated fade'><p>".
            __('Aliyun OSS Support is almost ready. You should <a href="%s">Setting</a> it to work.', 'aliyun-oss').
            "</p></div>";
        echo sprintf($html, Config::$settingsUrl);
    }

    public function settingsPage()
    {
        if (!empty($_POST))
            $this->updateSettings();

        require __DIR__.'/../view/setting.php';
    }

    private function updateSettings()
    {
        $options = get_option('oss_options', []);

        isset($_POST['access_key']) && $options['ak'] = trim($_POST['access_key']);
        isset($_POST['region']) && $options['region'] = trim($_POST['region']);
        $options['internal'] = isset($_POST['internal']);
        $options['vpc'] = isset($_POST['vpc']);
        empty($_POST['access_key_secret']) || $options['sk'] = trim($_POST['access_key_secret']);

        isset($_POST['bucket']) && $options['bucket'] = trim($_POST['bucket']);
        isset($_POST['store_path']) && $options['path'] = trim($_POST['store_path']);
        $options['nolocalsaving'] = isset($_POST['no_local_saving']);
        if (isset($_POST['static_host']))
            $options['static_url'] = preg_replace('/(.*\/\/|)(.+?)(\/.*|)$/', '$2', $_POST['static_host']);

        if (isset($_POST['img_host_enable'])) {
            $options['img_service'] = true;
            $options['img_style'] = isset($_POST['img_style']);
        } else{
            $options['img_service'] = false;
            $options['img_style'] = false;
        }

        isset($_POST['keep_settings']) && $options['keep_settings'] = !!$_POST['keep_settings'];

        unset($options['img_url']);
        update_option('oss_options', $options);

        echo '<div class="updated"><p><strong>'. __('The settings have been saved', 'aliyun-oss') .'.</strong></p></div>';
    }
}