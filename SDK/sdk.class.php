<?php
/**
 * OSS(Open Storage Services) PHP SDK 
 */
//设置默认时区
date_default_timezone_set('Asia/Shanghai');

//检测API路径
if(!defined('OSS_API_PATH'))
    define('OSS_API_PATH', dirname(__FILE__));

//加载conf.inc.php文件,里面保存着OSS的地址以及用户访问的ID和KEY
require_once OSS_API_PATH.DIRECTORY_SEPARATOR.'conf.inc.php';
require_once OSS_API_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'requestcore'.DIRECTORY_SEPARATOR.'requestcore.class.php';
require_once OSS_API_PATH.DIRECTORY_SEPARATOR.'util'.DIRECTORY_SEPARATOR.'mimetypes.class.php';
require_once OSS_API_PATH.DIRECTORY_SEPARATOR.'util'.DIRECTORY_SEPARATOR.'oss_util.class.php';
//检测语言包
if(file_exists(OSS_API_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.ALI_LANG.'.inc.php')){
    require_once OSS_API_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.ALI_LANG.'.inc.php';
}else{
    throw new OSS_Exception(OSS_LANG_FILE_NOT_EXIST);
}

//定义软件名称，版本号等信息
define('OSS_NAME', 'aliyun-oss-sdk-php');
define('OSS_VERSION', '1.1.7');
define('OSS_BUILD', '20150311');
define('OSS_AUTHOR', 'xiaobing');

//检测get_loaded_extensions函数是否被禁用。由于有些版本把该函数禁用了，所以先检测该函数是否存在。
if(function_exists('get_loaded_extensions')){
    //检测curl扩展
    $enabled_extension = array("curl");
    $extensions = get_loaded_extensions();
    if($extensions){
        foreach ($enabled_extension as $item) {
            if(!in_array($item, $extensions)){
                throw new OSS_Exception("Extension {".$item."} has been disabled, please check php.ini config");
            }
        }
    }else{
        throw new OSS_Exception(OSS_NO_ANY_EXTENSIONS_LOADED);
    }
}else{
    throw new OSS_Exception('Function get_loaded_extensions has been disabled, please check php config.');
}

// CLASS
/**
 * OSS基础类
 * @author xiaobing
 * @since 2012-05-31
 */
class ALIOSS{
    /**
     * 默认构造函数
     * @param string $access_id (Optional)
     * @param string $access_key (Optional)
     * @param string $hostname (Optional)
     * @param string $security_token ($Optional)
     * @throws OSS_Exception
     * @author  xiaobing
     * @since   2011-11-08
     */
    public function __construct($access_id = NULL, $access_key = NULL, $hostname = NULL, $security_token = NULL){
        if (!$access_id && !defined('OSS_ACCESS_ID')){
            throw new OSS_Exception(NOT_SET_OSS_ACCESS_ID);
        }
        if (!$access_key && !defined('OSS_ACCESS_KEY')){
            throw new OSS_Exception(NOT_SET_OSS_ACCESS_KEY);
        }
        if($access_id && $access_key){
            $this->access_id = $access_id;
            $this->access_key = $access_key;
        }elseif (defined('OSS_ACCESS_ID') && defined('OSS_ACCESS_KEY')){
            $this->access_id = OSS_ACCESS_ID;
            $this->access_key = OSS_ACCESS_KEY;
        }else{
            throw new OSS_Exception(NOT_SET_OSS_ACCESS_ID_AND_ACCESS_KEY);
        }
        if(empty($this->access_id) || empty($this->access_key)){
            throw new OSS_Exception(OSS_ACCESS_ID_OR_ACCESS_KEY_EMPTY);
        }
        if ($hostname) {
            $this->hostname = $hostname;
        }
        else if (!$hostname and defined('OSS_ENDPOINT')) {
            $this->hostname = OSS_ENDPOINT;
        }else{
            $this->hostname = self::DEFAULT_OSS_ENDPOINT;
        }
        
        //支持sts的security token
        $this->security_token = $security_token;
    }
    

    /*%******************************************************************************************************%*/
    //Service Operation
    /**
     * 获取bucket列表
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function list_bucket($options = NULL) {
        $this->precheck_options($options);
        $options[self::OSS_BUCKET] = '';
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $response = $this->auth($options);
        return $response;
    }

    /*%******************************************************************************************************%*/
    //Bucket Operation
    /**
     * 创建bucket
     * @param string $bucket (Required)
     * @param string $acl (Optional)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function create_bucket($bucket, $acl = self::OSS_ACL_TYPE_PRIVATE, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_HEADERS] = array(self::OSS_ACL => $acl);
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 删除bucket
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function delete_bucket($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 获取bucket的acl
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_bucket_acl($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 设置bucket的acl
     * @param string $bucket (Required)
     * @param string $acl  (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function set_bucket_acl($bucket, $acl, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_HEADERS] = array(self::OSS_ACL => $acl);
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 获取bucket logging
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function get_bucket_logging($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'logging';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 设置bucket Logging
     * @param string $bucket (Required)
     * @param string $target_bucket  (Required)
     * @param string $target_prefix  (Optional)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function set_bucket_logging($bucket, $target_bucket, $target_prefix, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $this->precheck_bucket($target_bucket, OSS_TARGET_BUCKET_IS_NOT_ALLOWED_EMPTY);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'logging';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><BucketLoggingStatus></BucketLoggingStatus>');
        $logging_enabled_part=$xml->addChild('LoggingEnabled');
        $logging_enabled_part->addChild('TargetBucket', $target_bucket);
        $logging_enabled_part->addChild('TargetPrefix', $target_prefix);
        $options[self::OSS_CONTENT] = $xml->asXML();        
        return $this->auth($options);
    }

    /**
     * 删除bucket logging
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function delete_bucket_logging($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'logging';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 设置bucket website
     * @param string $bucket (Required)
     * @param string $index_document  (Required)
     * @param string $error_document  (Optional)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function set_bucket_website($bucket, $index_document, $error_document, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        OSSUtil::is_empty($index_document, OSS_INDEX_DOCUMENT_IS_NOT_ALLOWED_EMPTY);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'website';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><WebsiteConfiguration></WebsiteConfiguration>');
        $index_document_part=$xml->addChild('IndexDocument');
        $error_document_part=$xml->addChild('ErrorDocument');
        $index_document_part->addChild('Suffix', $index_document);
        $error_document_part->addChild('Key', $error_document);
        $options[self::OSS_CONTENT] = $xml->asXML();        
        return $this->auth($options);
    }

    /**
     * 获取bucket website
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function get_bucket_website($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 删除bucket website
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function delete_bucket_website($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 设置bucket cors
     * @param string $bucket (Required)
     * @param array $cors_rules  (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function set_bucket_cors($bucket, $cors_rules, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'cors';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><CORSConfiguration></CORSConfiguration>');
        foreach ($cors_rules as $cors_rule){
            $cors_rule_part = $xml->addChild('CORSRule');
            foreach ($cors_rule[self::OSS_CORS_ALLOWED_ORIGIN] as $value){  
                $cors_rule_part->addChild(self::OSS_CORS_ALLOWED_ORIGIN, $value);
            }
            foreach ($cors_rule[self::OSS_CORS_ALLOWED_HEADER] as $value){  
                $cors_rule_part->addChild(self::OSS_CORS_ALLOWED_HEADER, $value);
            }
            foreach ($cors_rule[self::OSS_CORS_ALLOWED_METHOD] as $value){  
                $cors_rule_part->addChild(self::OSS_CORS_ALLOWED_METHOD, $value);
            }
            foreach ($cors_rule[self::OSS_CORS_EXPOSE_HEADER] as $value){   
                $cors_rule_part->addChild(self::OSS_CORS_EXPOSE_HEADER, $value);
            }
            $cors_rule_part->addChild(self::OSS_CORS_MAX_AGE_SECONDS, $cors_rule[self::OSS_CORS_MAX_AGE_SECONDS]);
        }   
        $options[self::OSS_CONTENT] = $xml->asXML();        
        return $this->auth($options);
    }
    /**
     * 获取bucket cors
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function get_bucket_cors($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'cors';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 删除bucket cors
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author lijie.ma@alibaba-inc.com
     * @since 2014-05-04
     * @return ResponseCore
     */
    public function delete_bucket_cors($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'cors';
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 检验跨域资源请求
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param string $origin (Required)
     * @param string $request_method (Required)
     * @param string $request_headers (Required)
     * @param array $options (Optional)
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function options_object($bucket, $object, $origin, $request_method, $request_headers, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_OPTIONS;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_HEADERS] = array(
            self::OSS_OPTIONS_ORIGIN => $origin,
            self::OSS_OPTIONS_REQUEST_HEADERS => $request_headers,
            self::OSS_OPTIONS_REQUEST_METHOD => $request_method
        );
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 设置bucket lifecycle
     * @param string $bucket (Required)
     * @param string $lifecycle_xml (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @since 2015-03-11
     * @return ResponseCore
     */
    public function set_bucket_lifecycle($bucket, $lifecycle_xml, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'lifecycle';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';
        $options[self::OSS_CONTENT] = $lifecycle_xml;       
        return $this->auth($options);
    }

    /**
     * 获取bucket lifecycle
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @since 2015-03-11
     * @return ResponseCore
     */
    public function get_bucket_lifecycle($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'lifecycle';
        return  $this->auth($options);
    }

    /**
     * 删除bucket lifecycle
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @since 2015-03-11
     * @return ResponseCore
     */
    public function delete_bucket_lifecycle($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'lifecycle';
        return $this->auth($options);
    }

    /**
     * 设置bucket referer
     * @param string $bucket (Required)
     * @param bool $is_allow_empty_referer (Required)
     * @param array $referer_list (Optional)
     * @param array $options (Optional)
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function set_bucket_referer($bucket, $is_allow_empty_referer = true, $referer_list = NULL, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'referer';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';

        $xml = new SimpleXMLElement('<RefererConfiguration></RefererConfiguration>');
        $list = $xml->addChild('RefererList');
        if ($referer_list){
            foreach ($referer_list as $value){  
                $list->addChild('Referer', $value);
            }
        }   
        else{
            $list->addChild('Referer', '');
        }
        $value = "true";
        if (!$is_allow_empty_referer){
            $value = "false";
        }
        $xml->addChild('AllowEmptyReferer', $value);
        $options[self::OSS_CONTENT] = $xml->asXML();        
        return $this->auth($options);
    }

    /**
     * 获取bucket referer
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @since 2015-03-11
     * @return ResponseCore
     */
    public function get_bucket_referer($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'referer';
        return  $this->auth($options);
    }

    /*%******************************************************************************************************%*/
    //Object Operation

    /**
     * 获取bucket下的object列表
     * @param string $bucket (Required)
     * @param array $options (Optional)
     * 其中options中的参数如下
     * $options = array(
     *      'max-keys'  => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于100。
     *      'prefix'    => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
     *      'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
     *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回。
     *)
     * 其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function list_object($bucket, $options = NULL){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_HEADERS] = array(
            self::OSS_DELIMITER => isset($options[self::OSS_DELIMITER])?$options[self::OSS_DELIMITER]:'/',
            self::OSS_PREFIX => isset($options[self::OSS_PREFIX])?$options[self::OSS_PREFIX]:'',
            self::OSS_MAX_KEYS => isset($options[self::OSS_MAX_KEYS])?$options[self::OSS_MAX_KEYS]:self::OSS_MAX_KEYS_VALUE,
            self::OSS_MARKER => isset($options[self::OSS_MARKER])?$options[self::OSS_MARKER]:'',
        );
        return $this->auth($options);
    }

    /**
     * 创建目录(目录和文件的区别在于，目录最后增加'/')
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function create_object_dir($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = $object . '/';   //虚拟目录需要以'/结尾'
        $options[self::OSS_CONTENT_LENGTH] = array(self::OSS_CONTENT_LENGTH => 0);
        return $this->auth($options);
    }

    /**
     * 通过在http body中添加内容来上传文件，适合比较小的文件
     * 根据api约定，需要在http header中增加content-length字段
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function upload_file_by_content($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);

        //内容校验
        OSSUtil::validate_content($options);
        $content_type = $this->get_mime_type($object);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = $object;

        if(!isset($options[self::OSS_LENGTH])){
            $options[self::OSS_CONTENT_LENGTH] = strlen($options[self::OSS_CONTENT]);
        }else{
            $options[self::OSS_CONTENT_LENGTH] = $options[self::OSS_LENGTH];
        }

        if(!isset($options[self::OSS_CONTENT_TYPE]) && isset($content_type) && !empty($content_type)){
            $options[self::OSS_CONTENT_TYPE] = $content_type;
        }
        return $this->auth($options);
    }

    /**
     * 上传文件，适合比较大的文件
     * @throws OSS_Exception
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param string $file (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2012-02-28
     * @return ResponseCore
     */
    public function upload_file_by_file($bucket, $object, $file, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        OSSUtil::is_empty($file, OSS_FILE_PATH_IS_NOT_ALLOWED_EMPTY);
        //Windows系统下进行转码
        $file = OSSUtil::encoding_path($file);
        $options[self::OSS_FILE_UPLOAD] = $file;
        if(!file_exists($options[self::OSS_FILE_UPLOAD])){
            throw new OSS_Exception($options[self::OSS_FILE_UPLOAD].OSS_FILE_NOT_EXIST);
        }
        $file_size = filesize($options[self::OSS_FILE_UPLOAD]);
        $is_check_md5 = $this->is_check_md5($options);
        if ($is_check_md5){
            $content_md5 = base64_encode(md5_file($options[self::OSS_FILE_UPLOAD], true));
            $options[self::OSS_CONTENT_MD5] = $content_md5;
        }
        $content_type = $this->get_mime_type($file);
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_CONTENT_TYPE] = $content_type;
        $options[self::OSS_CONTENT_LENGTH] = $file_size;
        $response = $this->auth($options);
        return $response;
    }

    /**
     * 拷贝object
     * @param string $from_bucket (Required)
     * @param string $from_object (Required)
     * @param string $to_bucket (Required)
     * @param string $to_object (Required)
     * @param array $options (Optional)
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function copy_object($from_bucket, $from_object, $to_bucket, $to_object, $options = NULL){
        $this->precheck_common($from_bucket, $from_object, $options);
        $this->precheck_common($to_bucket, $to_object, $options);
        $options[self::OSS_BUCKET] = $to_bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_OBJECT] = $to_object;
        if(isset($options[self::OSS_HEADERS])){
            $options[self::OSS_HEADERS][self::OSS_OBJECT_COPY_SOURCE] = '/'.$from_bucket.'/'.$from_object;
        }
        else {
            $options[self::OSS_HEADERS] = array(self::OSS_OBJECT_COPY_SOURCE => '/'.$from_bucket.'/'.$from_object);
        }

        return $this->auth($options);
    }

    /**
     * 获得object的meta信息
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param string $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_object_meta($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_HEAD;
        $options[self::OSS_OBJECT] = $object;
        return $this->auth($options);
    }

    /**
     * 删除object
     * @param string $bucket(Required)
     * @param string $object (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function delete_object($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_OBJECT] = $object;
        return $this->auth($options);
    }

    /**
     * 批量删除objects
     * @throws OSS_Exception
     * @param string $bucket(Required)
     * @param array $objects (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2012-03-09
     * @return ResponseCore
     */
    public function delete_objects($bucket, $objects, $options = null){
        $this->precheck_common($bucket, NULL, $options, false);
        //objects
        if(!is_array($objects) || !$objects){
            throw new OSS_Exception('The ' . __FUNCTION__ . ' method requires the "objects" option to be set as an array.');
        }

        $options[self::OSS_METHOD] = self::OSS_HTTP_POST;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'delete';
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Delete></Delete>');
        // Quiet mode
        if (isset($options['quiet'])){
            $quiet = 'false';
            if (is_bool($options['quiet'])) { //Boolean
                $quiet = $options['quiet'] ? 'true' : 'false';
            }elseif (is_string($options['quiet'])){ // String
                $quiet = ($options['quiet'] === 'true') ? 'true' : 'false';
            }
            $xml->addChild('Quiet', $quiet);
        }
        // Add the objects
        foreach ($objects as $object){
            $sub_object = $xml->addChild('Object');
            $object = OSSUtil::s_replace($object);        
            $sub_object->addChild('Key', $object);
        }       
        $options[self::OSS_CONTENT] = $xml->asXML();        
        return $this->auth($options);
    }

    /**
     * 获得Object内容
     * @param string $bucket(Required)
     * @param string $object (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_object($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object;
        if(isset($options[self::OSS_LAST_MODIFIED])){
            $options[self::OSS_HEADERS][self::OSS_IF_MODIFIED_SINCE] = $options[self::OSS_LAST_MODIFIED];
            unset($options[self::OSS_LAST_MODIFIED]);
        }
        if(isset($options[self::OSS_ETAG])){
            $options[self::OSS_HEADERS][self::OSS_IF_NONE_MATCH] = $options[self::OSS_ETAG];
            unset($options[self::OSS_ETAG]);
        }
        if(isset($options[self::OSS_RANGE])){
            $range = $options[self::OSS_RANGE];
            $options[self::OSS_HEADERS][self::OSS_RANGE] = "bytes=$range";
            unset($options[self::OSS_RANGE]);
        }

        return $this->auth($options);
    }

    /**
     * 检测Object是否存在
     * @param string $bucket(Required)
     * @param string $object (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function is_object_exist($bucket, $object, $options = NULL){
        return $this->get_object_meta($bucket, $object, $options);
    }

    /**
     * 获取分片大小
     * @param int $part_size (Required)
     * @return int
     */
    private function get_part_size($part_size){
        $part_size = (integer)$part_size;
        if($part_size <= self::OSS_MIN_PART_SIZE){ 
            $part_size = self::OSS_MIN_PART_SIZE;
        }elseif ($part_size > self::OSS_MAX_PART_SIZE){
            $part_size = self::OSS_MAX_PART_SIZE; 
        }else{
            $part_size = self::OSS_MID_PART_SIZE; 
        }       
        return $part_size; 
    }
    
    /*%******************************************************************************************************%*/
    //Multi Part相关操作    
    /**
     * 计算文件可以分成多少个part，以及每个part的长度以及起始位置
     * 方法必须在 <upload_part()>中调用
     *
     * @param integer $file_size (Required) 文件大小
     * @param integer $part_size (Required) part大小,默认5M
     * @return array An array 包含 key-value 键值对. Key 为 `seekTo` 和 `length`.
     */ 
    public function get_multipart_counts($file_size, $part_size = 5242880){
        $i = 0;
        $size_count = $file_size;
        $values = array();
        $part_size = $this->get_part_size($part_size);
        while ($size_count > 0)
        {
            $size_count -= $part_size;
            $values[] = array(
                self::OSS_SEEK_TO => ($part_size * $i),
                self::OSS_LENGTH => (($size_count > 0) ? $part_size : ($size_count + $part_size)),
            );
            $i++;
        }
        return $values;     
    }

    /**
     * 初始化multi-part upload
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     */
    public function initiate_multipart_upload($bucket, $object, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_METHOD] = self::OSS_HTTP_POST;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_SUB_RESOURCE] = 'uploads';
        $options[self::OSS_CONTENT] = '';
        $content_type = $this->get_mime_type($object);
        if(!isset($options[self::OSS_HEADERS])){
            $options[self::OSS_HEADERS] = array();
        }
        $options[self::OSS_HEADERS][self::OSS_CONTENT_TYPE] =  $content_type;

        return $this->auth($options);
    }

    /**
     * 初始化multi-part upload，并且返回uploadId
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param array $options (Optional) Key-Value数组
     * @return string uploadId
     * @throws OSS_Exception
     */
    public function init_multipart_upload($bucket, $object, $options = NULL){
        $res = $this->initiate_multipart_upload($bucket, $object, $options);
        if (!$res->isOK()){
            throw new OSS_Exception('Init multi-part upload failed...');
        }
        $xml = new SimpleXmlIterator($res->body);
        $uploadId = (string)$xml->UploadId;
        return $uploadId;
    }

    /**
     * 上传part
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param string $upload_id (Required) uploadId
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     */
    public function upload_part($bucket, $object, $upload_id, $options = null){
        $this->precheck_common($bucket, $object, $options);
        $this->precheck_param($options, self::OSS_FILE_UPLOAD, __FUNCTION__);
        $this->precheck_param($options, self::OSS_PART_NUM, __FUNCTION__);

        $options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_UPLOAD_ID] = $upload_id; 

        if(isset($options[self::OSS_LENGTH])){
            $options[self::OSS_CONTENT_LENGTH] = $options[self::OSS_LENGTH];
        }
        return $this->auth($options);
    }

    /**
     * 获取已成功上传的part
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param string $upload_id (Required) uploadId
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     */ 
    public function list_parts($bucket, $object, $upload_id, $options = null){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_UPLOAD_ID] = $upload_id;
        $options[self::OSS_QUERY_STRING] = array();
        foreach (array('max-parts', 'part-number-marker') as $param){
            if (isset($options[$param])){
                $options[self::OSS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }   
        return $this->auth($options);
    }

    /**
     * 中止上传mulit-part upload
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param string $upload_id (Required) uploadId
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     */ 
    public function abort_multipart_upload($bucket, $object, $upload_id, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_METHOD] = self::OSS_HTTP_DELETE;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_UPLOAD_ID] = $upload_id;
        return $this->auth($options);
    }

    /**
     * 完成multi-part上传
     * @param string $bucket (Required) Bucket名称
     * @param string $object (Required) Object名称
     * @param string $upload_id (Required) uploadId
     * @param array | xml $parts 可以是一个上传成功part的数组，或者是一个ReponseCore对象
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     */ 
    public function complete_multipart_upload($bucket, $object, $upload_id, $parts, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        $options[self::OSS_METHOD] = self::OSS_HTTP_POST;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_UPLOAD_ID] = $upload_id;
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';

        if(is_string($parts)){
            $options[self::OSS_CONTENT] = $parts;
        }else if($parts instanceof SimpleXMLElement){
            $options[self::OSS_CONTENT] = $parts->asXML();
        }else if((is_array($parts) || $parts instanceof ResponseCore)){
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><CompleteMultipartUpload></CompleteMultipartUpload>');

            if (is_array($parts)){
                foreach ($parts as $node){
                    $part = $xml->addChild('Part');
                    $part->addChild('PartNumber', $node['PartNumber']);
                    $part->addChild('ETag', $node['ETag']);
                }
            }elseif ($parts instanceof ResponseCore){
                foreach ($parts->body->Part as $node){
                    $part = $xml->addChild('Part');
                    $part->addChild('PartNumber', (string) $node->PartNumber);
                    $part->addChild('ETag', (string) $node->ETag);
                }
            }
            $options[self::OSS_CONTENT] = $xml->asXML();            
        }
        return $this->auth($options);       
    }

    /**
     * 列出multipart上传
     * @param string $bucket (Requeired) bucket 
     * @param array $options (Optional) 关联数组
     * @author xiaobing
     * @since 2012-03-05
     * @return ResponseCore
     */
    public function list_multipart_uploads($bucket, $options = null){
        $this->precheck_common($bucket, NULL, $options, false);
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = '/';
        $options[self::OSS_SUB_RESOURCE] = 'uploads';

        foreach (array('delimiter','key-marker', 'max-uploads', 'prefix','upload-id-marker') as $param){
            if (isset($options[$param])){
                $options[self::OSS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        return $this->auth($options);
    }

    /**
     * 从已存在的object拷贝part
     * @param string $from_bucket (Required)
     * @param string $from_object (Required)
     * @param string $to_bucket (Required)
     * @param string $to_object (Required)
     * @param int $part_number (Required)
     * @param string $upload_id (Required)
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function copy_upload_part($from_bucket, $from_object, $to_bucket, $to_object, $part_number, $upload_id, $options = NULL){
    	$this->precheck_common($from_bucket, $from_object, $options);
    	$this->precheck_common($to_bucket, $to_object, $options);

        //如果没有设置$options['isFullCopy']，则需要强制判断copy的起止位置
        $start_range = "0";
        if(isset($options['start'])){
            $start_range = $options['start'];
        }
        $end_range = "";
        if(isset($options['end'])){
            $end_range = $options['end'];
        }
    	$options[self::OSS_METHOD] = self::OSS_HTTP_PUT;
    	$options[self::OSS_BUCKET] = $to_bucket;
    	$options[self::OSS_OBJECT] = $to_object;
    	$options[self::OSS_PART_NUM] = $part_number;
    	$options[self::OSS_UPLOAD_ID] = $upload_id;
    	
    	if(!isset($options[self::OSS_HEADERS])){
    		$options[self::OSS_HEADERS] = array();
    	}

        $options[self::OSS_HEADERS][self::OSS_OBJECT_COPY_SOURCE] = '/' . $from_bucket . '/' . $from_object;
        $options[self::OSS_HEADERS][self::OSS_OBJECT_COPY_SOURCE_RANGE] = "bytes=" . $start_range . "-" . $end_range;
        return $this->auth($options);
    }

    /**
     * multipart上传统一封装，从初始化到完成multipart，以及出错后中止动作
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param array $options (Optional) Key-Value数组
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function create_mpu_object($bucket, $object, $options = null){
        $this->precheck_common($bucket, $object, $options);
        if(isset($options[self::OSS_LENGTH])){
            $options[self::OSS_CONTENT_LENGTH] = $options[self::OSS_LENGTH];
            unset($options[self::OSS_LENGTH]);
        }

        if(isset($options[self::OSS_FILE_UPLOAD])){
            //Windows系统下进行转码
            $options[self::OSS_FILE_UPLOAD] = OSSUtil::encoding_path($options[self::OSS_FILE_UPLOAD]);
        }

        $this->precheck_param($options, self::OSS_FILE_UPLOAD, __FUNCTION__);
        $upload_position = isset($options[self::OSS_SEEK_TO]) ? (integer) $options[self::OSS_SEEK_TO] : 0;

        if (isset($options[self::OSS_CONTENT_LENGTH])){
            $upload_file_size = (integer) $options[self::OSS_CONTENT_LENGTH];
        } else {
            $filename = $options[self::OSS_FILE_UPLOAD];
            $upload_file_size = filesize($filename);
            if ($upload_file_size !== false) {
                $upload_file_size -= $upload_position;
            }
        }

        if ($upload_position === false || !isset($upload_file_size) || $upload_file_size === false || $upload_file_size < 0){
            throw new OSS_Exception('The size of `fileUpload` cannot be determined in ' . __FUNCTION__ . '().');
        }
        // 处理partSize
        if (isset($options[self::OSS_PART_SIZE])){
            $options[self::OSS_PART_SIZE] = $this->get_part_size($options[self::OSS_PART_SIZE]);
        }
        else{
            $options[self::OSS_PART_SIZE] = self::OSS_MID_PART_SIZE;
        }

        $is_check_md5 = $this->is_check_md5($options);
        // 如果上传的文件小于partSize,则直接使用普通方式上传
        if ($upload_file_size < $options[self::OSS_PART_SIZE] && !isset($options[self::OSS_UPLOAD_ID])){
            $local_file = $options[self::OSS_FILE_UPLOAD];
            $options = array(
                self::OSS_CHECK_MD5 => $is_check_md5,
            );          
            return $this->upload_file_by_file($bucket, $object, $local_file, $options);
        }   

        // 初始化multipart
        if (isset($options[self::OSS_UPLOAD_ID])){
            $upload_id = $options[self::OSS_UPLOAD_ID];
        }else{
            //初始化
            $init_options = array();          
            $upload_id = $this->init_multipart_upload($bucket, $object, $init_options);
        }       

        // 获取的分片
        $pieces = $this->get_multipart_counts($upload_file_size, (integer) $options[self::OSS_PART_SIZE]);

        $response_upload_part = array();
        foreach ($pieces as $i => $piece){
            $from_pos = $upload_position + (integer) $piece[self::OSS_SEEK_TO];
            $to_pos = (integer) $piece[self::OSS_LENGTH] + $from_pos - 1;
            $up_options = array(
                self::OSS_FILE_UPLOAD => $options[self::OSS_FILE_UPLOAD],
                self::OSS_PART_NUM => ($i + 1),
                self::OSS_SEEK_TO => $from_pos,
                self::OSS_LENGTH => $to_pos - $from_pos + 1, 
                self::OSS_CHECK_MD5 => $is_check_md5,
            );
            if ($is_check_md5) {
                $content_md5 = OSSUtil::get_content_md5_of_file($options[self::OSS_FILE_UPLOAD], $from_pos, $to_pos);
                $up_options[self::OSS_CONTENT_MD5] = $content_md5;
            }
            $response_upload_part[] = $this->upload_part($bucket, $object, $upload_id, $up_options);
        }

        $upload_parts = array();
        $upload_part_result = true;
        foreach ($response_upload_part as $i => $response){
            $upload_part_result = $upload_part_result && $response->isOk();
            if(!$upload_part_result){
                throw new OSS_Exception('any part upload failed, please try again');
            }
            $upload_parts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => (string) $response->header['etag']
            );      
        }
        return $this->complete_multipart_upload($bucket, $object, $upload_id, $upload_parts);
    }

    /**
     * 通过Multi-Part方式上传整个目录，其中的object默认为文件名
     * @param string $bucket (Required)
     * @param string $dir (Required)
     * @param bool $recursive 是否递归，如果为true，则递归读取所有目录，默认为不递归读取
     * @param string $exclude
     * @param array $options (Optional) Key-Value数组，其中可以包括以下的key
     * @return bool 
     * @throws OSS_Exception
     */
    public function create_mtu_object_by_dir($bucket, $dir, $recursive = false, $exclude = ".|..|.svn", $options = null){
        $this->precheck_common($bucket, NULL, $options, false);
        //Windows系统下进行转码
        $dir = OSSUtil::encoding_path($dir);
        //判断是否目录
        if(!is_dir($dir)){
            throw new OSS_Exception($dir.' is not a directory, please check it');
        }

        $file_list_array = $this->read_dir($dir, $exclude, $recursive);


        if(empty($file_list_array)){
            throw new OSS_Exception($dir.' is empty...');
        }

        $is_upload_ok = true;
        $index = 1;

        foreach ($file_list_array as $k=>$item){
            echo $index++.". ";
            echo "Multiupload file ".$item['path']." ";
            if (is_dir($item['path'])) {
                echo " skipped, because it is directory...\n";
            }
            else {
                $options = array(
                    self::OSS_FILE_UPLOAD => $item['path'],
                    self::OSS_PART_SIZE => self::OSS_MIN_PART_SIZE,
                );          

                $response = $this->create_mpu_object($bucket, $item['file'], $options);
                if($response->isOK()){
                    echo " successful..\n";
                } 
                else {
                    echo " failed..\n";
                    $is_upload_ok = false;
                    continue;
                }
            }
        }
        return $is_upload_ok;
    }

    /**
     * 上传目录
     * @param array $options
     * $options = array(
     *      'bucket'    =>  (Required) string
     *      'object'    =>  (Optional) string
     *      'directory' =>  (Required) string
     *      'exclude'   =>  (Optional) string
     *      'recursive' =>  (Optional) string
     *      'checkmd5'  =>  (Optional) boolean
     * )
     * @return bool
     * @throws OSS_Exception
     */
    public function batch_upload_file($options = NULL){
        if((NULL == $options) || !isset($options['bucket']) || empty($options['bucket']) || !isset($options['directory']) ||empty($options['directory'])){
            throw new OSS_Exception('Bad Request', 400);
        }

        $is_batch_upload_ok = true;
        $bucket = $this->get_value($options, 'bucket');
        $directory = $this->get_value($options, 'directory');
        //Windows系统下进行转码
        $directory = OSSUtil::encoding_path($directory);

        //判断是否目录
        if(!is_dir($directory)){
            throw new OSS_Exception($directory . ' is not a directory, please check it');
        }

        $object = $this->get_value($options, 'object', '');
        $exclude = $this->get_value($options, 'exclude', '.|..|.svn', true);
        $recursive = $this->get_value($options, 'recursive', false, true, true);

        //read directory
        $file_list_array = $this->read_dir($directory, $exclude, $recursive);     

        if(!$file_list_array){
            throw new OSS_Exception($directory.' is empty...');
        }

        $index = 1;
        $is_check_md5 = $this->is_check_md5($options);

        foreach ($file_list_array as $k=>$item){
            echo $index++.". ";
            echo "Upload file ".$item['path']." ";
            if (is_dir($item['path'])) {
                echo " skipped, because it is directory...\n";
            }
            else {
                $options = array(
                    self::OSS_FILE_UPLOAD => $item['path'],
                    self::OSS_PART_SIZE => self::OSS_MIN_PART_SIZE,
                    self::OSS_CHECK_MD5 => $is_check_md5,
                );          
                $real_object = (!empty($object)?$object.'/':'').$item['file'];
                $response = $this->create_mpu_object($bucket, $real_object, $options);
                if($response->isOK()){
                    echo " successful..\n";
                }else{
                    echo " failed..\n";
                    $is_batch_upload_ok = false;
                    continue;
                }
            }
        }
        return $is_batch_upload_ok;
    }

    /*%******************************************************************************************************%*/
    //Object Group相关操作, 不建议再使用，官方API文档中已经无相关介绍
    /**
     * 转化object数组为固定个xml格式
     * @param string $bucket (Required)
     * @param array $object_array (Required)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-12-27
     * @return string
     */
    public function make_object_group_xml($bucket, $object_array){
        $xml = '';
        $xml .= '<CreateFileGroup>';

        if($object_array){
            if(count($object_array) > self::OSS_MAX_OBJECT_GROUP_VALUE){
                throw new OSS_Exception(OSS_OBJECT_GROUP_TOO_MANY_OBJECT, '-401');
            }
            $index = 1;
            foreach ($object_array as $key => $value){
                $object_meta = (array)$this->get_object_meta($bucket, $value);
                if(isset($object_meta) && isset($object_meta['status']) && isset($object_meta['header']) && isset($object_meta['header']['etag']) && $object_meta['status'] == 200){
                    $xml .= '<Part>';
                    $xml .= '<PartNumber>'.intval($index).'</PartNumber>';
                    $xml .= '<PartName>'.$value.'</PartName>';
                    $xml .= '<ETag>'.$object_meta['header']['etag'].'</ETag>';
                    $xml .= '</Part>';

                    $index++;
                }
            }
        }else{
            throw new OSS_Exception(OSS_OBJECT_ARRAY_IS_EMPTY, '-400');
        }
        $xml .= '</CreateFileGroup>';
        return $xml;
    }
    /**
     * 创建Object Group, 不建议再使用，官方API文档中已经无相关介绍
     * @param string $object_group (Required)  Object Group名称
     * @param string $bucket (Required) Bucket名称
     * @param array $object_arry (Required) object数组，所有的object必须在同一个bucket下
     * 其中$object_arrya 格式如下:
     * $object = array(
     *      $object1,
     *      $object2,
     *      ...
     * )
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function create_object_group($bucket, $object_group, $object_arry, $options = NULL){
        $this->precheck_common($bucket, $object_group, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_POST;
        $options[self::OSS_OBJECT] = $object_group;
        $options[self::OSS_CONTENT_TYPE] = $this->get_mime_type($object_group);  //重设Content-Type
        $options[self::OSS_SUB_RESOURCE] = 'group';    //设置?group
        $options[self::OSS_CONTENT] = $this->make_object_group_xml($bucket, $object_arry);   //格式化xml
        return $this->auth($options );
    }

    /**
     * 获取Object Group, 不建议再使用，官方API文档中已经无相关介绍
     * @param string $object_group (Required)
     * @param string $bucket    (Required)
     * @param array $options    (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_object_group($bucket, $object_group, $options = NULL){
        $this->precheck_common($bucket, $object_group, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object_group;
        $options[self::OSS_HEADERS] = array(self::OSS_OBJECT_GROUP => self::OSS_OBJECT_GROUP);  //header中的x-oss-file-group不能为空，否则返回值错误
        return $this->auth($options);
    }

    /**
     * 获取Object Group 的Object List信息, 不建议再使用，官方API文档中已经无相关介绍
     * @param string $object_group (Required)
     * @param string $bucket    (Required)
     * @param array $options    (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_object_group_index($bucket, $object_group, $options = NULL){
        $this->precheck_common($bucket, $object_group, $options);
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_METHOD] = self::OSS_HTTP_GET;
        $options[self::OSS_OBJECT] = $object_group;
        $options[self::OSS_CONTENT_TYPE] = 'application/xml';  //重设Content-Type
        $options[self::OSS_HEADERS] = array(self::OSS_OBJECT_GROUP => self::OSS_OBJECT_GROUP);
        return $this->auth($options);
    }

    /**
     * 获得object group的meta信息, 不建议再使用，官方API文档中已经无相关介绍
     * @param string $bucket (Required)
     * @param string $object_group (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function get_object_group_meta($bucket, $object_group, $options = NULL){
        return $this->get_object_meta($bucket, $object_group, $options);
    }

    /**
     * 删除Object Group, 不建议再使用，官方API文档中已经无相关介绍
     * @param string $bucket(Required)
     * @param string $object_group (Required)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-11-14
     * @return ResponseCore
     */
    public function delete_object_group($bucket, $object_group, $options = NULL){
        return $this->delete_object($bucket, $object_group, $options);
    }

    /*%******************************************************************************************************%*/
    //带签名的url相关

    /**
     * 获取GET签名的url
     * @param string $bucket (Required)
     * @param string $object (Required)
     * @param int	 $timeout (Optional)
     * @param array $options (Optional)
     * @author xiaobing
     * @since 2011-12-21
     * @return string
     */
    public function get_sign_url($bucket, $object, $timeout = 60, $options = NULL){
        return $this->presign_url($bucket, $object, $timeout, self::OSS_HTTP_GET, $options);
    }

    /**
     * 获取签名url,支持生成get和put签名
     * @param string $bucket
     * @param string $object
     * @param int $timeout
     * @param array $options (Optional) Key-Value数组
     * @param string $method
     * @return ResponseCore
     * @throws OSS_Exception
     */
    public function presign_url($bucket, $object, $timeout = 60, $method = self::OSS_HTTP_GET, $options = NULL){
        $this->precheck_common($bucket, $object, $options);
        //method
        if (self::OSS_HTTP_GET !== $method && self::OSS_HTTP_PUT !== $method) {
            throw new OSS_Exception(OSS_INVALID_METHOD);
        }
        $options[self::OSS_BUCKET] = $bucket;
        $options[self::OSS_OBJECT] = $object;
        $options[self::OSS_METHOD] = $method;
        if (!isset($options[self::OSS_CONTENT_TYPE])) {
            $options[self::OSS_CONTENT_TYPE] = '';
        }
        $timeout = time() + $timeout;
        $options[self::OSS_PREAUTH] = $timeout;
        $options[self::OSS_DATE] = $timeout;
        $this->set_sign_sts_in_url(true);
        return $this->auth($options);
    }

    /**
     * 检测options参数
     * @param array $options
     * @throws OSS_Exception
     */
    private function precheck_options(&$options) {
        OSSUtil::validate_options($options);
        if (!$options) {
            $options = array();
        }
    }

    /**
     * 校验bucket参数
     * @param string $bucket
     * @param string $err_msg
     * @throws OSS_Exception
     */
    private function precheck_bucket($bucket, $err_msg = OSS_BUCKET_IS_NOT_ALLOWED_EMPTY) {
        OSSUtil::is_empty($bucket, $err_msg);
    }

    /**
     * 校验object参数
     * @param string $object
     * @throws OSS_Exception
     */
    private function precheck_object($object) {
        OSSUtil::is_empty($object, OSS_OBJECT_IS_NOT_ALLOWED_EMPTY);
    }

    /**
     * 校验bucket,options参数
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $is_check_object
     */
    private function precheck_common($bucket, $object, &$options, $is_check_object = true) {
        if ($is_check_object){
            $this->precheck_object($object);
        }
        $this->precheck_options($options);
        $this->precheck_bucket($bucket);
    }
    
    /**
     * 参数校验
     * @param array $options
     * @param string $param
     * @param string $func_name
     * @throws OSS_Exception
     */
    private function precheck_param($options, $param, $func_name){
        if (!isset($options[$param])){
            throw new OSS_Exception('The `' . $param . '` options is required in ' . $func_name . '().');
        }
    }

    /**
     * 检测md5
     * @param array $options
     * @return bool|null
     */
    private function is_check_md5($options){
        return $this->get_value($options, self::OSS_CHECK_MD5, false, true, true);
    }

    /**
     * 获取value
     * @param array $options
     * @param string $key
     * @param string $default
     * @param bool $is_check_empty
     * @param bool $is_check_bool
     * @return bool|null
     */
    private function get_value($options, $key, $default = NULL, $is_check_empty = false, $is_check_bool = false) {
        $value = $default;
        if (isset($options[$key])){
            if ($is_check_empty){
                if (!empty($options[$key])){
                    $value= $options[$key];
                }
            }
            else{
                $value= $options[$key];
            }
            unset($options[$key]);
        }
        if ($is_check_bool){
            if ($value !== true && $value !== false){
                $value = false;
            }
        }
        return $value;
    }

    /**
     * 获取mimetype类型
     * @param string $object
     * @return string
     */
    private function get_mime_type($object) {
        $extension = explode('.', $object);
        $extension = array_pop($extension);
        $mime_type = MimeTypes::get_mimetype(strtolower($extension));
        return $mime_type;
    }

    /**
     * 读取目录
     * @param $dir
     * @param string $exclude
     * @param bool $recursive
     * @return array
     */
    private function read_dir($dir, $exclude = ".|..|.svn", $recursive = false){
        $file_list_array = array(); 
        $base_path=$dir; 
        $exclude_array = explode("|", $exclude); 
        // filter out "." and ".."
        $exclude_array = array_unique(array_merge($exclude_array,array('.','..'))); 

        if($recursive){
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $new_file)
            {
                if ($new_file->isDir()) continue;
                    echo "$new_file\n";
                    $object = str_replace($base_path, '', $new_file);
                    if(!in_array(strtolower($object), $exclude_array)){ 
                        $object = ltrim($object, '/');
                        if (is_file($new_file)){ 
                            $key = md5($new_file.$object, false);
                            $file_list_array[$key] = array('path' => $new_file,'file' => $object,); 
                        } 
                    }
            }
        }
        else if($handle = opendir($dir)){ 
            while ( false !== ($file = readdir($handle))){                 
                if(!in_array(strtolower($file), $exclude_array)){ 
                    $new_file = $dir.'/'.$file;                 

                    $object = $file;
                    $object = ltrim($object, '/');
                    if (is_file($new_file)){ 
                        $key = md5($new_file.$object, false);
                        $file_list_array[$key] = array('path' => $new_file,'file' => $object,); 
                    } 
                } 
            } 
            closedir($handle);        
        }         
        return $file_list_array; 
    } 


    /*%******************************************************************************************************%*/
    //请求

    /**
     * auth接口
     * @param array $options
     * @return ResponseCore
     * @throws OSS_Exception
     * @throws RequestCore_Exception
     */
    public function auth($options){
        OSSUtil::validate_options($options);

        //验证Bucket,list_bucket时不需要验证
        if(!( ('/' == $options[self::OSS_OBJECT]) && ('' == $options[self::OSS_BUCKET]) && ('GET' == $options[self::OSS_METHOD])) && !OSSUtil::validate_bucket($options[self::OSS_BUCKET])){
            throw new OSS_Exception('"'.$options[self::OSS_BUCKET].'"'.OSS_BUCKET_NAME_INVALID);
        }
        //验证Object
        if(isset($options[self::OSS_OBJECT]) && !OSSUtil::validate_object($options[self::OSS_OBJECT])){
            throw  new OSS_Exception($options[self::OSS_OBJECT].OSS_OBJECT_NAME_INVALID);
        }
        //Object编码为UTF-8
        $tmp_object = $options[self::OSS_OBJECT];
        try {
            if(OSSUtil::is_gb2312($options[self::OSS_OBJECT])){
                $options[self::OSS_OBJECT] = iconv('GB2312', "UTF-8//IGNORE",$options[self::OSS_OBJECT]);
            }elseif(OSSUtil::check_char($options[self::OSS_OBJECT],true)){
                $options[self::OSS_OBJECT] = iconv('GBK', "UTF-8//IGNORE",$options[self::OSS_OBJECT]);
            }   
        } catch (Exception $e) {
            try{
                $tmp_object = iconv(mb_detect_encoding($tmp_object), "UTF-8", $tmp_object);
            } 
            catch(Exception $e) {
            }
        }
        $options[self::OSS_OBJECT] = $tmp_object;

        //验证ACL
        if(isset($options[self::OSS_HEADERS][self::OSS_ACL]) && !empty($options[self::OSS_HEADERS][self::OSS_ACL])){
            if(!in_array(strtolower($options[self::OSS_HEADERS][self::OSS_ACL]), self::$OSS_ACL_TYPES)){
                throw new OSS_Exception($options[self::OSS_HEADERS][self::OSS_ACL].':'.OSS_ACL_INVALID);
            }
        }

        //定义scheme
        $scheme = $this->use_ssl ? 'https://' : 'http://';
        if($this->enable_domain_style){
            $hostname = $this->vhost ? $this->vhost : (($options[self::OSS_BUCKET] == '') ? $this->hostname : ($options[self::OSS_BUCKET].'.').$this->hostname);
        }else{
            $hostname = (isset($options[self::OSS_BUCKET]) && '' !== $options[self::OSS_BUCKET]) ? $this->hostname.'/'.$options[self::OSS_BUCKET] : $this->hostname;
        }

        //请求参数
        $signable_resource = '';
        $query_string_params = array();
        $signable_query_string_params = array();
        $string_to_sign = '';       

        $oss_host = $this->hostname;
        if ($this->enable_domain_style){
            $oss_host = $hostname;
        }
        $headers = array (
            self::OSS_CONTENT_MD5 => '',
            self::OSS_CONTENT_TYPE => isset($options[self::OSS_CONTENT_TYPE]) ? $options[self::OSS_CONTENT_TYPE] : 'application/x-www-form-urlencoded',
            self::OSS_DATE => isset($options[self::OSS_DATE]) ? $options[self::OSS_DATE] : gmdate('D, d M Y H:i:s \G\M\T'),
            self::OSS_HOST => $oss_host,
        );

        if (isset($options[self::OSS_CONTENT_MD5])){
            $headers[self::OSS_CONTENT_MD5] = $options[self::OSS_CONTENT_MD5];
        }
        
        //增加stsSecurityToken
        if((!is_null($this->security_token)) && (!$this->enable_sts_in_url)){
            $headers[self::OSS_SECURITY_TOKEN] = $this->security_token;
        }

        if (isset($options[self::OSS_OBJECT]) && '/' !== $options[self::OSS_OBJECT]){
            $signable_resource = '/'.str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::OSS_OBJECT]));
        }

        if(isset($options[self::OSS_QUERY_STRING])){
            $query_string_params = array_merge($query_string_params, $options[self::OSS_QUERY_STRING]);
        }
        $query_string = OSSUtil::to_query_string($query_string_params);

        $signable_list = array(
            self::OSS_PART_NUM,
            'response-content-type', 
            'response-content-language',
            'response-cache-control', 
            'response-content-encoding',
            'response-expires',
            'response-content-disposition',
            self::OSS_UPLOAD_ID,         
        );

        foreach ($signable_list as $item){
            if(isset($options[$item])){
                $signable_query_string_params[$item] = $options[$item]; 
            }
        }

        if ($this->enable_sts_in_url && (!is_null($this->security_token))) {
            $signable_query_string_params["security-token"] = $this->security_token;
        }
        $signable_query_string = OSSUtil::to_query_string($signable_query_string_params);

        //合并 HTTP headers
        if (isset($options[self::OSS_HEADERS])) {
            $headers = array_merge($headers, $options[self::OSS_HEADERS]);
        }

        //生成请求URL
        $conjunction = '?';

        $non_signable_resource = '';

        if (isset($options[self::OSS_SUB_RESOURCE])){
            $signable_resource .= $conjunction . $options[self::OSS_SUB_RESOURCE];
            $conjunction = '&';
        }   

        if($signable_query_string !== ''){
            $signable_query_string = $conjunction . $signable_query_string;
            $conjunction = '&';
        }

        if($query_string !== ''){
            $non_signable_resource .= $conjunction . $query_string;
            $conjunction = '&';         
        }

        $this->request_url =  $scheme . $hostname . $signable_resource . $signable_query_string . $non_signable_resource;

        //创建请求
        $request = new RequestCore($this->request_url);
        $user_agent = OSS_NAME."/".OSS_VERSION." (".php_uname('s')."/".php_uname('r')."/".php_uname('m').";".PHP_VERSION.")";
        $request->set_useragent($user_agent);

        // Streaming uploads
        if (isset($options[self::OSS_FILE_UPLOAD])){
            if (is_resource($options[self::OSS_FILE_UPLOAD])){
                $length = null; 

                if (isset($options[self::OSS_CONTENT_LENGTH])){
                    $length = $options[self::OSS_CONTENT_LENGTH];
                }elseif (isset($options[self::OSS_SEEK_TO])){
                    $stats = fstat($options[self::OSS_FILE_UPLOAD]);
                    if ($stats && $stats[self::OSS_SIZE] >= 0){
                        $length = $stats[self::OSS_SIZE] - (integer) $options[self::OSS_SEEK_TO];
                    }
                }

                $request->set_read_stream($options[self::OSS_FILE_UPLOAD], $length);

                if ($headers[self::OSS_CONTENT_TYPE] === 'application/x-www-form-urlencoded'){
                    $headers[self::OSS_CONTENT_TYPE] = 'application/octet-stream';
                }
            }else{
                $request->set_read_file($options[self::OSS_FILE_UPLOAD]);
                $length = $request->read_stream_size; 
                if (isset($options[self::OSS_CONTENT_LENGTH])){
                    $length = $options[self::OSS_CONTENT_LENGTH];
                }elseif (isset($options[self::OSS_SEEK_TO]) && isset($length)){
                    $length -= (integer) $options[self::OSS_SEEK_TO];
                }
                $request->set_read_stream_size($length);
                if (isset($headers[self::OSS_CONTENT_TYPE]) && ($headers[self::OSS_CONTENT_TYPE] === 'application/x-www-form-urlencoded')){
                    $mime_type = self::get_mime_type($options[self::OSS_FILE_UPLOAD]);
                    $headers[self::OSS_CONTENT_TYPE] = $mime_type;
                }
            }
        }

        if (isset($options[self::OSS_SEEK_TO])){
            $request->set_seek_position((integer)$options[self::OSS_SEEK_TO]);
        }   

        if (isset($options[self::OSS_FILE_DOWNLOAD])){
            if (is_resource($options[self::OSS_FILE_DOWNLOAD])){
                $request->set_write_stream($options[self::OSS_FILE_DOWNLOAD]);
            }else{
                $request->set_write_file($options[self::OSS_FILE_DOWNLOAD]);
            }
        }       

        if(isset($options[self::OSS_METHOD])){
            $request->set_method($options[self::OSS_METHOD]);
            $string_to_sign .= $options[self::OSS_METHOD] . "\n";           
        }

        if (isset($options[self::OSS_CONTENT])) {
            $request->set_body($options[self::OSS_CONTENT]);
            if ($headers[self::OSS_CONTENT_TYPE] === 'application/x-www-form-urlencoded'){
                $headers[self::OSS_CONTENT_TYPE] = 'application/octet-stream';
            }           

            $headers[self::OSS_CONTENT_LENGTH] = strlen($options[self::OSS_CONTENT]);
            $headers[self::OSS_CONTENT_MD5] = base64_encode(md5($options[self::OSS_CONTENT], true));
        }

        uksort($headers, 'strnatcasecmp');

        foreach($headers as $header_key => $header_value){
            $header_value = str_replace(array ("\r", "\n"), '', $header_value);
            if ($header_value !== '') {
                $request->add_header($header_key, $header_value);
            }

            if (
                strtolower($header_key) === 'content-md5' ||
                strtolower($header_key) === 'content-type' ||
                strtolower($header_key) === 'date' ||
                (isset($options['self::OSS_PREAUTH']) && (integer) $options['self::OSS_PREAUTH'] > 0)
            ){
                $string_to_sign .= $header_value . "\n";
            }elseif (substr(strtolower($header_key), 0, 6) === self::OSS_DEFAULT_PREFIX){
                $string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
            }           
        }

        $string_to_sign .= '/' . $options[self::OSS_BUCKET];
        $string_to_sign .=  $this->enable_domain_style ? ($options[self::OSS_BUCKET] != '' ? ($options[self::OSS_OBJECT]=='/'?'/':'') :''): '';
        $string_to_sign .= rawurldecode($signable_resource) . urldecode($signable_query_string);

        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->access_key, true));
        $request->add_header('Authorization', 'OSS ' . $this->access_id . ':' . $signature);

        if (isset($options[self::OSS_PREAUTH]) && (integer) $options[self::OSS_PREAUTH] > 0){
            $signed_url = $this->request_url . $conjunction . self::OSS_URL_ACCESS_KEY_ID.'=' . rawurlencode($this->access_id) . '&'.self::OSS_URL_EXPIRES.'=' . $options[self::OSS_PREAUTH] . '&'.self::OSS_URL_SIGNATURE.'=' . rawurlencode($signature);
            return $signed_url;
        }elseif (isset($options[self::OSS_PREAUTH])){
            return $this->request_url;
        }       

        if ($this->debug_mode){
            $request->debug_mode = $this->debug_mode;
        }

        $request->send_request();

        $response_header = $request->get_response_header();
        $response_header['oss-request-url'] = $this->request_url;
        $response_header['oss-redirects'] = $this->redirects;
        $response_header['oss-stringtosign'] = $string_to_sign;
        $response_header['oss-requestheaders'] = $request->request_headers;

        $data =  new ResponseCore($response_header , $request->get_response_body(), $request->get_response_code());

        //retry if OSS Internal Error
        if((integer)$request->get_response_code() === 500){  
            if($this->redirects <= $this->max_retries){
                //设置休眠
                $delay = (integer) (pow(4, $this->redirects) * 100000);
                usleep($delay);
                $this->redirects++;
                $data = $this->auth($options);
            }
        }

        $this->redirects = 0;   
        return $data;
    }

    /*%******************************************************************************************************%*/
    //属性

    /**
     * 设置debug模式
     * @param boolean $debug_mode (Optional)
     * @author xiaobing
     * @since 2012-05-29
     * @return void
     */
    public function set_debug_mode($debug_mode = false){
        $this->debug_mode = $debug_mode;
    }
    public function get_debug_mode(){
        return $this->debug_mode;
    }

    /**
     * 设置最大尝试次数
     * @param int $max_retries
     * @author xiaobing
     * @since 2012-05-29
     * @return void
     */
    public function set_max_retries($max_retries = 3){
        $this->max_retries = $max_retries;
    }

    /**
     * 获取最大尝试次数
     * @author xiaobing
     * @since 2012-05-29
     * @return int
     */
    public function get_max_retries(){
        return $this->max_retries;
    }

    /**
     * 设置host地址
     * @author xiaobing
     * @param string $hostname host name
     * @param int   $port int
     * @since 2012-06-11
     * @return void
     */
    public function set_host_name($hostname, $port = null){
        $this->hostname = $hostname;

        if($port){
            $this->port = $port;
            $this->hostname .= ':'.$port;
        }
    }

    public function get_host(){
        return $this->hostname;
    }

    public function get_port(){
        return $this->port;
    }

    public function get_id(){
        return $this->access_id;
    }

    /**
     * 设置vhost地址
     * @author xiaobing
     * @param string $vhost vhost
     * @since 2012-06-11
     * @return void
     */
    public function set_vhost($vhost){
        $this->vhost = $vhost;
    }

    public function get_vhost(){
        return $this->vhost;
    }
    /**
     * 设置路径形式，如果为true,则启用三级域名，如bucket.oss.aliyuncs.com
     * @author xiaobing
     * @param boolean $enable_domain_style 
     * @since 2012-06-11
     * @return void
     */
    public function set_enable_domain_style($enable_domain_style = true){
        $this->enable_domain_style = $enable_domain_style;
    }

    public function get_enable_domain_style(){
        return $this->enable_domain_style;
    }

    public function set_sign_sts_in_url($enable){
        if ($enable) {
            $this->enable_sts_in_url = true;
        } else {
            $this->enable_sts_in_url = false;
        }
    }

    /*%******************************************************************************************************%*/
    const DEFAULT_OSS_HOST = 'oss.aliyuncs.com';
    const DEFAULT_OSS_ENDPOINT = 'oss.aliyuncs.com';
    const NAME = OSS_NAME;
    const BUILD = OSS_BUILD;
    const VERSION = OSS_VERSION;
    const AUTHOR = OSS_AUTHOR;
    //OSS 内部常量
    const OSS_BUCKET = 'bucket';
    const OSS_OBJECT = 'object';
    const OSS_HEADERS = OSSUtil::OSS_HEADERS;
    const OSS_METHOD = 'method';
    const OSS_QUERY = 'query';
    const OSS_BASENAME = 'basename';
    const OSS_MAX_KEYS = 'max-keys';
    const OSS_UPLOAD_ID = 'uploadId';
    const OSS_PART_NUM = 'partNumber';
    const OSS_MAX_KEYS_VALUE = 100;
    const OSS_MAX_OBJECT_GROUP_VALUE = OSSUtil::OSS_MAX_OBJECT_GROUP_VALUE;
    const OSS_MAX_PART_SIZE = OSSUtil::OSS_MAX_PART_SIZE;
    const OSS_MID_PART_SIZE = OSSUtil::OSS_MID_PART_SIZE;
    const OSS_MIN_PART_SIZE = OSSUtil::OSS_MIN_PART_SIZE;
    const OSS_FILE_SLICE_SIZE = 8192;
    const OSS_PREFIX = 'prefix';
    const OSS_DELIMITER = 'delimiter';
    const OSS_MARKER = 'marker';
    const OSS_CONTENT_MD5 = 'Content-Md5';
    const OSS_SELF_CONTENT_MD5 = 'x-oss-meta-md5';
    const OSS_CONTENT_TYPE = 'Content-Type';
    const OSS_CONTENT_LENGTH = 'Content-Length';
    const OSS_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const OSS_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    const OSS_IF_MATCH = 'If-Match';
    const OSS_IF_NONE_MATCH = 'If-None-Match';
    const OSS_CACHE_CONTROL = 'Cache-Control';
    const OSS_EXPIRES = 'Expires';
    const OSS_PREAUTH = 'preauth';
    const OSS_CONTENT_COING = 'Content-Coding';
    const OSS_CONTENT_DISPOSTION = 'Content-Disposition';
    const OSS_RANGE = 'range';
    const OSS_ETAG = 'etag';
    const OSS_LAST_MODIFIED = 'lastmodified';
    const OS_CONTENT_RANGE = 'Content-Range';
    const OSS_CONTENT = OSSUtil::OSS_CONTENT;
    const OSS_BODY = 'body';
    const OSS_LENGTH = OSSUtil::OSS_LENGTH;
    const OSS_HOST = 'Host';
    const OSS_DATE = 'Date';
    const OSS_AUTHORIZATION = 'Authorization';
    const OSS_FILE_DOWNLOAD = 'fileDownload';
    const OSS_FILE_UPLOAD = 'fileUpload';
    const OSS_PART_SIZE = 'partSize';
    const OSS_SEEK_TO = 'seekTo';
    const OSS_SIZE = 'size';
    const OSS_QUERY_STRING = 'query_string';
    const OSS_SUB_RESOURCE = 'sub_resource';
    const OSS_DEFAULT_PREFIX = 'x-oss-';
    const OSS_CHECK_MD5 = 'checkmd5';
    /*%******************************************************************************************%*/
    //私有URL变量
    const OSS_URL_ACCESS_KEY_ID = 'OSSAccessKeyId';
    const OSS_URL_EXPIRES = 'Expires';
    const OSS_URL_SIGNATURE = 'Signature';
    /*%******************************************************************************************%*/
    //HTTP方法
    const OSS_HTTP_GET = 'GET';
    const OSS_HTTP_PUT = 'PUT';
    const OSS_HTTP_HEAD = 'HEAD';
    const OSS_HTTP_POST = 'POST';
    const OSS_HTTP_DELETE = 'DELETE';
    const OSS_HTTP_OPTIONS = 'OPTIONS';
    /*%******************************************************************************************%*/
    //其他常量
    const OSS_ACL = 'x-oss-acl';
    const OSS_OBJECT_GROUP = 'x-oss-file-group';
    const OSS_MULTI_PART = 'uploads';
    const OSS_MULTI_DELETE = 'delete';
    const OSS_OBJECT_COPY_SOURCE = 'x-oss-copy-source';
    const OSS_OBJECT_COPY_SOURCE_RANGE = "x-oss-copy-source-range";
    //支持STS SecurityToken
    const OSS_SECURITY_TOKEN = "x-oss-security-token";
    
    const OSS_ACL_TYPE_PRIVATE = 'private';
    const OSS_ACL_TYPE_PUBLIC_READ = 'public-read';
    const OSS_ACL_TYPE_PUBLIC_READ_WRITE = 'public-read-write';
    //OSS ACL数组
    static $OSS_ACL_TYPES = array(
        self::OSS_ACL_TYPE_PRIVATE,
        self::OSS_ACL_TYPE_PUBLIC_READ,
        self::OSS_ACL_TYPE_PUBLIC_READ_WRITE
    );

    //CORS 相关
    const OSS_CORS_ALLOWED_ORIGIN='AllowedOrigin';
    const OSS_CORS_ALLOWED_METHOD='AllowedMethod';
    const OSS_CORS_ALLOWED_HEADER='AllowedHeader';
    const OSS_CORS_EXPOSE_HEADER='ExposeHeader';
    const OSS_CORS_MAX_AGE_SECONDS='MaxAgeSeconds';
    const OSS_OPTIONS_ORIGIN = 'Origin';
    const OSS_OPTIONS_REQUEST_METHOD = 'Access-Control-Request-Method';
    const OSS_OPTIONS_REQUEST_HEADERS = 'Access-Control-Request-Headers';


    /*%******************************************************************************************%*/
    //是否使用SSL
    public $version = OSS_VERSION;
    protected $use_ssl = false;
    //是否使用debug模式
    private $debug_mode = false;
    private $max_retries = 3;
    private $redirects = 0;
    private $vhost;
    //路径表现方式
    private $enable_domain_style = false;
    private $request_url;
    private $access_id;
    private $access_key;
    private $hostname;
    private $port;
    private $security_token;
    private $enable_sts_in_url = false;
}
