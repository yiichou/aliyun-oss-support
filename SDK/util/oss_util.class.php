<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'thirdparty'.DIRECTORY_SEPARATOR.'xml2array.class.php';

// EXCEPTIONS
/**
 * OSS异常类，继承自基类
 */
class OSS_Exception extends Exception {}
class body
{
    public $Part;
}
class Part
{
    public $PartNumber;
    public $ETag;
}
class OSSUtil
{
    const OSS_CONTENT = 'content';
    const OSS_LENGTH = 'length';
    const OSS_HEADERS = 'headers';
    const OSS_MAX_OBJECT_GROUP_VALUE = 1000;
    const OSS_MAX_PART_SIZE = 524288000;
    const OSS_MID_PART_SIZE = 52428800;
    const OSS_MIN_PART_SIZE = 5242880;

    //oss默认响应头
    static $OSS_DEFAULT_REAPONSE_HEADERS = array(
        'date','content-type','content-length','connection','accept-ranges','cache-control','content-disposition','content-encoding','content-language',
        'etag','expires','last-modified','server'
    );

    public static function get_object_list_marker_from_xml($xml, &$marker) 
    {
        $xml = new SimpleXMLElement($xml); 
        $is_truncated = $xml->IsTruncated;  
        $object_list = array();
        $marker = $xml->NextMarker;
        foreach ( $xml->Contents as $content) {  
            array_push($object_list, $content->Key);
        }  
        return $object_list;
    }

    public static function print_res($response, $msg = "", $is_simple_print = true){
        if ($is_simple_print){
            if ((int)($response->status / 100) == 2){
                echo $msg." OK\n";
            }
            else{
                echo "ret:".$response->status."\n";
                echo $msg." FAIL\n";
            }
        }
        else {
            echo '|-----------------------Start---------------------------------------------------------------------------------------------------'."\n";
            echo '|-Status:' . $response->status . "\n";
            echo '|-Body:' ."\n"; 
            $body = $response->body . "\n";
            echo $body . "\n";
            echo "|-Header:\n";
            print_r ($response->header);
            echo '-----------------------End-----------------------------------------------------------------------------------------------------'."\n\n";
        }
    }

    /*%******************************************************************************************************%*/
    //工具类相关

    /**
     * 生成query params
     * @param array $array 关联数组
     * @return string 返回诸如 key1=value1&key2=value2
     */
    public static function to_query_string($options = array()){
        $temp = array();
        uksort($options, 'strnatcasecmp');
        foreach ($options as $key => $value){
            if (is_string($key) && !is_array($value)){
                $temp[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return implode('&', $temp);
    }

    /**
     * @param $str
     * @return string
     */
    public static function hex_to_base64($str){
        $result = '';
        for ($i = 0; $i < strlen($str); $i += 2){
            $result .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($result);
    }

    public static function s_replace($subject){
        $search = array('<', '>', '&', '\'', '"');
        $replace = array('&lt;', '&gt;', '&amp;', '&apos;', '&quot;');
        return str_replace($search, $replace, $subject);
    }

    /**
     * @param $subject
     * @return mixed
     */
    public static function replace_invalid_xml_char($subject){
        $search = array(
            '&#01;', '&#02;', '&#03;', '&#04;', '&#05;', '&#06;', '&#07;', '&#08;', '&#09;', '&#10;', '&#11;', '&#12;', '&#13;',
            '&#14;', '&#15;', '&#16;', '&#17;', '&#18;', '&#19;', '&#20;', '&#21;', '&#22;', '&#23;', '&#24;', '&#25;', '&#26;',
            '&#27;', '&#28;', '&#29;', '&#30;', '&#31;', '&#127;'
        );
        $replace = array(
            '%01', '%02', '%03', '%04', '%05', '%06', '%07', '%08', '%09', '%0A', '%0B', '%0C', '%0D',
            '%0E', '%0F', '%10', '%11', '%12', '%13', '%14', '%15', '%16', '%17', '%18', '%19', '%1A',
            '%1B', '%1C', '%1D', '%1E', '%1F', '%7F'
        );

        return str_replace($search, $replace, $subject);
    }

    /**
     * @param $str
     * @return int
     */
    public static function chk_chinese($str){
        return preg_match('/[\x80-\xff]./', $str);
    }

    /**
     * 检测是否GB2312编码
     * @param string $str 
     * @author xiaobing
     * @since 2012-03-20
     * @return boolean false UTF-8编码  TRUE GB2312编码
     */
    public static function is_gb2312($str)  {  
        for($i=0; $i<strlen($str); $i++) {  
            $v = ord( $str[$i]);  
            if( $v > 127) {  
                if( ($v >= 228) && ($v <= 233)){  
                    if( ($i+2) >= (strlen($str) - 1)) return true;  // not enough characters  
                    $v1 = ord( $str[$i+1]);  
                    $v2 = ord( $str[$i+2]);  
                    if( ($v1 >= 128) && ($v1 <=191) && ($v2 >=128) && ($v2 <= 191)) 
                        return false;   //UTF-8编码  
                    else  
                        return true;    //GB编码  
                }  
            }  
        }  
    } 

    /**
     * 检测是否GBK编码
     * @param string $str 
     * @param boolean $gbk
     * @author xiaobing
     * @since 2012-06-04
     * @return boolean 
     */ 
    public static function check_char($str, $gbk = true){ 
        for($i=0; $i<strlen($str); $i++) {
            $v = ord( $str[$i]);
            if( $v > 127){
                if( ($v >= 228) && ($v <= 233)){
                    if(($i+2)>= (strlen($str)-1)) return $gbk?true:FALSE;  // not enough characters
                    $v1 = ord( $str[$i+1]); $v2 = ord( $str[$i+2]);
                    if($gbk){
                        return (($v1 >= 128) && ($v1 <=191) && ($v2 >=128) && ($v2 <= 191))?FALSE:TRUE;//GBK
                    }else{
                        return (($v1 >= 128) && ($v1 <=191) && ($v2 >=128) && ($v2 <= 191))?TRUE:FALSE;
                    }
                }
            }
        }
        return $gbk?TRUE:FALSE;
    }


    /**
     * 检验bucket名称是否合法
     * bucket的命名规范：
     * 1. 只能包括小写字母，数字
     * 2. 必须以小写字母或者数字开头
     * 3. 长度必须在3-63字节之间
     * @param string $bucket (Required)
     * @author xiaobing
     * @since 2011-12-27
     * @return boolean
     */
    public static function validate_bucket($bucket){
        $pattern = '/^[a-z0-9][a-z0-9-]{2,62}$/';
        if (!preg_match($pattern, $bucket)) {
            return false;
        }
        return true;
    }

    /**
     * 检验object名称是否合法
     * object命名规范:
     * 1. 规则长度必须在1-1023字节之间
     * 2. 使用UTF-8编码
     * @param string $object (Required)
     * @author xiaobing
     * @since 2011-12-27
     * @return boolean
     */
    public static function validate_object($object){
        $pattern = '/^.{1,1023}$/';
        if (empty($object) || !preg_match($pattern, $object)) {
            return false;
        }
        return true;
    }

    /**
     * 检验$options
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-12-27
     * @return boolean 
     */
    public static function validate_options($options){
        //$options
        if ($options != NULL && !is_array($options)) {
            throw new OSS_Exception ($options.':'.OSS_OPTIONS_MUST_BE_ARRAY);
        }
    }

    /**
     * 检测上传文件的内容
     * @param array $options (Optional)
     * @throws OSS_Exception
     * @author xiaobing
     * @since  2011-12-27
     * @return string
     */
    public static function validate_content($options){
        if(isset($options[self::OSS_CONTENT])){
            if($options[self::OSS_CONTENT] == '' || !is_string($options[self::OSS_CONTENT])){
                throw new OSS_Exception(OSS_INVALID_HTTP_BODY_CONTENT,'-600');
            }
        }else{
            throw new OSS_Exception(OSS_NOT_SET_HTTP_CONTENT, '-601');
        }
    }

    /**
     * @param $options
     * @throws OSS_Exception
     */
    public static function validate_content_length($options){
        if(isset($options[self::OSS_LENGTH]) && is_numeric($options[self::OSS_LENGTH])){
            if( !$options[self::OSS_LENGTH] > 0){
                throw new OSS_Exception(OSS_CONTENT_LENGTH_MUST_MORE_THAN_ZERO, '-602');
            }
        }else{
            throw new OSS_Exception(OSS_INVALID_CONTENT_LENGTH, '-602');
        }
    }

    /**
     * 校验BUCKET/OBJECT/OBJECT GROUP是否为空
     * @param  string $name (Required)
     * @param  string $errMsg (Required)
     * @throws OSS_Exception
     * @author xiaobing
     * @since 2011-12-27
     * @return void
     */
    public static function is_empty($name,$errMsg){
        if(empty($name)){
            throw new OSS_Exception($errMsg);
        }
    }

    /**
     * 设置http header
     * @param string $key (Required)
     * @param string $value (Required)
     * @param array $options (Required)
     * @throws OSS_Exception
     * @author xiaobing
     * @return void
     */
    public static function set_options_header($key, $value, &$options) {
        if (isset($options[self::OSS_HEADERS])) {
            if (!is_array($options[self::OSS_HEADERS])) {
                throw new OSS_Exception(OSS_INVALID_OPTION_HEADERS, '-600');
            }
        } else {
            $options[self::OSS_HEADERS] = array ();
        }
        $options[self::OSS_HEADERS][$key] = $value;
    }   

    /**
     * 仅供测试使用的接口,请勿使用
     */
    public static function generate_file($filename, $size) {
        if (file_exists($filename) && $size == filesize($filename)) {
            echo $filename." already exists, no need to create again. ";
            return;
        }
        $part_size = 1*1024*1024;
        $write_size = 0; 
        $fp = fopen($filename, "w");
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        if($fp) 
        { 
            while ($size > 0) {
                if ($size < $part_size) {
                    $write_size = $size;
                } else {
                    $write_size = $part_size;
                }
                $size -= $write_size;
                $a = $characters[rand(0, $charactersLength - 1)];
                $content = str_repeat($a, $write_size);
                $flag = fwrite($fp, $content); 
                if(!$flag) 
                { 
                    echo "write to ". $filename . " failed. <br>"; 
                    break; 
                } 
            }
        } 
        else 
        { 
            echo "open ". $filename . " failed. <br>"; 
        } 
        fclose($fp); 
    }

    public static function get_content_md5_of_file($filename, $from_pos, $to_pos) {
        $content_md5 = "";
        if (($to_pos - $from_pos) > self::OSS_MAX_PART_SIZE) {
            return $content_md5;
        }
        $filesize = filesize($filename);
        if ($from_pos >= $filesize || $to_pos >= $filesize || $from_pos < 0 || $to_pos < 0) {
            return $content_md5;
        }

        $total_length = $to_pos - $from_pos + 1;
        $buffer = 8192;
        $left_length = $total_length;
        if (!file_exists($filename)) {
            return $content_md5;
        }

        if (false === $fh = fopen($filename, 'rb')) {
            return $content_md5;
        }

        fseek($fh, $from_pos);
        $data = '';
        while (!feof($fh)) {
            if ($left_length >= $buffer) { 
                $read_length = $buffer;
            }
            else {
                $read_length = $left_length;
            }
            if ($read_length <= 0) {
                break;
            }
            else {
                $data .= fread($fh, $read_length);
                $left_length = $left_length - $read_length;
            }
        }
        fclose($fh);
        $content_md5 = base64_encode(md5($data, true));
        return $content_md5;
    }

    /**
     * 检测是否windows系统，因为windows系统默认编码为GBK
     * @return bool
     */
    public static function is_win(){
        return strtoupper(substr(PHP_OS,0,3)) == "WIN";
    }

    /**
     * 主要是由于windows系统编码是gbk，遇到中文时候，如果不进行转换处理会出现找不到文件的问题
     * @param $file_path
     * @return string
     */
    public static function encoding_path($file_path){
        if(self::chk_chinese($file_path) && self::is_win()){
            $file_path = iconv('utf-8', 'gbk',$file_path);
        }
        return $file_path;
    }

    /**
     * 转换响应
     * @param $response
     * @return array
     * @throws Exception
     */
    public static function parse_response($response, $format="array"){
        //如果启用响应结果转换，则进行转换，否则原样返回
        $body = $response->body;
        $headers = $response->header;

        switch (strtolower($format)) {
            case 'array':
                $body = empty($body) ? $body : XML2Array::createArray($body);
                break;
            case "json":
                $body = empty($body) ? $body : json_encode(XML2Array::createArray($body));
                break;
            default:
                break;
        }

        return array(
            'success' => $response->isOk(),
            'status' => $response->status,
            'header' => $headers,
            'body' => $body
        );
        return $response;
    }
}
