<?php

namespace OSS\WP;

class UrlHelper
{

    public function __construct()
    {
        add_filter('upload_dir', [$this, 'resetUploadBaseUrl'], 30 );

        if (Config::$enableImgService == false) return $this;

        add_filter('wp_get_attachment_metadata', [$this, 'replaceImgMeta'], 990);
        add_filter('wp_calculate_image_srcset_meta', [$this, 'replaceImgMeta'], 990);

        if (Config::$enableImgStyle == false) return $this;

        add_filter('wp_get_attachment_image_src', [$this,'fixPostThumbnailUrl'], 990, 3);
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
     * 修复某些情况下 WordPress 会使用原图替代其他尺寸图片
     * 开启图片样式时,修复这种兼容可以带来更好的浏览体验
     *
     * @param $image
     * @param $_
     * @param $size
     * @return mixed
     */
    public function fixPostThumbnailUrl($image, $_, $size)
    {
        if (false === strpos($image[0], "x-oss-process=style/")) {
            $size = is_string($size) ? $size : 'origin';
            $image[0] = $this->aliImageStyle($image[0], $size);
        }
        return $image;
    }

    /**
     * 设置 upload_url_path，使用外部存储OSS
     *
     * @param $uploads
     * @return mixed
     */
    public function resetUploadBaseUrl($uploads)
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