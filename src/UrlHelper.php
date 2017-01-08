<?php

namespace OSS\WP;

class UrlHelper
{

    public function __construct()
    {
        add_filter('upload_dir', [$this, 'resetUploadBaseUrl'], 30 );

        if (Config::$enableImgService) {
            add_filter('wp_get_attachment_metadata', [$this, 'replaceImgMeta'], 990);
            add_filter('wp_calculate_image_srcset_meta', [$this, 'replaceImgMeta'], 990);
            add_filter('wp_get_attachment_url', [$this,'replaceImgUrl'], 30, 2);
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
        if (empty($data['sizes']))
            return $data;

        $filename = end(explode('/',$data['file']));

        if (Config::$enableImgStyle) {
            foreach(['thumbnail', 'post-thumbnail', 'medium', 'medium_large', 'large'] as $style ) {
                if (isset($data['sizes'][$style]))
                    $data['sizes'][$style]['file'] = $this->aliImageStyle($filename, $style);
            }
        } else {
            foreach ($data['sizes'] as $size => $info)
                $data['sizes'][$size]['file'] = $this->aliImageResize($filename, $info['height'], $info['width']);
        }

        return $data;
    }

    /**
     * 重置图片链接，使用独立的图片服务器。
     * 仅在开启图片服务时启用
     *
     * @param $url
     * @param $post_id
     * @return mixed
     */
    public function replaceImgUrl($url, $post_id)
    {
        if (wp_attachment_is_image($post_id)) {
            $baseUrl = is_ssl() ?  set_url_scheme(Config::baseUrl()) : Config::baseUrl();
            $imgBaseUrl = rtrim(Config::$staticHost . Config::$storePath, '/');
            $url = str_replace($baseUrl, $imgBaseUrl, $url);

            Config::$enableImgStyle && $url .= '@!origin';
        }
        return $url;
    }

    /**
     * 设置 upload_url_path，使用外部存储OSS
     *
     * @param $uploads
     * @return mixed
     */
    public function resetUploadBaseUrl( $uploads )
    {
        if (Config::$staticHost) {
            $baseUrl = rtrim(Config::$staticHost . Config::$storePath, '/');
            $uploads['baseurl'] = $baseUrl;
        }
        return $uploads;
    }

    protected function aliImageResize($file, $height, $width)
    {
        return "{$file}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
    }

    protected function aliImageStyle($file, $style)
    {
        return "{$file}?x-oss-process=style/{$style}";
    }

}