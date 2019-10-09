<?php

namespace OSS\WP;

class UrlHelper
{
    protected $wpBaseUrl = "";
    protected $ossBaseUrl = "";
    public function __construct()
    {
        $this->wpBaseUrl = wp_get_upload_dir()['baseurl'];
        $this->ossBaseUrl = rtrim(Config::$staticHost . Config::$storePath, '/');
        add_filter('oss_get_attachment_url', array($this, 'getOssUrl'), 9, 1);
        add_filter('oss_get_image_url', array($this, 'getOssImgUrl'), 9, 2);
        add_filter('wp_get_attachment_url', array($this,'replaceAttachmentUrl'), 300, 2);
        add_filter('wp_calculate_image_srcset', array($this, 'replaceImgSrcsetUrl'), 300);
        if (Config::$enableImgService) {
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            add_filter('wp_get_attachment_metadata', array($this, 'replaceImgMeta'), 900);
        }
        add_filter( 'content_edit_pre',array($this,'ClassicUrlEditPre'), 10, 2 );//经典编辑器加载时,如果鉴权打开,则在内容加载时给链接添加鉴权信息     
        add_filter( 'rest_prepare_post',array($this,'RestUrlEditPre'), 10, 3 );//古藤堡编辑器及其他依赖WP RESTapi的编辑器加载时,如果鉴权打开,则在内容加载时给链接添加鉴权信息
        add_filter( 'content_save_pre',array($this,'imageUrlSavePre'));//保存内容时去掉鉴权信息,经过测试,如果经典编辑器运行在插件模式下时,此过滤器不起作用.古藤堡编辑器测试可用.
    }

    /**
     * 添加url鉴权信息
     *
     * @param $url
     * @return $url 返回带签名的url
     * 
     * 如果设置中的url签名选项打开且鉴权类型为阿里云url鉴权A、B、C类型，则按对应鉴权类型对url添加签名信息
     */
    public function sign_url($url)
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
            }else{
                $url=$urlhost."/".$md5."/".$time.$filename; 
            }
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

            if (Config::$sourceImgProtect && wp_attachment_is_image($post_id)) {
                $url = $this->aliImageStyle($url, 'full');
            }
        }
        $url = $this->sign_url($url);
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
            $sources[$k]['url'] = $this->sign_url( $sources[$k]['url']);
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
        $basename = \PHPMailer::mb_pathinfo($data['file'], PATHINFO_BASENAME);
        $styles = get_intermediate_image_sizes();
        $styles[] = 'full';
        foreach ($data['sizes'] as $size => $info) {
            if (Config::$enableImgStyle && in_array($size, $styles)) {
                $data['sizes'][$size]['file'] = $this->aliImageStyle($basename, $size);
            } else {
                $data['sizes'][$size]['file'] = $this->aliImageResize($basename, $info['height'], $info['width']);
            }
            $url = $this->ossBaseUrl.'/'.$data['sizes'][$size]['file'];
            $url = $this->sign_url($url);
            $data['sizes'][$size]['file'] = str_replace($this->ossBaseUrl.'/','',$url);
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
        $url = $this->sign_url($url);        
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
        $url = $this->sign_url($url);
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


    /**
     * fucntion RestUrlEditPre 在使用REST API的编辑器加载文章时修改文章url链接，添加鉴权信息
     * return 修改后的文章内容
     * 
     * **/
    public function RestUrlEditPre( $data, $post, $request ) {
        if(!Config::$enableUrlAuth){return $data;}//如果鉴权关闭则返回
        $content = $data->data['content']['raw'];
        $content =  $this->ContentUrlAuth($content); 
        $data->data['content']['raw']=$content;
        return $data;
    }
    
    /**
     * fucntion ClassicUrlEditPre 在wp classic编辑器加载文章时修改文章url链接，添加鉴权信息
     * return 修改后的文章内容
     * 
     * **/
    public function ClassicUrlEditPre( $content, $post_id ) {
        if(!Config::$enableUrlAuth){return $content;}//如果鉴权关闭则返回
        $content =  $this->ContentUrlAuth($content); 
        return $content;
    }

    /**
     * function ContentUrlAuth add auth for content
     * @param $content
     * @return $content
     * 
     * **/
    public function ContentUrlAuth($content){
        if(!$content){return;}//内容为空时返回
        $matches=preg_match_all('/<img.*? src=".*"\/>/',stripslashes($content),$imgs);
        if($matches==0){return $content;}//如果data不包含图片文件则返回
        foreach($imgs[0] as $val){
            preg_match('/src="([^"]*)"/',var_export($val,true),$url);
                if(!empty($url[0])){
                    $url=$url[1];
                    $url_auth= $this->sign_url($url);//对url添加鉴权
                    $img_auth=str_replace($url,$url_auth,$val);//生成带鉴权url的img标签
                    $content=str_replace($val,$img_auth,$content);//使用带鉴权img标签替换文章原有的img标签
                }
        }
        return $content;
    }
     /**
      * function imageUrlSavePre 执行动作：移除cdn鉴权参数
     * @param $content 输入编辑器内容;
     * @return $content;
     */
    public function imageUrlSavePre($content){//该方法暂时只适用于A方式
        if(Config::$enableUrlAuth||1){//如果鉴权打开则进行操作
            $matches=preg_match_all('/<img.*? src=".*"\/>/',stripslashes($content),$imgs);
            if($matches>0){
                foreach($imgs[0] as $val){
                preg_match('/auth_key.*(?=".*alt)/',var_export($val,true),$auth_key);
                    if(!empty($auth_key[0])){
                        $content=str_replace('?'.$auth_key[0],'',$content); 
                        $content=str_replace('&amp;'.$auth_key[0],'',$content);              
                    }
                }
            }
        }        
        return $content;
    }
}
