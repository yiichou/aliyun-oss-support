<?php
$options = array_merge(OSS\WP\Config::$originOptions, get_option('oss_options', []));
$d = 'aliyun-oss';
?>

<div class="wrap" style="margin: 10px;">
    <h1><?= __('Aliyun OSS Settings', $d)?></h1>
    <form name="form1" method="post" action="<?= wp_nonce_url(OSS\WP\Config::$settingsUrl); ?>">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="access_key">AccessKey</label></th>
                <td><input name="access_key" type="text" id="access_key"
                           value="<?= $options['ak'] ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="access_key_secret"></label>AccessKeySecret</th>
                <td><input name="access_key_secret" type="text" id="access_key_secret" value=""
                           placeholder="~<?= __("You can't see me", $d) ?> ʅ(‾◡◝)" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="region"><?= __('Region', $d).'/'.__('Endpoint', $d) ?></label></th>
                <td>
                    <select name="region" id="region">
                        <option value="oss-cn-hangzhou"><?= __('oss-cn-hangzhou', $d)?></option>
                        <option value="oss-cn-shanghai"><?= __('oss-cn-shanghai', $d)?></option>
                        <option value="oss-cn-qingdao"><?= __('oss-cn-qingdao', $d)?></option>
                        <option value="oss-cn-beijing"><?= __('oss-cn-beijing', $d)?></option>
                        <option value="oss-cn-zhangjiakou"><?= __('oss-cn-zhangjiakou', $d)?></option>
                        <option value="oss-cn-shenzhen"><?= __('oss-cn-shenzhen', $d)?></option>
                        <option value="oss-cn-hongkong"><?= __('oss-cn-hongkong', $d)?></option>
                        <option value="oss-us-west-1"><?= __('oss-us-west-1', $d)?></option>
                        <option value="oss-us-east-1"><?= __('oss-us-east-1', $d)?></option>
                        <option value="oss-ap-southeast-1"><?= __('oss-ap-southeast-1', $d)?></option>
                        <option value="oss-ap-northeast-1"><?= __('oss-ap-northeast-1', $d)?></option>
                        <option value="oss-eu-central-1"><?= __('oss-eu-central-1', $d)?></option>
                    </select>

                    <label for="internal" style="margin-left: 72px">
                        <input name="internal" type="checkbox" id="internal" <?= $options['internal'] ? 'checked' : '' ?>>
                        <?= __('internal', $d)?>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
        <hr >

        <h2 class="title"><?= __('Bucket Settings', $d) ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="bucket">Bucket</label></th>
                <td><input name="bucket" type="text" id="bucket" value="<?= $options['bucket'] ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="static_host"></label><?= __('Bucket Host', $d) ?></th>
                <td>
                    <input name="static_host" type="text" id="static_host" value="<?= $options['static_url'] ?>" class="regular-text host">
                    <?= is_ssl()?'<p class="description">'.__('Your site is working under https, please make sure the host can use https too.', $d).'</p>':'' ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="store_path"></label><?= __('Storage Path', $d) ?></th>
                <td><input name="store_path" type="text" id="store_path" value="<?= $options['path'] ?>" class="regular-text">
                    <p class="description"><?= __("Keep this empty if you don't need.", $d) ?></p></td>
            </tr>
            <tr>
                <th scope="row"><?= __('Keep Files', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?= __('Keep Files', $d) ?></span></legend>
                        <label for="no_local_saving">
                            <input name="no_local_saving" type="checkbox" id="no_local_saving"
                                <?= $options['nolocalsaving'] ? 'checked' : '' ?>> <?= __("Don't keep files on local server.", $d) ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            </tbody>
        </table>
        <hr>

        <h2 class="title"><?= __('Aliyun Image Service Settings', $d) ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><?= __('Image Service', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?= __('Image Service Enable', $d) ?></span></legend>
                        <label for="img_host_enable">
                            <input name="img_host_enable" type="checkbox" id="img_host_enable"
                                <?= $options['img_service'] || $options['img_url'] ? 'checked' : '' ?>> <?= __('Enable', $d) ?>
                        </label>
                    </fieldset>
                    <p class="description"><?= __("Use Aliyun Image Service to provide thumbnails, no need to upload thumbnails to OSS any more.", $d) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?= __('Preset Image Style', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?= __('Preset Image Style', $d) ?></span></legend>
                        <label for="img_style">
                            <input name="img_style" type="checkbox" id="img_style" <?= $options['img_style'] ? 'checked' : '' ?>
                                <?= $options['img_service'] ? '' : 'disabled' ?>> <?= __('Enable', $d) ?>
                        </label>
                    </fieldset>
                    <p class="description"><?= __("Optional, use preset styles instead of dynamic params to deal image.", $d) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td>
                    <p class="description"><?= __("There is a guide about Image Service.", $d) ?> =>
                        <a href="https://github.com/IvanChou/aliyun-oss-support/wiki/How-to-use-Image-Service">
                            <?= __("How to use Image Service", $d) ?>
                        </a>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <input name="keep_settings" type="hidden" id="keep_settings" value="<?= $options['keep_settings'] ?>">
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?= __('Commit', $d)?>"></p>
    </form>
</div>

<script>
    jQuery(function ($) {
        var region = '<?= $options['region'] ?>';
        $('#region option[value='+region+']').attr('selected', 'selected');

        $('input.host').blur(function () {
            var val = $(this).val().replace(/(.*\/\/|)(.+?)(\/.*|)$/, '$2');
            $(this).val(val);
        });

        $('#bucket').blur(function () {
            var $staticHost = $('#static_host');
            var bucket = $(this).val();
            var region = $('#region').val();
            if ( bucket !== "" && $staticHost.val() == "" )
                $staticHost.val(bucket + '.' + region + '.aliyuncs.com');
        });

        $('#img_host_enable').change(function () {
            if ($(this).prop('checked')) {
                $('#img_host').attr('disabled', false);
                $('#img_style').attr('disabled', false);
            } else {
                $('#img_host').attr('disabled', true);
                $('#img_style').prop('checked', false).attr('disabled', true);
            }
        })
    })
</script>
