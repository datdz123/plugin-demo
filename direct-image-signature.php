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
    
    global $post;
    
    $has_invoice_list_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dis_invoice_list');
    
    
    // Thêm Tailwind CSS từ CDN
    wp_enqueue_style('tailwind-css', 'https://cdn.tailwindcss.com', array(), '3.3.5');
    
    // Style chính
    wp_enqueue_style('dis-style', DIS_PLUGIN_URL . 'assets/css/direct-image-signature.css', array(), DIS_VERSION);
    
    // Thêm Fancybox CSS
    wp_enqueue_style('fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css', array(), '5.0.0');
    
    // Thêm jQuery nếu chưa có
    wp_enqueue_script('jquery');
    
    // Thêm jQuery UI cho chức năng kéo thả
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-resizable');
    
    // Thêm Fabric.js (thư viện để vẽ chữ ký)
    wp_enqueue_script('fabric-js', 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js', array('jquery'), '5.3.1', false);
    
    // Thêm Fancybox JS
    wp_enqueue_script('fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', array('jquery'), '5.0.0', false);
    
    // Thêm EXIF.js để xử lý hướng của ảnh
    wp_enqueue_script('exif-js', 'https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js', array('jquery'), '2.3.0', false);
    
    // Thêm SignaturePad.js
    wp_enqueue_script('signature-pad-js', 'https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js', array('jquery'), '4.1.5', false);
    
    // JS chính
    wp_enqueue_script('signature-script', DIS_PLUGIN_URL . 'assets/js/signature.js', array('jquery', 'fabric-js', 'fancybox-js', 'exif-js', 'signature-pad-js'), DIS_VERSION, true);
    
    // Thêm script xử lý đăng nhập/đăng ký
    wp_enqueue_script('auth-script', DIS_PLUGIN_URL . 'assets/js/auth.js', array('jquery', 'sweetalert2-js'), DIS_VERSION, true);
    
    // Thêm SweetAlert2 CSS
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
 
    // Thêm SweetAlert2 JS
    wp_enqueue_script('sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array('jquery'), '11.0.0', true);
    
    // Localize script cho signature.js
    wp_localize_script('signature-script', 'dis_signature', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dis_signature_nonce'),
        'i18n' => array(
            'page' => __('Trang', 'direct-image-signature'),
            'sign_here' => __('Ký tại đây', 'direct-image-signature'),
            'apply' => __('Áp dụng', 'direct-image-signature'),
            'clear' => __('Xóa', 'direct-image-signature'),
            'cancel' => __('Hủy', 'direct-image-signature'),
            'empty_signature' => __('Vui lòng ký trước khi áp dụng', 'direct-image-signature'),
            'no_signatures' => __('Vui lòng ký ít nhất một chữ ký trước khi lưu', 'direct-image-signature'),
            'error' => __('Đã xảy ra lỗi. Vui lòng thử lại.', 'direct-image-signature'),
            'invoice_sign' => __('Ký hóa đơn', 'direct-image-signature'),
            'upload_signature' => __('Tải lên chữ ký', 'direct-image-signature'),
            'draw_signature' => __('Vẽ chữ ký', 'direct-image-signature'),
            'save_signatures' => __('Lưu chữ ký', 'direct-image-signature'),
            'no_images' => __('Không có ảnh nào cho hóa đơn này', 'direct-image-signature'),
            'scale_up' => __('Phóng to', 'direct-image-signature'),
            'scale_down' => __('Thu nhỏ', 'direct-image-signature')
        )
    ));
    
    // Localize script cho auth.js
    wp_localize_script('auth-script', 'dis_auth', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dis_signature_nonce'),
        'i18n' => array(
            'login_success' => __('Đăng nhập thành công', 'direct-image-signature'),
            'register_success' => __('Đăng ký thành công', 'direct-image-signature'),
            'redirecting' => __('Đang chuyển hướng...', 'direct-image-signature'),
            'login_error' => __('Lỗi đăng nhập', 'direct-image-signature'),
            'register_error' => __('Lỗi đăng ký', 'direct-image-signature'),
            'system_error' => __('Đã xảy ra lỗi', 'direct-image-signature'),
            'try_again' => __('Vui lòng thử lại sau', 'direct-image-signature'),
            'close' => __('Đóng', 'direct-image-signature'),
            'try_again_btn' => __('Thử lại', 'direct-image-signature')
        )
    ));
    wp_enqueue_script('create-invoice-js', DIS_PLUGIN_URL . 'assets/js/create-invoice.js', array('jquery'), DIS_VERSION, true);
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
require_once(DIS_PLUGIN_DIR . 'includes/class-ajax-handler.php');

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

// Xử lý AJAX để lấy danh sách ảnh của hóa đơn
function dis_get_invoice_images() {
    // Kiểm tra nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
        wp_send_json_error('Lỗi bảo mật!');
    }

    // Kiểm tra invoice_id
    if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
        wp_send_json_error('Thiếu ID hóa đơn!');
    }

    $invoice_id = intval($_POST['invoice_id']);
    $invoice = get_post($invoice_id);

    if (!$invoice || $invoice->post_type !== 'dis_invoice') {
        wp_send_json_error('Không tìm thấy hóa đơn!');
    }

    // Lấy danh sách ảnh từ ACF
    $images = get_field('list_img', $invoice_id);
    if (!$images || empty($images)) {
        wp_send_json_error('Không có ảnh nào cho hóa đơn này!');
    }

    wp_send_json_success(array(
        'images' => $images,
        'title' => get_the_title($invoice_id)
    ));
}
add_action('wp_ajax_dis_get_invoice_images', 'dis_get_invoice_images');
add_action('wp_ajax_nopriv_dis_get_invoice_images', 'dis_get_invoice_images');

// Hàm gửi mail cho người ký tiếp theo
function send_invoice_email_to_next_signer($invoice_id, $signed_users, $assigned_signers) {
   

    // Đảm bảo các mảng là mảng và không rỗng
    if (!is_array($assigned_signers)) {
        $assigned_signers = array($assigned_signers);
    }
    if (!is_array($signed_users)) {
        $signed_users = array();
    }
    
    // Loại bỏ các giá trị null hoặc rỗng
    $assigned_signers = array_filter($assigned_signers);
    $signed_users = array_filter($signed_users);

    // Tìm người ký tiếp theo trong danh sách theo thứ tự
    foreach ($assigned_signers as $signer_id) {
        if (!in_array($signer_id, $signed_users)) {
            send_invoice_email_to_user($signer_id, $invoice_id);
            return;
        }
    }
    
}

// Hàm gửi mail xác nhận cho người vừa ký
function send_signer_confirmation_email($user_id, $invoice_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return;

    // Lấy nội dung template từ Options Page
    $subject = get_field('invoice_signed_email_subject', 'option');
    if (empty($subject)) {
        $subject = 'Bạn đã hoàn thành việc ký hoá đơn';
    }
    
    $body_template = get_field('invoice_signed_email_body', 'option');
    if (empty($body_template)) {
        $body_template = "Xin chào {customer_name},\n\nBạn vừa hoàn tất ký hoá đơn: {invoice_title}.\n\nCảm ơn bạn!";
    }

    // Thay thế biến
    $variables = array(
        '{customer_name}' => $user->display_name,
        '{invoice_title}' => get_the_title($invoice_id)
    );
    $body = strtr($body_template, $variables);

    // Gửi mail
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($user->user_email, $subject, nl2br($body), $headers);
}

// Xử lý AJAX để lưu chữ ký
function dis_save_signatures() {
    // Kiểm tra nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
        wp_send_json_error(array('message' => __('Lỗi bảo mật!', 'direct-image-signature')));
        return;
    }

    // Kiểm tra dữ liệu
    if (!isset($_POST['invoice_id'])) {
        wp_send_json_error(array('message' => __('Thiếu ID hóa đơn.', 'direct-image-signature')));
        return;
    }
    
    if (!isset($_POST['merged_images'])) {
        wp_send_json_error(array('message' => __('Thiếu dữ liệu hình ảnh đã ghép.', 'direct-image-signature')));
        return;
    }

    $invoice_id = intval($_POST['invoice_id']);
    $merged_images = $_POST['merged_images'];
    $user_id = get_current_user_id();

    // Lấy danh sách người ký được phân công
    $assigned_signers = get_field('signature', $invoice_id);
    if (!is_array($assigned_signers)) {
        $assigned_signers = array($assigned_signers);
    }
    // Loại bỏ các giá trị null hoặc rỗng
    $assigned_signers = array_filter($assigned_signers);

    // Kiểm tra quyền ký
    if (!in_array($user_id, $assigned_signers)) {
        wp_send_json_error(array('message' => __('Bạn không được phân quyền ký hóa đơn này.', 'direct-image-signature')));
        return;
    }

    // Lấy danh sách người đã ký
    $signed_users = get_post_meta($invoice_id, '_dis_signed_users', true);
    if (!is_array($signed_users)) {
        $signed_users = array();
    }

    // Thêm người dùng hiện tại vào danh sách đã ký
    if (!in_array($user_id, $signed_users)) {
        $signed_users[] = $user_id;
        
        // Gửi mail xác nhận cho người vừa ký
        send_signer_confirmation_email($user_id, $invoice_id);
    }

    // Cập nhật danh sách người đã ký
    update_post_meta($invoice_id, '_dis_signed_users', $signed_users);

    // Xác định trạng thái mới
    $total_signers = count($assigned_signers);
    $total_signed = count($signed_users);

    if ($total_signed === 0) {
        $new_status = 'pending';
    } elseif ($total_signers === 1 || $total_signed === $total_signers) {
        // Nếu chỉ có 1 người ký hoặc tất cả đã ký -> done
        $new_status = 'done';
    } else {
        // Có nhiều người ký và chưa ký đủ -> signed
        $new_status = 'signed';
        
        // Gửi mail cho người ký tiếp theo
        send_invoice_email_to_next_signer($invoice_id, $signed_users, $assigned_signers);
    }
  

    // Nếu dữ liệu là chuỗi JSON, chuyển đổi thành mảng
    if (is_string($merged_images)) {
        $merged_images = json_decode(stripslashes($merged_images), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Lỗi xử lý dữ liệu hình ảnh đã ghép.', 'direct-image-signature')));
            return;
        }
    }
    
    // Lưu các ảnh đã ghép
    $signed_images = array();
    
    if (!is_array($merged_images) || empty($merged_images)) {
        wp_send_json_error(array('message' => __('Không có dữ liệu hình ảnh đã ghép hợp lệ.', 'direct-image-signature')));
        return;
    }
    
    // Xử lý từng trang
    foreach ($merged_images as $page_index => $merged_image_data) {
        // Bỏ qua nếu không có dữ liệu
        if (empty($merged_image_data)) continue;
        
        // Lưu hình ảnh đã ghép
        $merged_image_id = save_signed_image($merged_image_data, $invoice_id, $page_index);
        
        // Thêm ID của hình ảnh vào mảng signed_images
        if ($merged_image_id) {
            $signed_images[] = $merged_image_id;
        } else {
            wp_send_json_error(array('message' => __('Không thể lưu hình ảnh đã ghép cho trang ' . ($page_index + 1), 'direct-image-signature')));
            return;
        }
    }

    if (empty($signed_images)) {
        wp_send_json_error(array('message' => __('Không thể lưu hình ảnh đã ghép.', 'direct-image-signature')));
        return;
    }

    // Cập nhật danh sách ảnh của hóa đơn với ảnh mới nhất
    $current_list_img = get_field('list_img', $invoice_id);
    if (!is_array($current_list_img)) {
        $current_list_img = array();
    }

    // Cập nhật từng ảnh đã ký
    foreach ($signed_images as $index => $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
            // Cập nhật hoặc thêm mới ảnh đã ký
            if (isset($current_list_img[$index])) {
                $current_list_img[$index] = array(
                    'ID' => $image_id,
                    'url' => $image_url,
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                );
            } else {
                $current_list_img[] = array(
                    'ID' => $image_id,
                    'url' => $image_url,
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                );
            }
        }
    }

    // Cập nhật lại trường list_img với danh sách ảnh mới
    update_field('list_img', $current_list_img, $invoice_id);

    // Cập nhật trạng thái hóa đơn
    update_post_meta($invoice_id, '_dis_invoice_status', $new_status);

    $signed_info = get_post_meta($invoice_id, '_dis_signed_info', true);
    if (!is_array($signed_info)) {
        $signed_info = array();
    }
    $signed_info[$user_id] = array(
        'time' => current_time('mysql'),
        'images' => $signed_images,
        'total_signers' => $total_signers,
        'total_signed' => $total_signed
    );
    update_post_meta($invoice_id, '_dis_signed_info', $signed_info);

    wp_send_json_success(array(
        'message' => __('Đã lưu chữ ký thành công.', 'direct-image-signature'),
        'reload' => true,
        'status' => $new_status,
        'total_signers' => $total_signers,
        'total_signed' => $total_signed
    ));
}

// Hàm lưu ảnh chữ ký từ data URL
function save_signature_image($data_url) {
    // Kiểm tra data URL
    if (strpos($data_url, 'data:image/') !== 0) {
        return false;
    }
    
    // Tách dữ liệu từ data URL
    $parts = explode(',', $data_url);
    if (count($parts) !== 2) {
        return false;
    }
    
    try {
        // Lấy loại file
        preg_match('/data:image\/(.*);base64/', $parts[0], $matches);
        if (empty($matches) || count($matches) < 2) {
            return false;
        }
        
        $image_type = $matches[1];
        
        // Giải mã dữ liệu base64
        $image_data = base64_decode($parts[1]);
        if (!$image_data) {
            return false;
        }
        
        // Tạo tên file
        $filename = 'signature-' . time() . '-' . mt_rand(1000, 9999) . '.' . $image_type;
        
        // Lưu file tạm thời
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/' . $filename;
        $bytes_written = file_put_contents($temp_file, $image_data);
        
        if ($bytes_written === false) {
            return false;
        }
        
        // Tạo attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $temp_file);
        
        if (is_wp_error($attach_id)) {
            return false;
        }
        
        // Cập nhật metadata cho attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $temp_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    } catch (Exception $e) {
        return false;
    }
}

// Hàm lưu hình ảnh đã ghép chữ ký
function save_signed_image($data_url, $invoice_id, $page_index) {
    // Kiểm tra data URL
    if (strpos($data_url, 'data:image/') !== 0) {
        return false;
    }
    
    // Tách dữ liệu từ data URL
    $parts = explode(',', $data_url);
    if (count($parts) !== 2) {
        return false;
    }
    
    try {
        // Lấy loại file
        preg_match('/data:image\/(.*);base64/', $parts[0], $matches);
        if (empty($matches) || count($matches) < 2) {
            return false;
        }
        
        $image_type = $matches[1];
        
        // Giải mã dữ liệu base64
        $image_data = base64_decode($parts[1]);
        if (!$image_data) {
            return false;
        }
        
        // Tạo tên file
        $filename = 'signed-image-' . $invoice_id . '-' . $page_index . '-' . time() . '.' . $image_type;
        
        // Lưu file tạm thời
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/' . $filename;
        $bytes_written = file_put_contents($temp_file, $image_data);
        
        if ($bytes_written === false) {
            return false;
        }
        
        // Tạo attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => sprintf(__('Signed Image - Invoice #%d - Page %d', 'direct-image-signature'), $invoice_id, $page_index + 1),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $temp_file);
        
        if (is_wp_error($attach_id)) {
            return false;
        }
        
        // Cập nhật metadata cho attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $temp_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    } catch (Exception $e) {
        return false;
    }
}

// Xử lý AJAX đăng nhập

// Đăng ký hooks cho AJAX
add_action('wp_ajax_dis_save_signatures', 'dis_save_signatures');
add_action('wp_ajax_nopriv_dis_save_signatures', 'dis_save_signatures');

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

// Hook khi hóa đơn được tạo
function dis_send_first_signer_email($post_id, $post) {
    // Chỉ xử lý cho post type dis_invoice và status publish
    if ($post->post_type !== 'dis_invoice' || $post->post_status !== 'publish') {
        return;
    }

    // Kiểm tra xem đã gửi mail chưa để tránh gửi lại
    $mail_sent = get_post_meta($post_id, '_dis_first_mail_sent', true);
    if ($mail_sent) {
        return;
    }

    // Lấy danh sách người ký được phân công
    $assigned_signers = get_field('signature', $post_id);
    if (!is_array($assigned_signers)) {
        $assigned_signers = array($assigned_signers);
    }
    $assigned_signers = array_filter($assigned_signers);

    if (empty($assigned_signers)) {
        return;
    }
    // Nếu có người ký, gửi mail cho người đầu tiên
    if (!empty($assigned_signers)) {
        $first_signer = reset($assigned_signers);
        send_invoice_email_to_user($first_signer, $post_id);
        
        // Đánh dấu đã gửi mail
        update_post_meta($post_id, '_dis_first_mail_sent', true);
    }
}

// Thêm hook cho cả khi post được tạo mới và khi update
add_action('wp_insert_post', 'dis_send_first_signer_email', 10, 2);
add_action('post_updated', 'dis_send_first_signer_email', 10, 2);

// Hàm gửi mail cho người ký
function send_invoice_email_to_user($user_id, $invoice_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) return;

    // Lấy link trang chủ với tab pending
    $home_url = home_url('/?tab=pending');
    

    // Lấy nội dung template từ Options Page
    $subject = get_field('invoice_email_subject', 'option');
    $body_template = get_field('invoice_email_body', 'option');

    // Thay thế biến
    $variables = array(
        '{customer_name}' => $user->display_name,
        '{invoice_link}'  => $home_url,
        '{invoice_title}' => get_the_title($invoice_id)
    );
    $body = strtr($body_template, $variables);

    // Gửi mail
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($user->user_email, $subject, nl2br($body), $headers);
}

// Xử lý tạo hóa đơn
function handle_create_invoice() {
    if (!isset($_POST['create_invoice_nonce']) || !wp_verify_nonce($_POST['create_invoice_nonce'], 'create_invoice_nonce')) {
        wp_die('Lỗi bảo mật!');
    }

    if (!is_user_logged_in()) {
        wp_die('Bạn cần đăng nhập để thực hiện chức năng này!');
    }

    // Kiểm tra dữ liệu
    if (empty($_POST['invoice_title'])) {
        wp_die('Vui lòng nhập tiêu đề hóa đơn!');
    }

    if (empty($_FILES['invoice_images'])) {
        wp_die('Vui lòng chọn ít nhất một ảnh hóa đơn!');
    }

    if (empty($_POST['invoice_signers'])) {
        wp_die('Vui lòng chọn ít nhất một người ký!');
    }

    // Tạo hóa đơn mới
    $invoice_data = array(
        'post_title'    => sanitize_text_field($_POST['invoice_title']),
        'post_content'  => sanitize_textarea_field($_POST['invoice_description']),
        'post_status'   => 'publish',
        'post_type'     => 'dis_invoice'
    );

    $invoice_id = wp_insert_post($invoice_data);

    if (is_wp_error($invoice_id)) {
        wp_die('Không thể tạo hóa đơn. Vui lòng thử lại!');
    }

    // Xử lý upload ảnh
    $uploaded_images = array();
    $files = $_FILES['invoice_images'];
    
    foreach ($files['name'] as $key => $value) {
        if ($files['error'][$key] === 0) {
            $file = array(
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key]
            );

            $_FILES = array('upload_file' => $file);
            $attachment_id = media_handle_upload('upload_file', $invoice_id);

            if (!is_wp_error($attachment_id)) {
                $uploaded_images[] = array(
                    'ID'  => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id)
                );
            }
        }
    }

    // Lưu ảnh vào ACF
    update_field('list_img', $uploaded_images, $invoice_id);

    // Lưu người ký
    update_field('signature', $_POST['invoice_signers'], $invoice_id);

    // Lưu trạng thái
    update_post_meta($invoice_id, '_dis_invoice_status', 'pending');

    // Gửi email cho người ký đầu tiên
    $first_signer = reset($_POST['invoice_signers']);
    if ($first_signer) {
        send_invoice_email_to_user($first_signer, $invoice_id);
        update_post_meta($invoice_id, '_dis_first_mail_sent', true);
    }

    // Chuyển hướng về trang danh sách hóa đơn
    wp_redirect(home_url('/?tab=pending'));
    exit;
}
add_action('admin_post_create_invoice', 'handle_create_invoice'); 