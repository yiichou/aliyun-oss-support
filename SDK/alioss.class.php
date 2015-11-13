<?php
//检测API路径
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'sdk.class.php';

/* 
有没有发现这里做了一些没什么屁用的封装？
看上去很厉害的样子，又是静态类，又是单例

好吧，我说实话，做这个只是为了把在外围调用的函数封装得和 ACE 内置的 Alibaba 类一样
因为我还要维护 ACE 版本（其实主要是维护 ACE 版本），所以这样我可以偷懒，你懂的
静态类 Alibaba 只是为了做一个命名空间的作用
单例类 Storage 也只是顺手弄的，毕竟带上缩略图，一次要上传好几张图片，用单例还是可以省点资源
以上，看到请忽略这个文件
*/

final class Storage {
  private $oss_access_id   = '';
  private $oss_access_key  = '';
  private $bucket          = '';
  private $hostname        = NULL;
  private $aliyun_oss      = NULL;
  private static $instance = NULL;
  
  private function __construct($config){
    if (isset($config['id']) && isset($config['key']) && isset($config['bucket'])) {
      $this->oss_access_id   = $config['id'];
      $this->oss_access_key  = $config['key'];
      $this->bucket          = $config['bucket'];
      $this->hostname        = isset($config['end_point']) ? $config['end_point'] : NULL;
      $this->aliyun_oss      = new ALIOSS($this->oss_access_id, $this->oss_access_key, $this->hostname);
    }
  }
  
  private function __clone(){}
  
  public static function getInstance($config) {
    if(self::$instance instanceof self)
      return self::$instance;
    return self::$instance = new self($config);
  }
  
  public function saveFile($object, $file, $opt = NULL) {
    self::$instance->aliyun_oss->upload_file_by_file( $this->bucket, $object, $file, $opt );
  }
  
  public function delete($file) {
    self::$instance->aliyun_oss->delete_object( $this->bucket, $file );
  }
  
}

class Alibaba {
  public static function Storage($config) {
    return Storage::getInstance($config);
  }
  
}
