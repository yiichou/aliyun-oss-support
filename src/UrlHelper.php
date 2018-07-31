<?php

namespace OSS\WP;

class UrlHelper
{
    public function __construct()
    {
        add_filter('upload_dir', array($this, 'resetUploadBaseUrl'), 30);
        add_filter('oss_get_attachment_url', array($this, 'getOssUrl'), 9, 1);
        add_filter('oss_get_image_url', array($this, 'getOssImgUrl'), 9, 2);

        if (Config::$enableImgService) {
            add_filter('wp_get_attachment_metadata', array($this, 'replaceImgMeta'), 900);

            if (Config::$enableImgStyle && Config::$sourceImgProtect) {
                add_filter('wp_get_attachment_url', array($this,'replaceOriginalImgUrl'), 30, 2);
                add_filter('wp_calculate_image_srcset', array($this, 'replaceOriginalImgSrcset'), 900);
            }
        }
    }

    /**
     * 修改从数据库中取出的图片信息，以使用 Aliyun 的图片服务
     * 仅在开启图片服务时启用
     *
     * @param $data
     * @return mixed
     */
    public function replaceImgMeta($data)
    {
        if (empty($data['sizes']) || (wp_debug_backtrace_summary(null, 4, false)[0] == 'wp_delete_attachment')) {
            return $data;
        }

        $basename = pathinfo($data['file'], PATHINFO_BASENAME);
        $styles = get_intermediate_image_sizes();
        $styles[] = 'full';

        if (Config::$enableImgStyle) {
            foreach ($styles as $style) {
                if (isset($data['sizes'][$style])) {
                    $data['sizes'][$style]['file'] = $this->aliImageStyle($basename, $style);
                }
            }
        } else {
            foreach ($data['sizes'] as $size => $info) {
                $data['sizes'][$size]['file'] = $this->aliImageResize($basename, $info['height'], $info['width']);
            }
        }

        return $data;
    }

    /**
     * 将原图链接替换为 full 样式的 OSS 图片地址
     * 仅在开启图片服务 + 原图保护时启用
     *
     * @param $url
     * @param $post_id
     * @return mixed
     */
    public function replaceOriginalImgUrl($url, $post_id)
    {
        if (wp_attachment_is_image($post_id)) {
            $url = $this->aliImageStyle($url, 'full');
        }
        return $url;
    }

    /**
     * 将 Srcset 中原图链接替换为 full 样式的 OSS 图片地址
     * 仅在开启图片服务 + 原图保护时启用
     *
     * @param $sources
     * @return mixed
     */
    public function replaceOriginalImgSrcset($sources)
    {
        foreach ($sources as $k => $source) {
            if (false === strstr($source['url'], Config::$customSeparator)) {
                $sources[$k]['url'] = $this->aliImageStyle($source['url'], 'full');
            }
        }
        return $sources;
    }

    /**
     * 设置 upload_url_path，将图片/附件的路径修改为 OSS 地址
     *
     * @param $uploads
     * @return mixed
     */
    public function resetUploadBaseUrl($uploads)
    {
        if (Config::$staticHost) {
            $base_url = rtrim(Config::$staticHost . Config::$storePath, '/');
            $uploads['baseurl'] = $base_url;
        }
        return $uploads;
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
