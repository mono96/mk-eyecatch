<?php
/**
 * Plugin Name: Mono96 MK Eyecatch
 * Description: 管理画面で指定したテキストを画像の真ん中に追加し、直接ダウンロードするプラグイン。各入力欄は20文字まで。
 * Version: 1.0
 * Author: Mono96
 */

if (!defined('WPINC')) {
    die;
}

function mono96_mk_eyecatch_menu() {
    add_menu_page('Mono96 MK Eyecatch', 'MK Eyecatch', 'manage_options', 'mono96-mk-eyecatch', 'mono96_mk_eyecatch_options_page');
}
add_action('admin_menu', 'mono96_mk_eyecatch_menu');

function mono96_mk_eyecatch_options_page() {
    ?>
    <div class="wrap">
        <h2>Mono96 MK Eyecatch</h2>
        <form method="post" enctype="multipart/form-data">
            <?php
            // エラーメッセージの表示
            if (isset($_GET['error'])) {
                ?>
                <div class="error">
                    <p><?php echo esc_html($_GET['error']); ?></p>
                </div>
                <?php
            }
            ?>
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
            <?php submit_button('画像を生成'); ?>
        </form>
    </div>
    <?php
}

function mono96_mk_eyecatch_process_image() {
    if (isset($_POST['mono96_mk_eyecatch_text1']) || isset($_POST['mono96_mk_eyecatch_text2']) || isset($_POST['mono96_mk_eyecatch_text3'])) {
        $texts = [
            sanitize_text_field($_POST['mono96_mk_eyecatch_text1']),
            sanitize_text_field($_POST['mono96_mk_eyecatch_text2']),
            sanitize_text_field($_POST['mono96_mk_eyecatch_text3'])
        ];

        // 文字数チェック（日本語対応）
        foreach ($texts as $index => $text) {
            if (mb_strlen($text) > 20) {
                $over = mb_strlen($text) - 20;
                wp_redirect(add_query_arg('error', urlencode("テキスト" . ($index + 1) . "が20文字を超えています。{$over}文字削除してください。"), menu_page_url('mono96-mk-eyecatch', false)));
                exit;
            }
        }

        $plugin_dir_path = plugin_dir_path(__FILE__);
        $image_path = $plugin_dir_path . 'eye2024.jpg'; // 画像のファイル名を指定
        $font_path = $plugin_dir_path . 'NotoSansJP-VariableFont_wght.ttf'; // フォントのファイル名を指定

        $image = imagecreatefromjpeg($image_path);
        $color = imagecolorallocate($image, 0, 0, 0);

        $fontSize = 60; // フォントサイズ
        $angle = 0;
        $y = (imagesy($image) / 2) - ((count($texts) - 1) * $fontSize) / 2; // 中央揃えの計算

        foreach ($texts as $text) {
            if (!empty($text)) {
                $textBox = imagettfbbox($fontSize, $angle, $font_path, $text);
                $textWidth = $textBox[2] - $textBox[0];
                $x = (imagesx($image) - $textWidth) / 2;
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $font_path, $text);
                $y += $fontSize * 1.5; // 次の行のY座標
            }
        }

        // 一時ファイルに画像を保存してダウンロード
        $temp_image_path = tempnam(sys_get_temp_dir(), 'mk_eyecatch') . '.jpg';
        imagejpeg($image, $temp_image_path);
        imagedestroy($image);

        header('Content-Description: File Transfer');
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . basename($temp_image_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($temp_image_path));
        readfile($temp_image_path);

        // 一時ファイルを削除
        unlink($temp_image_path);

        exit;
    }
}

add_action('admin_init', 'mono96_mk_eyecatch_process_image');
