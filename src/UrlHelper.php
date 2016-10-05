<?php

namespace OSS\WP;

class UrlHelper
{

    public function __construct()
    {
        add_filter('upload_dir', [$this, 'resetUploadBaseUrl'], 30 );

        if (Config::$imgHost) {
            add_filter('wp_get_attachment_metadata', [$this, 'replaceImgMeta'], 990);
            add_filter('wp_calculate_image_srcset_meta', [$this, 'replaceImgMeta'], 990);
            add_filter('wp_get_attachment_url', [$this,'replaceImgUrl'], 30, 2);
            add_filter('wp_calculate_image_srcset', [$this, 'replaceImgSrcsetUrl'], 30, 2);
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
        $fileExt = strrchr($filename,'.');

        if (Config::$enableImgStyle) {
            foreach(['thumbnail', 'post-thumbnail', 'medium', 'medium_large', 'large'] as $style )
                isset($data['sizes'][$style]) && $data['sizes'][$style]['file'] = "{$filename}@!{$style}";
        } else {
            foreach ($data['sizes'] as $size => $info)
                $data['sizes'][$size]['file'] = "{$filename}@{$info['height']}h_{$info['width']}w_1e_1c_1l{$fileExt}";
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
            $imgBaseUrl = rtrim(Config::$imgHost . Config::$storePath, '/');
            $url = str_replace($baseUrl, $imgBaseUrl, $url);

            Config::$enableImgStyle && $url .= '@!origin';
        }
        return $url;
    }

    /**
     * 重置 srcset 图片链接，使用独立的图片服务器。
     * 仅在开启图片服务时启用
     *
     * @param $sources
     * @return mixed
     */
    public function replaceImgSrcsetUrl($sources)
    {
        foreach( $sources as $w => $img )
            $sources[$w]['url'] = str_replace(Config::$staticHost, Config::$imgHost, $img['url']);
        return $sources;
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

}