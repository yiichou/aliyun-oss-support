<?php

namespace OSS\WP;

class UrlHelper
{
    protected $wpBaseUrl = "";
    protected $ossBaseUrl = "";

    public function __construct()
    {
        if (empty(Config::$staticHost))
            return;

        $this->wpBaseUrl = wp_get_upload_dir()['baseurl'];
        $this->ossBaseUrl = rtrim(Config::$staticHost . Config::$storePath, '/');

        add_filter('oss_get_attachment_url', array($this, 'getOssUrl'), 9, 1);
        add_filter('oss_get_image_url', array($this, 'getOssImgUrl'), 9, 2);

        add_filter('wp_get_attachment_url', array($this,'replaceAttachmentUrl'), 300, 2);
        add_filter('wp_calculate_image_srcset', array($this, 'replaceImgSrcsetUrl'), 300);

        if (Config::$enableImgService) {
            add_filter('wp_get_attachment_metadata', array($this, 'replaceImgMeta'), 900);
        }
    }

    /**
     * 将图片/附件 Url 替换为 OSS Url
     *
     * @param $url
     * @param $post_id
     * @return mixed
     */
    public function replaceAttachmentUrl($url, $post_id)
    {
        if (!$this->is_excluded($url)) {
            $url = str_replace($this->wpBaseUrl, $this->ossBaseUrl, $url);

            if (Config::$sourceImgProtect && wp_attachment_is_image($post_id)) {
                $url = $this->aliImageStyle($url, 'full');
            }
        }
        return $url;
    }

    /**
     * 将图片 Srcsets Url 替换为 OSS Url
     *
     * @param $sources
     * @return mixed
     */
    public function replaceImgSrcsetUrl($sources)
    {
        foreach ($sources as $k => $source) {
            if (!$this->is_excluded($source['url'])) {
                $sources[$k]['url'] = str_replace($this->wpBaseUrl, $this->ossBaseUrl, $source['url']);

                if (Config::$sourceImgProtect && (false === strstr($sources[$k]['url'], Config::$customSeparator))) {
                    $sources[$k]['url'] = $this->aliImageStyle($sources[$k]['url'], 'full');
                }
            }
        }
        return $sources;
    }

    /**
     * 图片服务模式下, 修改图片元数据，以使用 Aliyun 的图片服务
     *
     * @param $data
     * @return mixed
     */
    public function replaceImgMeta($data)
    {
        if (empty($data['sizes']) || $this->is_excluded($data['file']) ||
            (wp_debug_backtrace_summary(null, 4, false)[0] == 'wp_delete_attachment')) {
            return $data;
        }

        if (!class_exists('\PHPMailer')) {
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
        }
        $basename = \PHPMailer::mb_pathinfo($data['file'], PATHINFO_BASENAME);
        $styles = get_intermediate_image_sizes();
        $styles[] = 'full';

        foreach ($data['sizes'] as $size => $info) {
            if (Config::$enableImgStyle && in_array($size, $styles)) {
                $data['sizes'][$size]['file'] = $this->aliImageStyle($basename, $size);
            } else {
                $data['sizes'][$size]['file'] = $this->aliImageResize($basename, $info['height'], $info['width']);
            }
        }

        return $data;
    }

    /**
     * 将附件地址替换为 OSS 地址
     * 通过 apply_filters: oss_get_attachment_url 手动调用
     * eg. $url = apply_filters('oss_get_attachment_url', $url)
     *
     * @param string $url 附件的 url 或相对路径
     * @return string
     */
    public function getOssUrl($url)
    {
        $uri = parse_url($url);
        if (empty($uri['host']) || false === strstr(Config::$staticHost, $uri['host'])) {
            $url = Config::$staticHost . Config::$storePath . '/' . ltrim($uri['path'], '/');
        }

        return $url;
    }

    /**
     * 将图片地址替换为 OSS 图片地址
     * 通过 apply_filters: oss_get_image_url 手动调用
     * eg. $url = apply_filters('oss_get_image_url', $image_url, $style)
     *
     * @param string $url 图片的 url 或相对路径
     * @param srting/array $style 图片样式或包含高宽的数组. eg. 'large' or ['width' => 50, 'height' => 50]
     * @return string
     */
    public function getOssImgUrl($url, $style)
    {
        $url = $this->getOssUrl($url);
        if (!Config::$enableImgService) {
            return $url;
        }

        if (Config::$enableImgStyle) {
            $style = (is_string($style) && !empty($style)) ? $style : 'full';
            $url = $this->aliImageStyle($url, $style);
        } else {
            if (is_array($style)) {
                $height = $style['height'];
                $width = $style['width'];
            } elseif (!empty($style)) {
                $height = get_option($style . '_size_h');
                $width = get_option($style . '_size_w');
            }
            if ($height && $height) {
                $url = $this->aliImageResize($url, $height, $width);
            }
        }

        return $url;
    }

    protected function is_excluded($url)
    {
        return Config::$exclude && preg_match(Config::$exclude, $url);
    }

    protected function aliImageResize($file, $height, $width)
    {
        return "{$file}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
    }

    protected function aliImageStyle($file, $style)
    {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'gif') {
            return $file;
        } elseif ($style == 'full' && !Config::$sourceImgProtect) {
            return $file;
        } else {
            return $file . Config::$customSeparator . $style;
        }
    }
}
