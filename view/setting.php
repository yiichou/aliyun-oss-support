<?php
$options = array_merge(OSS\WP\Config::$originOptions, get_option('oss_options', array()));
$d = 'aliyun-oss';
?>

<div class="wrap" style="margin: 10px;">
    <h1><?php echo __('Aliyun OSS Settings', $d)?></h1>
    <form name="form1" method="post" action="<?php echo wp_nonce_url(OSS\WP\Config::$settingsUrl); ?>">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="access_key">AccessKey</label></th>
                <td><input name="access_key" type="text" id="access_key"
                           value="<?php echo $options['ak'] ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="access_key_secret"></label>AccessKeySecret</th>
                <td><input name="access_key_secret" type="text" id="access_key_secret" value=""
                           placeholder="~<?php echo __("You can't see me", $d) ?> ʅ(‾◡◝)" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="region"><?php echo __('Region', $d).'/'.__('Endpoint', $d) ?></label></th>
                <td>
                    <select name="region" id="region">
                        <option value="oss-cn-hangzhou"><?php echo __('oss-cn-hangzhou', $d)?></option>
                        <option value="oss-cn-shanghai"><?php echo __('oss-cn-shanghai', $d)?></option>
                        <option value="oss-cn-qingdao"><?php echo __('oss-cn-qingdao', $d)?></option>
                        <option value="oss-cn-beijing"><?php echo __('oss-cn-beijing', $d)?></option>
                        <option value="oss-cn-zhangjiakou"><?php echo __('oss-cn-zhangjiakou', $d)?></option>
                        <option value="oss-cn-huhehaote"><?php echo __('oss-cn-huhehaote', $d)?></option>
                        <option value="oss-cn-shenzhen"><?php echo __('oss-cn-shenzhen', $d)?></option>
                        <option value="oss-cn-hongkong"><?php echo __('oss-cn-hongkong', $d)?></option>
                        <option value="oss-us-west-1"><?php echo __('oss-us-west-1', $d)?></option>
                        <option value="oss-us-east-1"><?php echo __('oss-us-east-1', $d)?></option>
                        <option value="oss-ap-southeast-1"><?php echo __('oss-ap-southeast-1', $d)?></option>
                        <option value="oss-ap-southeast-2"><?php echo __('oss-ap-southeast-2', $d)?></option>
                        <option value="oss-ap-southeast-3"><?php echo __('oss-ap-southeast-3', $d)?></option>
                        <option value="oss-ap-southeast-5"><?php echo __('oss-ap-southeast-5', $d)?></option>
                        <option value="oss-ap-northeast-1"><?php echo __('oss-ap-northeast-1', $d)?></option>
                        <option value="oss-ap-south-1"><?php echo __('oss-ap-south-1', $d)?></option>
                        <option value="oss-eu-central-1"><?php echo __('oss-eu-central-1', $d)?></option>
                        <option value="oss-me-east-1"><?php echo __('oss-me-east-1', $d)?></option>
                    </select>

                    <label for="internal" style="margin-left: 48px">
                        <input name="internal" type="checkbox" id="internal" <?php echo $options['internal'] ? 'checked' : '' ?>>
                        <?php echo __('internal', $d)?>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
        <hr >

        <h2 class="title"><?php echo __('Bucket Settings', $d) ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="bucket">Bucket</label></th>
                <td><input name="bucket" type="text" id="bucket" value="<?php echo $options['bucket'] ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="static_host"></label><?php echo __('Bucket Host', $d) ?></th>
                <td>
                    <input name="static_host" type="text" id="static_host" value="<?php echo $options['static_url'] ?>" class="regular-text host">
                    <?php echo is_ssl()?'<p class="description">'.__('Your site is working under https, please make sure the host can use https too.', $d).'</p>':'' ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="store_path"></label><?php echo __('Storage Path', $d) ?></th>
                <td><input name="store_path" type="text" id="store_path" value="<?php echo $options['path'] ?>" class="regular-text">
                    <p class="description"><?php echo __("Keep this empty if you don't need.", $d) ?></p></td>
            </tr>
            </tbody>
        </table>
        <hr>

        <h2 class="title"><?php echo __('Aliyun Image Service Settings', $d) ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><?php echo __('Image Service', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Image Service', $d) ?></span></legend>
                        <label for="img_service">
                            <input name="img_service" type="checkbox" id="img_service"
                                <?php echo $options['img_service'] ? 'checked' : '' ?>> <?php echo __('Enable', $d) ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php echo __("Use Aliyun Image Service to provide thumbnails, no need to upload thumbnails to OSS any more.", $d) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __('Preset Image Style', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Preset Image Style', $d) ?></span></legend>
                        <label for="img_style">
                            <input name="img_style" type="checkbox" id="img_style" <?php echo $options['img_style'] ? 'checked' : '' ?>
                                <?php echo $options['img_service'] ? '' : 'disabled' ?>> <?php echo __('Enable', $d) ?>
                        </label>
                    </fieldset>
                    <p class="description">
                        <?php echo __("Optional, use preset styles instead of dynamic params to deal image.", $d) ?>
                        <span id="export_style_profile" <?php echo $options['img_style'] ? '' : 'style="display: none"' ?>>
                            => <a href="/wp-admin/options-general.php?page=aliyun-oss&action=update-img-style-profile" target="_blank">
                                <?php echo __("Click to export style profile", $d) ?>
                            </a>
                        </span>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __('Source Image Protection', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Source Image Protection', $d) ?></span></legend>
                        <label for="source_img_protect">
                            <input name="source_img_protect" type="checkbox" id="source_img_protect"
                                <?php echo $options['source_img_protect'] ? 'checked' : '' ?>
                                <?php echo $options['img_style'] ? '' : 'disabled' ?> > <?php echo __('Enable', $d) ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php echo __("If you have enabled source image protection on Aliyun OSS, don't forget to enable this.", $d) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo __('Custom Separator', $d) ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo __('Custom Separator', $d) ?></span></legend>
                        <label for="custom_separator">
                            <input name="custom_separator" type="radio" value=""
                                <?php echo empty($options['custom_separator']) ? 'checked' : '' ?> <?php echo $options['img_style'] ? '' : 'disabled' ?>>
                            <span style="padding-right: 2rem"><?php echo __('Default', $d) ?></span>
                            <input name="custom_separator" type="radio" value="-"
                                <?php echo $options['custom_separator'] == '-' ? 'checked' : '' ?> <?php echo $options['img_style'] ? '' : 'disabled' ?>>
                            <span style="padding-right: 2rem">-</span>
                            <input name="custom_separator" type="radio" value="_"
                                <?php echo $options['custom_separator'] == '_' ? 'checked' : '' ?> <?php echo $options['img_style'] ? '' : 'disabled' ?>>
                            <span style="padding-right: 2rem">_</span>
                            <input name="custom_separator" type="radio" value="/"
                                <?php echo $options['custom_separator'] == '/' ? 'checked' : '' ?> <?php echo $options['img_style'] ? '' : 'disabled' ?>>
                            <span style="padding-right: 2rem">/</span>
                            <input name="custom_separator" type="radio" value="!"
                                <?php echo $options['custom_separator'] == '!' ? 'checked' : '' ?> <?php echo $options['img_style'] ? '' : 'disabled' ?>>
                            <span style="padding-right: 2rem">!</span>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"></th>
                <td>
                    <p class="description"><?php echo __("There is a guide about Image Service.", $d) ?> =>
                        <a href="https://github.com/IvanChou/aliyun-oss-support/wiki/How-to-use-Image-Service">
                            <?php echo __("How to use Image Service", $d) ?>
                        </a>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <p>
            <a href="#more-settings" id="load-more-settings"><?php echo __('More Options', $d) ?></a>
        </p>
        <div style="display: none" id="more-settings">
            <h2 class="title"><?php echo __('Advanced Options', $d) ?></h2>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php echo __('Clear Files On Server', $d) ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('Clear Files On Server', $d) ?></span></legend>
                            <label for="no_local_saving">
                                <input name="no_local_saving" type="checkbox" id="no_local_saving"
                                    <?php echo $options['nolocalsaving'] ? 'checked' : '' ?>> <?php echo __("Don't keep files on local server.", $d) ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo __('keep Settings When Uninstall', $d) ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('keep Settings When Uninstall', $d) ?></span></legend>
                            <label for="keep_settings">
                                <input name="keep_settings" type="checkbox" id="keep_settings"
                                    <?php echo $options['keep_settings'] ? 'checked' : '' ?>> <?php echo __('Enable', $d) ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Commit', $d)?>"></p>
    </form>
</div>

<script>
    jQuery(function ($) {
        var region = '<?php echo $options['region'] ?>';
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

        $('#img_service').change(function () {
            if ($(this).prop('checked')) {
                $('#img_style').attr('disabled', false);
            } else {
                $('#img_style').prop('checked', false).attr('disabled', true);
                $('#source_img_protect').prop('checked', false).attr('disabled', true);
                $('input[name="custom_separator"]').attr('disabled', true).eq(0).prop('checked', true);
            }
        });

        $('#img_style').change(function () {
            if ($(this).prop('checked')) {
                $('#source_img_protect').attr('disabled', false);
                $('input[name="custom_separator"]').attr('disabled', false);
                $('#export_style_profile').show();
            } else {
                $('#source_img_protect').prop('checked', false).attr('disabled', true);
                $('input[name="custom_separator"]').attr('disabled', true).eq(0).prop('checked', true);
                $('#export_style_profile').hide();
            }
        });

        $('#load-more-settings').click(function () {
            $('#more-settings').show();
            $(this).remove();
        })
    })
</script>
