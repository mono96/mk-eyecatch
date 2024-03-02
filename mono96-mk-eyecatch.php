<?php
/**
 * Plugin Name: Mono96 MK Eyecatch
 * Description: 管理画面で指定したテキスト（日本語含む）と画像のURLを入力し、画像の真ん中にテキストを追加し、直接ダウンロードするプラグイン。画像URLが未設定の場合はデフォルト画像を使用。フォント指定可能。
 * Version: 1.1
 * Author: Mono96
 */

if (!defined('WPINC')) {
    die;
}

function mono96_mk_eyecatch_menu() {
    add_menu_page('Mono96 MK Eyecatch', 'MK Eyecatch', 'manage_options', 'mono96-mk-eyecatch', 'mono96_mk_eyecatch_options_page');
}
add_action('admin_menu', 'mono96_mk_eyecatch_menu');

function mono96_mk_eyecatch_register_settings() {
    register_setting('mono96-mk-eyecatch-options-group', 'mono96_mk_eyecatch_image_url', 'esc_url_raw');
}
add_action('admin_init', 'mono96_mk_eyecatch_register_settings');

function mono96_mk_eyecatch_options_page() {
    ?>
    <div class="wrap">
        <h2>Mono96 MK Eyecatch</h2>
        <form method="post" action="options.php">
            <?php settings_fields('mono96-mk-eyecatch-options-group'); ?>
            <?php do_settings_sections('mono96-mk-eyecatch-options-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">画像のURL:</th>
                    <td><input type="text" name="mono96_mk_eyecatch_image_url" value="<?php echo esc_attr(get_option('mono96_mk_eyecatch_image_url')); ?>" size="60" /></td>
                </tr>
            </table>
            <?php submit_button('画像URLを保存'); ?>
        </form>
        <form method="post">
            <h2>テキスト設定</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">テキスト1（最大20文字）:</th>
                    <td><input type="text" name="mono96_mk_eyecatch_text1" maxlength="20" size="20" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">テキスト2（最大20文字）:</th>
                    <td><input type="text" name="mono96_mk_eyecatch_text2" maxlength="20" size="20" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">テキスト3（最大20文字）:</th>
                    <td><input type="text" name="mono96_mk_eyecatch_text3" maxlength="20" size="20" /></td>
                </tr>
            </table>
            <input type="submit" name="submit_text" class="button-primary" value="画像を生成" />
        </form>
    </div>
    <?php
}

function mono96_mk_eyecatch_process_image() {
    if (isset($_POST['submit_text'])) {
        $image_url = esc_url_raw(get_option('mono96_mk_eyecatch_image_url'));
        $plugin_dir_path = plugin_dir_path(__FILE__);
        $font_path = $plugin_dir_path . 'NotoSansJP-VariableFont_wght.ttf'; // Ensure the font file is present in the plugin directory.

        // If the image URL is not set, use the default image.
        if (empty($image_url)) {
            $image_path = $plugin_dir_path . 'eye2024.jpg';
        } else {
            $image_path = download_url($image_url);
            if (is_wp_error($image_path)) {
                wp_die('Failed to download the image.');
            }
        }

        $image = imagecreatefromjpeg($image_path);
        $color = imagecolorallocate($image, 0, 0, 0);
        $fontSize = 60;
        $angle = 0;
        $texts = [
            sanitize_text_field($_POST['mono96_mk_eyecatch_text1']),
            sanitize_text_field($_POST['mono96_mk_eyecatch_text2']),
            sanitize_text_field($_POST['mono96_mk_eyecatch_text3'])
        ];
        $yOffset = $fontSize;

        foreach ($texts as $text) {
            if (!empty($text)) {
                $textBox = imagettfbbox($fontSize, $angle, $font_path, $text);
                $textWidth = $textBox[2] - $textBox[0];
                $x = (imagesx($image) - $textWidth) / 2;
                $y = (imagesy($image) / 2) - ((count($texts) - 1) * $yOffset) / 2 + ($yOffset * array_search($text, $texts));
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $font_path, $text);
            }
        }

        // Generate a timestamp-based filename for the output image.
        $timestamp = date('YmdHis');
        $temp_image_path = sys_get_temp_dir() . '/mk_eyecatch_' . $timestamp . '.jpg';
        imagejpeg($image, $temp_image_path);
        imagedestroy($image);

        if (!empty($image_url) && !is_wp_error($image_path)) {
            @unlink($image_path); // Delete the downloaded image file if it was used.
        }

        header('Content-Description: File Transfer');
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . basename($temp_image_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($temp_image_path));
        readfile($temp_image_path);

        @unlink($temp_image_path); // Delete the temporary image file.

        exit;
    }
}

add_action('admin_init', 'mono96_mk_eyecatch_process_image');
