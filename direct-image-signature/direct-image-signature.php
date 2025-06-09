<?php
/**
 * Plugin Name: Direct Image Signature
 * Plugin URI: 
 * Description: Cho phép người dùng ký trực tiếp lên bất kỳ hình ảnh nào trên trang web.
 * Version: 1.0.2
 * Author: Admin
 * Author URI: 
 * Text Domain: direct-image-signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Định nghĩa hằng số
define('DIS_VERSION', '1.0.2');
define('DIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DIS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Thêm scripts và styles
function dis_enqueue_scripts() {
    // Chỉ tải trên frontend
    if (is_admin()) {
        return;
    }
    
    // Thêm Tailwind CSS từ CDN
    wp_enqueue_style('tailwind-css', 'https://cdn.tailwindcss.com', array(), '3.3.5');
    
    // Style chính
    wp_enqueue_style('dis-style', DIS_PLUGIN_URL . 'assets/css/direct-image-signature.css', array(), DIS_VERSION);
    
    // Thêm Fancybox CSS
    wp_enqueue_style('fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css', array(), '5.0.0');
    
    // Thêm Fabric.js (thư viện để vẽ chữ ký)
    wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js', array(), '5.3.1', true);
    
    // Thêm jQuery nếu chưa có
    wp_enqueue_script('jquery');
    
    // Thêm Fancybox JS
    wp_enqueue_script('fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', array('jquery'), '5.0.0', true);
    
    // Thêm EXIF.js để xử lý hướng của ảnh
    wp_enqueue_script('exif-js', 'https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js', array('jquery'), '2.3.0', true);
    
    // Thêm SignaturePad.js
    wp_enqueue_script('signature-pad-js', 'https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js', array(), '4.1.5', true);
    
    // JS chính
    wp_enqueue_script('dis-script', DIS_PLUGIN_URL . 'assets/js/direct-image-signature.js', array('jquery', 'fabric-js', 'fancybox-js', 'exif-js', 'signature-pad-js'), DIS_VERSION, true);
    
    wp_localize_script('dis-script', 'dis_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dis_nonce'),
        'plugin_url' => DIS_PLUGIN_URL,
        'is_admin' => is_admin() ? 'true' : 'false',
        'debug' => defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'
    ));
}
add_action('wp_enqueue_scripts', 'dis_enqueue_scripts');

// Nạp các tệp lớp
require_once(DIS_PLUGIN_DIR . 'includes/class-user-management.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-invoice-management.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-invoice-handler.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-invoice-tabs.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-invoice-post-type.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-invoice-list-shortcode.php');
require_once(DIS_PLUGIN_DIR . 'includes/class-image-sign-shortcode.php');

// Nạp cấu hình ACF
require_once(DIS_PLUGIN_DIR . 'includes/acf-config.php');

// Xử lý AJAX để upload ảnh chữ ký
function dis_upload_signature() {
    // Kiểm tra nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_nonce')) {
        wp_send_json_error('Lỗi bảo mật!');
    }

    // Kiểm tra file upload
    if (!isset($_FILES['signature_image']) || empty($_FILES['signature_image']['tmp_name'])) {
        wp_send_json_error('Không tìm thấy file upload!');
    }

    // Kiểm tra loại file
    $file_type = wp_check_filetype(basename($_FILES['signature_image']['name']));
    $allowed_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif');
    
    if (!in_array($file_type['type'], $allowed_types)) {
        wp_send_json_error('Loại file không được hỗ trợ. Vui lòng upload file JPG, PNG hoặc GIF.');
    }

    // Tạo thư mục temp nếu chưa có
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/dis-temp';
    
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    // Tạo tên file tạm thời
    $temp_filename = 'temp-signature-' . time() . '.' . $file_type['ext'];
    $temp_file_path = $temp_dir . '/' . $temp_filename;
    $temp_file_url = $upload_dir['baseurl'] . '/dis-temp/' . $temp_filename;

    // Di chuyển file từ thư mục tạm sang thư mục đích
    if (move_uploaded_file($_FILES['signature_image']['tmp_name'], $temp_file_path)) {
        wp_send_json_success(array(
            'url' => $temp_file_url
        ));
    } else {
        wp_send_json_error('Không thể upload file. Vui lòng thử lại sau.');
    }
}
add_action('wp_ajax_dis_upload_signature', 'dis_upload_signature');
add_action('wp_ajax_nopriv_dis_upload_signature', 'dis_upload_signature');

// Xử lý AJAX để lưu hình ảnh
function dis_save_image() {
    // Kiểm tra nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_nonce')) {
        wp_send_json_error('Lỗi bảo mật!');
    }

    if (!isset($_POST['image_data']) || empty($_POST['image_data'])) {
        wp_send_json_error('Không tìm thấy dữ liệu hình ảnh!');
    }

    // Lấy dữ liệu hình ảnh từ base64
    $image_data = $_POST['image_data'];
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_data = base64_decode($image_data);

    if (!$image_data) {
        wp_send_json_error('Không thể đọc dữ liệu hình ảnh!');
    }

    // Tạo tên file
    $filename = 'signed-image-' . time() . '.png';

    // Lưu vào thư viện media
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['path'] . '/' . $filename;
    $upload_url = $upload_dir['url'] . '/' . $filename;

    // Lưu file
    $result = file_put_contents($upload_path, $image_data);
    
    if ($result === false) {
        wp_send_json_error('Không thể lưu hình ảnh! Kiểm tra quyền ghi vào thư mục uploads.');
    }

    // Tạo attachment trong WordPress
    $attachment = array(
        'post_mime_type' => 'image/png',
        'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
        'post_content' => 'Hình ảnh đã được ký với plugin Direct Image Signature',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $upload_path);

    if (is_wp_error($attach_id)) {
        wp_send_json_error('Không thể tạo attachment: ' . $attach_id->get_error_message());
    }

    // Cập nhật metadata cho attachment
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    wp_send_json_success(array(
        'attachment_id' => $attach_id,
        'url' => $upload_url
    ));
}
add_action('wp_ajax_dis_save_image', 'dis_save_image');
add_action('wp_ajax_nopriv_dis_save_image', 'dis_save_image');

// Tạo thư mục và file cần thiết khi kích hoạt plugin
function dis_create_files_and_folders() {
    // Tạo thư mục temp trong uploads
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/dis-temp';
    
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
        
        // Tạo file index.php để bảo vệ thư mục
        $index_file = $temp_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    // Tạo thư mục templates nếu chưa có
    $templates_dir = DIS_PLUGIN_DIR . 'templates';
    if (!file_exists($templates_dir)) {
        wp_mkdir_p($templates_dir);
    }
    
    // Tạo thư mục acf-json nếu chưa có
    $acf_json_dir = DIS_PLUGIN_DIR . 'includes/acf-json';
    if (!file_exists($acf_json_dir)) {
        wp_mkdir_p($acf_json_dir);
        
        // Tạo file index.php để bảo vệ thư mục
        $index_file = $acf_json_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
}
register_activation_hook(__FILE__, 'dis_create_files_and_folders');

// Khởi tạo plugin
function dis_init() {
    // Tạo thư mục temp nếu chưa có
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/dis-temp';
    
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    // Đăng ký shortcode cho trang đăng nhập/đăng ký
    if (!shortcode_exists('dis_login_form')) {
        add_shortcode('dis_login_form', array('DIS_User_Management', 'render_login_form'));
    }
    
    if (!shortcode_exists('dis_register_form')) {
        add_shortcode('dis_register_form', array('DIS_User_Management', 'render_register_form'));
    }
    
    // Đăng ký shortcode cho dashboard hóa đơn
    if (!shortcode_exists('dis_invoice_dashboard')) {
        add_shortcode('dis_invoice_dashboard', array('DIS_Invoice_Management', 'render_invoice_dashboard'));
    }
    
    // Đăng ký shortcode mới cho danh sách hóa đơn
    if (!shortcode_exists('dis_invoice_list')) {
        add_shortcode('dis_invoice_list', array('DIS_Invoice_List_Shortcode', 'render_invoice_list'));
    }
    
    // Đăng ký shortcode mới cho chức năng ký ảnh
    if (!shortcode_exists('dis_image_sign')) {
        add_shortcode('dis_image_sign', array('DIS_Image_Sign_Shortcode', 'render_image_sign_tool'));
    }
}
add_action('init', 'dis_init');

// Thêm HTML và scripts vào footer
function dis_add_to_footer() {
    // Chỉ thêm vào frontend
    if (is_admin()) {
        return;
    }
    
    ?>
    <div id="dis-container" style="display: none;">
        <div class="dis-lightbox-toolbar bg-gray-800 p-4 flex flex-wrap items-center justify-center gap-4">
            <button id="dis-draw" class="dis-button bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active">Vẽ chữ ký</button>
            <button id="dis-upload" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Tải ảnh chữ ký</button>
            <button id="dis-clear" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Xóa chữ ký</button>
            <div class="dis-size-control flex items-center gap-2">
                <label for="dis-size" class="text-white">Kích thước:</label>
                <input type="range" id="dis-size" min="1" max="30" value="10" class="w-24">
            </div>
            <div class="dis-color-control flex items-center gap-2">
                <label for="dis-color" class="text-white">Màu sắc:</label>
                <input type="color" id="dis-color" value="#000000">
            </div>
            <button id="dis-save" class="dis-button dis-save bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Lưu hình ảnh</button>
            <button id="dis-cancel" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Hủy</button>
        </div>
        
        <div class="dis-upload-dialog fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[9999]" style="display: none;">
            <div class="dis-upload-dialog-content bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-xl font-bold mb-4 border-b pb-2">Tải lên ảnh chữ ký</h3>
                <form id="dis-upload-form" enctype="multipart/form-data">
                    <div class="dis-upload-field mb-4">
                        <label for="dis-signature-file" class="block mb-2 font-semibold">Chọn ảnh chữ ký (JPG, PNG, GIF):</label>
                        <input type="file" id="dis-signature-file" name="signature_image" accept="image/jpeg, image/png, image/gif" class="border p-2 w-full rounded">
                    </div>
                    <div class="dis-upload-preview p-4 border rounded bg-gray-50" style="display: none;">
                        <img id="dis-upload-preview-img" src="" alt="Xem trước" class="max-h-48 mx-auto">
                    </div>
                    <div class="dis-upload-buttons flex justify-end gap-3 mt-4">
                        <button type="button" id="dis-upload-submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Chèn vào ảnh</button>
                        <button type="button" id="dis-upload-cancel" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="dis-result-dialog fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[9999]" style="display: none;">
            <div class="dis-result-dialog-content bg-white rounded-lg p-6 max-w-lg w-full">
                <h3 class="text-xl font-bold mb-2">Hình ảnh đã được ký</h3>
                <p class="mb-4 text-gray-600">Bạn có thể tải xuống hoặc nhấn vào hình ảnh để xem.</p>
                <div class="dis-result-image p-4 border rounded bg-gray-50 mb-4 text-center">
                    <a href="" id="dis-result-fancy-link" data-fancybox="results">
                        <img id="dis-result-img" src="" alt="Hình ảnh đã ký" class="max-h-64 mx-auto">
                    </a>
                </div>
                <div class="dis-result-buttons flex justify-center gap-4">
                    <a href="" id="dis-result-link" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" download>Tải xuống</a>
                    <button id="dis-result-close" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Đóng</button>
                </div>
            </div>
        </div>
        
        <div class="dis-loading fixed inset-0 bg-black bg-opacity-70 flex flex-col items-center justify-center z-[9999]" style="display: none;">
            <div class="dis-spinner w-12 h-12 border-4 border-t-blue-500 border-blue-200 rounded-full animate-spin mb-4"></div>
            <p class="text-white">Đang xử lý...</p>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'dis_add_to_footer');

// Thêm link vào danh sách plugin
function dis_add_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('options-general.php?page=direct-image-signature') . '">Cài đặt</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dis_add_action_links');

// Xóa file tạm khi gỡ cài đặt plugin
function dis_uninstall() {
    // Xóa thư mục temp
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/dis-temp';
    
    if (file_exists($temp_dir)) {
        // Xóa toàn bộ file trong thư mục
        $files = glob($temp_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Xóa thư mục
        rmdir($temp_dir);
    }
    
    // Xóa thư mục acf-json
    $acf_json_dir = DIS_PLUGIN_DIR . 'includes/acf-json';
    if (file_exists($acf_json_dir)) {
        // Xóa toàn bộ file trong thư mục
        $files = glob($acf_json_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Xóa thư mục
        rmdir($acf_json_dir);
    }
}
register_uninstall_hook(__FILE__, 'dis_uninstall'); 