<?php
/**
 * Cấu hình ACF cho plugin Direct Image Signature
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// 1. Thiết lập đường dẫn cho ACF
add_filter('acf/settings/path', 'dis_acf_settings_path');
function dis_acf_settings_path($path)
{
    $path = DIS_PLUGIN_DIR . 'includes/inc/acf/';
    return $path;
}

// 2. Thiết lập URL cho ACF
add_filter('acf/settings/dir', 'dis_acf_settings_dir');
function dis_acf_settings_dir($dir)
{
    $dir = DIS_PLUGIN_URL . 'includes/inc/acf/';
    return $dir;
}

// 3. Ẩn menu admin của ACF
add_filter('acf/settings/show_admin', 'dis_acf_show_admin');
function dis_acf_show_admin($show)
{
    // Chỉ hiển thị cho admin
    return current_user_can('manage_options');
}

// 4. Thiết lập điểm lưu JSON
add_filter('acf/settings/save_json', 'dis_acf_json_save_point');
function dis_acf_json_save_point($path)
{
    $path = DIS_PLUGIN_DIR . 'includes/acf-json';
    return $path;
}

// 5. Thiết lập điểm tải JSON
add_filter('acf/settings/load_json', 'dis_acf_json_load_point');
function dis_acf_json_load_point($paths)
{
    $paths[] = DIS_PLUGIN_DIR . 'includes/acf-json';
    return $paths;
}

// 6. Tải ACF
include_once(DIS_PLUGIN_DIR . 'includes/inc/acf/acf.php'); 