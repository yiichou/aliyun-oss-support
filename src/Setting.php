<?php

namespace OSS\WP;

class Setting
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'downloadImgStyleProfile'));
        add_filter('plugin_action_links', array($this, 'pluginActionLink'), 10, 2);
        load_plugin_textdomain('aliyun-oss', false, Config::$pluginPath.'/languages');

        if (!(Config::$bucket && Config::$accessKeyId && Config::$accessKeySecret)) {
            (isset($_GET['page']) && $_GET['page'] == 'aliyun-oss') || add_action('admin_notices', array($this, 'warning'));
        }
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
            array($this, 'settingsPage')
        );
    }

    /**
     * 添加设置页面入口连接
     *
     * @param $links
     * @param $file
     * @return array
     */
    public function pluginActionLink($links, $file)
    {
        if ($file == Config::$pluginPath.'/aliyun-oss.php') {
            array_unshift($links, '<a href="'.Config::$settingsUrl.'">'.__('Settings').'</a>');
        }

        return $links;
    }

    /**
     * Add a admin notice for setting up Aliyun OSS Support
     */
    public function warning()
    {
        $html = "<div id='oss-warning' class='notice-info notice fade'><p>".
            __('Aliyun OSS Support is almost ready. You should <a href="%s">Setting</a> it to work.', 'aliyun-oss').
            "</p></div>";
        echo sprintf($html, Config::$settingsUrl);
    }

    public function settingsPage()
    {
        !empty($_POST) && $this->updateSettings();
        require __DIR__.'/../view/setting.php';
    }

    public function downloadImgStyleProfile()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'download-img-style-profile') {
            header('Content-type: application/txtf');
            header('Content-Disposition: attachment; filename="aliyun-img-styles.txt"');
            echo $this->imageStyleProfile();
            exit;
        }
    }

    private function updateSettings()
    {
        $options = get_option('oss_options', array());

        isset($_POST['access_key']) && $options['ak'] = trim($_POST['access_key']);
        isset($_POST['region']) && $options['region'] = trim($_POST['region']);
        $options['internal'] = isset($_POST['internal']);
        $options['vpc'] = isset($_POST['vpc']);
        empty($_POST['access_key_secret']) || $options['sk'] = trim($_POST['access_key_secret']);

        isset($_POST['bucket']) && $options['bucket'] = trim($_POST['bucket']);
        isset($_POST['store_path']) && $options['path'] = trim($_POST['store_path']);
        $options['disable_upload'] = isset($_POST['disable_upload']);
        $options['nolocalsaving'] = empty($options['disable_upload']) && isset($_POST['no_local_saving']);
        if ( !empty($_POST['static_host']) ) {
            $options['static_url'] = preg_replace('/(.*\/\/|)(.+?)(\/.*|)$/', '$2', $_POST['static_host']);
        } elseif ( !empty($options['bucket']) ) {
            $options['static_url'] = join([$options['bucket'] , $options['region'], 'aliyuncs.com'], '.');
        }

        $options['img_service'] = isset($_POST['img_service']);
        $options['img_style'] = $options['img_service'] ? isset($_POST['img_style']) : false;
        $options['source_img_protect'] = $options['img_style'] ? isset($_POST['source_img_protect']) : false;
        if ($options['img_style'] && isset($_POST['custom_separator'])) {
            $options['custom_separator'] = $_POST['custom_separator'];
        } else {
            $options['custom_separator'] = '';
        }

        $options['keep_settings'] = isset($_POST['keep_settings']);
        isset($_POST['exclude']) && $options['exclude'] = trim(stripslashes($_POST['exclude']));
        unset($options['img_url']);
        
        //url鉴权设置
        $options['urlAuth'] = isset($_POST['urlAuth']);
        isset($_POST['authMethod']) && $options['authMethod'] = trim($_POST['authMethod']);
        empty($_POST['authPrimaryKey']) || $options['authPrimaryKey'] = trim($_POST['authPrimaryKey']);
        empty($_POST['authAuxKey']) || $options['authAuxKey'] = trim($_POST['authAuxKey']);
        isset($_POST['authExpTime']) && $options['authExpTime'] = (int)$_POST['authExpTime'];
        
        update_option('oss_options', $options);

        if (true === Config::checkOssConfig($options)) {
            echo '<div class="updated"><p><strong>'. __('The settings have been saved', 'aliyun-oss') .'.</strong></p></div>';
        }
    }

    private function imageStyleProfile()
    {
        global $_wp_additional_image_sizes;
        $content = '';

        foreach ( get_intermediate_image_sizes() as $s ) {
            $style = ['resize'];

            if (isset($_wp_additional_image_sizes[$s]['crop'])) {
                $crop = $_wp_additional_image_sizes[$s]['crop'];
            } else {
                $crop = get_option("{$s}_crop");
            }
            $style[] = $crop ? 'm_fill' : 'm_lfit';


            if (isset($_wp_additional_image_sizes[$s]['width'])) {
                $width = intval($_wp_additional_image_sizes[$s]['width']);
            } else {
                $width = get_option("{$s}_size_w");
            }
            $width > 0 && $style[] = "w_{$width}";

            if (isset($_wp_additional_image_sizes[$s]['height'])) {
                $height = intval($_wp_additional_image_sizes[$s]['height']);
            } else {
                $height = get_option("{$s}_size_h");
            }
            $height > 0 && $style[] = "h_{$height}";

            $style[] = 'limit_1';
            $content .= "styleName:{$s},styleBody:image/" . join(',', $style) . "/auto-orient,0\n";
        }
        $content .= 'styleName:full,styleBody:image/auto-orient,0';
        return $content;
    }
}
