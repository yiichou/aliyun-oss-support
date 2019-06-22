<?php

namespace OSS\WP;

class UrlHelper
{
    protected $wpBaseUrl = "";
    protected $ossBaseUrl = "";
    public static $imageMeta = "";
    /**
     * 替换图片url
     * 替换附件url（包括那些不在post中的图片，如文章特色图片）
     * 替换图片srcset
     * 替换附件srcset（如果附件为img，如文章特色图片）
     *
    */
    public function __construct()
    {   require_once ABSPATH . WPINC . '/class-phpmailer.php';
        $this->wpBaseUrl = wp_get_upload_dir()['baseurl'];
        $this->ossBaseUrl = rtrim(Config::$staticHost . Config::$storePath, '/');
        add_filter('oss_get_attachment_url', array($this, 'getOssUrl'), 1, 1);
        add_filter('oss_get_image_url', array($this, 'getOssImgUrl'), 1, 2);
        add_filter('wp_get_attachment_url', array($this,'replaceAttachmentUrl'), 2, 2);
        add_filter('wp_get_attachment_metadata', array($this, 'getImageMeta'), 900);
        add_filter('wp_calculate_image_srcset', array($this, 'replaceImgSrcsetUrl'),2,2);
        add_filter('wp_get_attachment_image_srcset', array($this, 'replaceImgSrcsetUrl'),2,2);  
    }
    /**
     * 添加url鉴权信息
     *
     * @param $url 输入单个url
     * @param $line debug，输入请求签名时相应语句的行号，默认值为空
     * @return $url 返回带签名的url
     * 
     * 如果设置中的url签名选项打开且鉴权类型为阿里云url鉴权A、B、C类型，则按对应鉴权类型对url添加签名信息
     */
    public function sign_url($url,$line)
    {
        date_default_timezone_set('PRC');
        $urlhost=parse_url($url, PHP_URL_SCHEME)."://".parse_url($url, PHP_URL_HOST); 
        $filename = parse_url($url, PHP_URL_PATH);      
        $expire_time= Config::$urlAuthExpTime;//set by hours
        $key=Config::$urlAuthPrimaryKey;
        if(Config::$enableUrlAuth && Config::$urlAuthMethod=="A"){
            $time = strtotime("+".$expire_time." hours");
            $sstring =$filename."-".$time."-0-0-".$key;
            $md5=md5($sstring);
            $auth_key="auth_key=".$time."-0-0-".$md5;
            if(strstr($url,'?')){
                $url = $url."&".$auth_key;
            }else{
                $url = $url."?".$auth_key;
            }            
        }
        if(Config::$enableUrlAuth && Config::$urlAuthMethod=="B"){
            $time=date("YmdHi",strtotime('+'.$expire_time.'hour'));
            $sstring=$key.$time.$filename;
            $md5=md5($sstring);            
            if(strstr($url,'?')){
                $url=explode("?",$url);
                $url=$urlhost."/".$time."/".$md5.$filename."?".$url[1];
            }else{
                $url=$urlhost."/".$time."/".$md5.$filename;
            }
        } 
        if(Config::$enableUrlAuth && Config::$urlAuthMethod=="C"){
            $time=dechex(time()+ $expire_time*3600);
            $sstring=$key.$filename.$time;
            $md5=md5($sstring);
            if(strstr($url,'?')){
                $url=explode('?',$url);
                $url=$urlhost."/".$md5."/".$time.$filename."?".$url[1];
            }else{        $url=$urlhost."/".$md5."/".$time.$filename; 
            }
        }
        if(Config::$enableUrlAuth_debug){
            $url=$url."&debug_auth_line=".$line; 
        }
        return $url;  
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
            
        }
        if (Config::$sourceImgProtect) {
            $url = $this->aliImageStyle($url, 'full');
        }
        $url = $this->sign_url($url,103);
        return $url;
    }
    /**
     * 获取imgmeta 
     * */

    public function getImageMeta($data)
    {
        self::$imageMeta = $data;
        return $data;
    }
    /**
     * 将图片 Srcsets Url 替换为 OSS Url
     *
     * @param $sources
     * @return mixed
     */
    public function replaceImgSrcsetUrl($sources)
    {
        $imageMeta = self::$imageMeta;
        $height=$imageMeta['height']<4096 ? $imageMeta['height']: 4096;
        $width=$imageMeta['width']<4096 ? $imageMeta['width']: 4096;
        $basename = \PHPMailer::mb_pathinfo($imageMeta['file'], PATHINFO_BASENAME);
        /**1.直接用ossBaseUrl构建srcset数据，不再使用原wpsrcset
         * 2.当图片服务打开时对url进行处理*/
        if (!$this->is_excluded($imageMeta['file'])) {
            if(Config::$enableImgService){
                foreach($imageMeta['sizes'] as $size => $info ){
                    if (Config::$enableImgStyle){
                        $url=$this->ossBaseUrl.'/'.$this->aliImageStyle($imageMeta['file'], $size);
                    }else{
                        $url=$this->ossBaseUrl.'/'.$this->aliImageResize($imageMeta['file'],$width);
                    }
                    $url = $this->sign_url($url,139);
                    $sources[$imageMeta['sizes'][$size]['width']] = array(
                        'url'=>$url,
                        'descriptor'=>'w',
                        'value'=>$imageMeta['sizes'][$size]['width'],
                        'size'=>$size,
                    );
                }
                /** 当原图保护打开时，添加一个带full关键字的url到srcset中*/
                if (Config::$sourceImgProtect){
                    $url=$this->ossBaseUrl.'/'.$this->aliImageStyle($imageMeta['file'], 'full');
                    $url = $this->sign_url($url,149);
                    $sources[$imageMeta['width']]=array(
                        'url'=>$url,
                        'descriptor'=>'w',
                        'value'=>$imageMeta['width'],
                        'size'=>'full',
                    );
                }
            }else{
                foreach ($sources as $k => $source) {
                        $url=str_replace($this->wpBaseUrl, $this->ossBaseUrl, $source['url']);
                        $url = $this->sign_url($url,161);
                        $sources[$k]['url'] =$url ;
                } 
            }
        }    
        return $sources;
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
        $url = $this->sign_url($url,185);   
        return $url;
    }

    /**
     * 将图片地址替换为 OSS 图片地址
     * 通过 apply_filters: oss_get_image_url 手动调用
     * eg. $url = apply_filters('oss_get_image_url', $image_url, $style)
     *
     * @param string $url 图片的 url 或相对路径
     * @param string/array $style 图片样式或包含高宽的数组. eg. 'large' or ['width' => 50, 'height' => 50]
     * @return string
     */
    public function getOssImgUrl($url, $style)
    {
        $url = $this->getOssUrl($url);
        if (!Config::$enableImgService) {
            $url = $this->sign_url($url,215);
            return $url;
        }
        else{
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
            $url = $this->sign_url($url,237);            
            return $url;
        }
    }

    protected function is_excluded($url)
    {
        return Config::$exclude && preg_match(Config::$exclude, $url);
    }

    protected function aliImageResize($file, $width)
    {
        return "{$file}?x-oss-process=image/resize,m_fill,w_{$width}";
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
