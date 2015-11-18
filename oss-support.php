<?php
/**
 * Plugin Name: 阿里云附件存储
 * Plugin URI: "http://yii.im/posts/aliyun-oss-support-plugin-for-wordpress"
 * Description: 使用阿里云存储OSS作为附件存储空间。This is a plugin that used Aliyun Cloud Storage(Aliyun OSS) for attachments remote saving.
 * Author: Ivan Chou
 * Author URI: http://yii.im/
 * Version: 2.3.2
 * Updated_at: 2015-11-16
 */

if (! class_exists(Alibaba))
    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'SDK/alioss.class.php');

//  plugin url
define('OSS_BASEFOLDER', plugin_basename(dirname(__FILE__)));

$oss_options = get_option('oss_options');

/**
 * 初始化
 */
function oss_set_options() {
    $options         = array(
        'bucket'     => "",
        'ak'         => "",
        'sk'         => "",
        'end_point'  => "",
        'path'       => "",
        'static_url' => "",
        'img_url'    => ""
    );
    add_option('oss_options', $options, '', 'yes');
}
register_activation_hook(__FILE__, 'oss_set_options');

/**
 * 设置提醒
 *
 * @param $oss_options
 */
function oss_admin_warnings($oss_options) {

    $oss_bucket = isset($oss_options['bucket']) ? esc_attr($oss_options['bucket']) : null;
	if ( !$oss_bucket && !isset($_POST['submit']) ) {
		function oss_warning() {
			echo "<div id='oss-warning' class='updated fade'><p><strong>".__('OSS is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your OSS Bucket </a> for it to work.'), "options-general.php?page=" . OSS_BASEFOLDER . "/oss-support.php")."</p></div>";
		}
		add_action('admin_notices', 'oss_warning');
		return;
	} 
}
oss_admin_warnings($oss_options);

/**
 * 上传原文件到 OSS (并根据设定清理本地文件)
 *
 * @param $file
 * @return mixed
 */
function upload_orign_2_oss($file)
{
    if ($_GET["action"] == 'upload-plugin' || $_GET["action"] == 'upload-theme') 
        return;

    $wp_uploads = wp_upload_dir();
    $oss_options = get_option('oss_options', TRUE);
    $config = array(
            'id'     => esc_attr($oss_options['ak']),
            'key'    => esc_attr($oss_options['sk']),
            'bucket' => esc_attr($oss_options['bucket']),
            'end_point' => esc_attr($oss_options['end_point'])
        );
    $oss_upload_path = trim($oss_options['path'],'/');
    $oss_nolocalsaving = (esc_attr($oss_options['nolocalsaving'])=='true') ? true : false;

    $object = str_replace($wp_uploads['basedir'], '', $file['file']);
    $object = ltrim($oss_upload_path . '/' .ltrim($object, '/'), '/');

    if(!is_object($aliyun_oss))
        $aliyun_oss = Alibaba::Storage($config);

    $opt['Expires'] = 'access plus 1 years';
    $aliyun_oss->saveFile( $object, $file['file'], $opt);

    if($oss_nolocalsaving && false === strpos($file['type'], 'image')){
        _delete_local_file($file['file']);
    }

    return $file;

}
add_filter('wp_handle_upload', 'upload_orign_2_oss', 30);

/**
 * 上传生成的缩略图到 OSS (并根据设定清理本地文件)
 *
 * @param $metadata
 * @return mixed
 */
function upload_thumb_2_oss($metadata)
{
    if ( preg_match('/\d{4}\/\d{2}/', $metadata['file'], $m) ) {
        $wp_uploads = wp_upload_dir($m[0]);
    } else {
        return $metadata;
    }
    
    $oss_options = get_option('oss_options', TRUE);
    $config = array(
            'id'     => esc_attr($oss_options['ak']),
            'key'    => esc_attr($oss_options['sk']),
            'bucket' => esc_attr($oss_options['bucket']),
            'end_point' => esc_attr($oss_options['end_point'])
        );
    $oss_upload_path = trim($oss_options['path'],'/');
    $oss_nolocalsaving = (esc_attr($oss_options['nolocalsaving'])=='true') ? true : false;

    if(!is_object($aliyun_oss) && $oss_options['img_url'] == "")
        $aliyun_oss = Alibaba::Storage($config);
    $opt['Expires'] = 'access plus 1 years';

    if (isset($metadata['sizes']) && count($metadata['sizes']) > 0) {
        foreach ($metadata['sizes'] as $val) {
            $object = ltrim($oss_upload_path . '/' .trim($wp_uploads['subdir'], '/').'/'.ltrim($val['file'], '/'), '/');
            $file = $wp_uploads['path'].'/'.$val['file'];

            if($oss_options['img_url'] == "")
                $aliyun_oss->saveFile( $object, $file, $opt);

            if($oss_nolocalsaving)
                _delete_local_file($file);
        }
    }

    if($oss_nolocalsaving) {
        $file = $wp_uploads['basedir'].'/'.$metadata['file'];
        _delete_local_file($file);
    }

    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'upload_thumb_2_oss', 60);

/**
 * 删除远程服务器上的单个文件
 * 
 * @param $file
 * @return mixed
 */
function delete_remote_file($file)
{
    if(!false == strpos($file, '@!'))
        return $file;

    $oss_options = get_option('oss_options', TRUE);
    $config = array(
            'id'     => esc_attr($oss_options['ak']),
            'key'    => esc_attr($oss_options['sk']),
            'bucket' => esc_attr($oss_options['bucket']),
            'end_point' => esc_attr($oss_options['end_point'])
        );
    $oss_upload_path = trim($oss_options['path'],'/');
    $wp_uploads = wp_upload_dir();

    $del_file = str_replace($wp_uploads['basedir'], '', $file);
    $del_file = ltrim($oss_upload_path . '/' .ltrim($del_file, '/'), '/');

    if(!is_object($aliyun_oss))
        $aliyun_oss = Alibaba::Storage($config);

    $aliyun_oss->delete($del_file);

    return $file;
}
add_action('wp_delete_file', 'delete_remote_file');

/**
 * 删除本地的缩略图（修正由于启用图片服务导致的原生方法删除不了缩略图）
 * 仅在开启图片服务时启用
 * 
 * @param $file
 * @return mixed
 */
function delete_thumb_img($file)
{
    if(!false == strpos($file, '@!')) //todo
        return $file;

    $file_t = substr($file, 0, strrpos($file, '.'));
    array_map('_delete_local_file', glob($file_t.'-*'));
    return $file;
}
if(!$oss_options['img_url'] == "")
    add_action('wp_delete_file', 'delete_thumb_img', 99);

/**
 * 修改从数据库中取出的图片信息，以使用 Aliyun 的图片服务
 * 仅在开启图片服务时启用
 *
 * @param $data
 * @return mixed
 */
function modefiy_img_meta($data) {
    $filename = basename($data['file']);

    if(isset($data['sizes']['thumbnail'])) {
        $data['sizes']['thumbnail']['file'] = $filename.'@!thumbnail';
    }
    if(isset($data['sizes']['post-thumbnail'])) {
        $data['sizes']['post-thumbnail']['file'] = $filename.'@!post-thumbnail';
    }
    if(isset($data['sizes']['medium'])) {
        $data['sizes']['medium']['file'] = $filename.'@!medium';
    }
    if(isset($data['sizes']['large'])) {
        $data['sizes']['large']['file'] = $filename.'@!large';
    }

    return $data;
}
if(!$oss_options['img_url'] == "")
    add_filter('wp_get_attachment_metadata', 'modefiy_img_meta', 990);

/**
 * 重置图片链接，使用独立的图片服务器。
 * 仅在开启图片服务时启用
 *
 * @param $url
 * @return mixed
 */
function modefiy_img_url($url, $post_id) {
    $wp_uploads = wp_upload_dir();
    $oss_options = get_option('oss_options', TRUE);

    if(wp_attachment_is_image($post_id)){
        $img_baseurl = rtrim($oss_options['img_url'], '/');
        if(rtrim($oss_options['path'], '/') != ""){
            $img_baseurl = $img_baseurl .'/'. rtrim($oss_options['path'], '/');
        }
        $url = str_replace(rtrim($wp_uploads['baseurl'], '/'), $img_baseurl, $url);
    }
    return $url;
}
if(!$oss_options['img_url'] == "")
    add_filter('wp_get_attachment_url', 'modefiy_img_url', 30, 2);

/**
 * 设置 upload_url_path，使用外部存储OSS
 *
 * @param $uploads
 * @return mixed
 */
function reset_upload_url_path( $uploads ) {
    $oss_options = get_option('oss_options', TRUE);

    if ($oss_options['static_url'] != "") {
        $baseurl = rtrim($oss_options['static_url'], '/');
        if(rtrim($oss_options['path'], '/') != ""){
            $baseurl = $baseurl .'/'. rtrim($oss_options['path'], '/');
        }
        $uploads['baseurl'] = $baseurl;
    }
    return $uploads;
}
add_filter( 'upload_dir', 'reset_upload_url_path', 30 );

/**
 * 添加设置页面入口连接
 */
function oss_plugin_action_links( $links, $file ) {
    if ( $file == plugin_basename( dirname(__FILE__).'/oss-support.php' ) ) {
        $links[] = '<a href="options-general.php?page=' . OSS_BASEFOLDER . '/oss-support.php">'.__('Settings').'</a>';
    }
    return $links;
}
add_filter( 'plugin_action_links', 'oss_plugin_action_links', 10, 2 );

/**
 * 设置页面
 */
function oss_add_setting_page() {
    add_options_page('OSS Setting', 'OSS Setting', 'manage_options', __FILE__, 'oss_setting_page');
}
add_action('admin_menu', 'oss_add_setting_page');

function oss_setting_page() {
    
    $oss_options = get_option('oss_options');

    // Set(rewrite) the setting. 
    $options = array();
    if(isset($_POST['bucket'])) {
        $options['bucket'] = trim(stripslashes($_POST['bucket']));
    }
    if(isset($_POST['ak'])) {
        $options['ak'] = trim(stripslashes($_POST['ak']));
    }
    if(isset($_POST['end_point'])) {
        $options['end_point'] = trim(stripslashes($_POST['end_point']));
    }
    if(isset($_POST['sk'])) {
        if($_POST['sk'] === '') {
            $options['sk'] = $oss_options['sk'];
        } else {
            $options['sk'] = trim(stripslashes($_POST['sk']));
        }
    }
    if(isset($_POST['path'])) {
        $options['path'] = trim(trim(stripslashes($_POST['path'])), '/').'/';
    }
    if(isset($_POST['static_url'])) {
        $options['static_url'] = trim(stripslashes($_POST['static_url']));
    }
    if(isset($_POST['img_url'])) {
        $options['img_url'] = trim(stripslashes($_POST['img_url']));
    }
    if(isset($_POST['nolocalsaving'])) {
        $options['nolocalsaving'] = 'true';
    }

    if($options !== array() ){

        update_option('oss_options', $options);
        $oss_options = $options;
        ?>
        <div class="updated"><p><strong>设置已保存！</strong></p></div>
    <?php
    }

    // Show the setting. 
    $oss_bucket = isset($oss_options['bucket']) ? esc_attr($oss_options['bucket']) : null;
    $oss_ak = isset($oss_options['ak']) ? esc_attr($oss_options['ak']) : null;
    $oss_sk = isset($oss_options['sk']) ? esc_attr($oss_options['sk']) : null;
    $oss_path = isset($oss_options['path']) ? esc_attr($oss_options['path']) : null;
    $end_point = isset($oss_options['end_point']) ? esc_attr($oss_options['end_point']) : null;
    $oss_static_url = isset($oss_options['static_url']) ? esc_attr($oss_options['static_url']) : null;
    $oss_img_url = isset($oss_options['img_url']) ? esc_attr($oss_options['img_url']) : null;

    $oss_nolocalsaving = isset($oss_options['nolocalsaving']) ? esc_attr($oss_options['nolocalsaving']) : null;
    ($oss_nolocalsaving == 'true') ? ($oss_nolocalsaving = true) : ($oss_nolocalsaving = false);
    ?>
    <div class="wrap" style="margin: 10px;">
        <h2>阿里云存储 设置</h2>
        <form name="form1" method="post" action="<?php echo wp_nonce_url('./options-general.php?page=' . OSS_BASEFOLDER . '/oss-support.php'); ?>">
            <hr>
            <fieldset>
                <legend>Bucket 设置</legend>
                <input type="text" name="bucket" value="<?php echo $oss_bucket;?>" placeholder="请输入云存储使用的 bucket"/>
                <p>请先访问 <a href="http://i.aliyun.com/dashboard?type=oss">阿里云存储</a> 创建 bucket 后，填写以上内容。</p>
            </fieldset>
            <hr>
            <fieldset>
                <legend>Access Key / API key</legend>
                <input type="text" name="ak" value="<?php echo $oss_ak;?>" placeholder=""/>
            </fieldset>
            <br>
            <fieldset>
                <legend>Secret Key</legend>
                <?php if ( ! empty($oss_sk)) : ?>
                    <input type="text" name="sk" value="" placeholder="〄 你看不到我 ʅ(‾◡◝)"/> ←置空请填入空格
                <?php else : ?>
                    <input type="text" name="sk" value="<?php echo $oss_sk;?>" placeholder=""/>
                <?php endif ?>
                <p>访问 <a href="http://i.aliyun.com/access_key/" target="_blank">阿里云 密钥管理页面</a>，获取 AKSK</p>
            </fieldset>
            <fieldset>
                <legend>数据节点地址</legend>
                <input type="text" name="end_point" value="<?php echo $end_point;?>" placeholder=""/>
                <p>查看所有节点及地址 <a href="https://docs.aliyun.com/?spm=5176.7114037.1996646101.11.XMMlZa&pos=6#/pub/oss/product-documentation/domain-region" target="_blank">OSS数据中心地址</a></p>
            </fieldset>
            <hr>
            <fieldset>
                <legend>Save path on OSS</legend>
                <input type="text" name="path" value="<?php echo $oss_path;?>" placeholder="/"/>
                <P>可以设置在 OSS 上存储的位置，如果你要存在根目录就留空，我真的没意见(ﾉ￣д￣)ﾉ</P>
            </fieldset>
            <fieldset>
                <legend>OSS-Http-Url</legend>
                <input type="text" name="static_url" value="<?php echo $oss_static_url;?>" placeholder="http://"/>
                <P>OSS Bucket 的可访问 URL，支持已绑定到 OSS 的独立域名，留空将使用本地资源</P>
            </fieldset>
            <hr>
            <fieldset>
                <legend>Aliyun-OSS 图片服务的 URL</legend>
                <input type="text" name="img_url" value="<?php echo $oss_img_url;?>" placeholder="http://"/>
                <dl>
                    <dt>请瞩目：</dt>
                    <dd>1.图片服务是可选的，留空即可不启用</dd>
                    <dd>2.使用请先在Aliyun中设置好四种样式: <code>{'thumbnail','post-thumbnail','large','medium'}</code></dd>
                    <dd>3.开启图片服务后，只有原图会上传到 OSS 中，缩略图不会再上传</dd>
                </dl>
            </fieldset>
            <hr>
            <fieldset>
                <label><input type="checkbox" name="nolocalsaving" <?php if($oss_nolocalsaving) echo 'checked="TRUE"';?> /> 不在本地保留备份</label>
            </fieldset>
            <fieldset class="submit">
                <input type="submit" name="submit" value="更新" />
            </fieldset>
        </form>
    </div>
<?php
}

/**
 * 删除本地的文件
 *
 * @param $file
 * @return bool
 */
function _delete_local_file($file){
    try{
        //文件不存在
        if(!@file_exists($file))
            return TRUE;
        //删除文件
        if(!@unlink($file))
            return FALSE;
        return TRUE;
    }
    catch(Exception $ex){
        return FALSE;
    }
}
