<?php
/**
 * Lớp xử lý các request AJAX
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp xử lý các request AJAX
 */
class DIS_Ajax_Handler {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        // Lấy chữ ký của user
        add_action('wp_ajax_dis_get_user_signature', array($this, 'get_user_signature'));
        
        // Upload chữ ký mới
        add_action('wp_ajax_dis_upload_signature', array($this, 'upload_signature'));
        
        // Lấy danh sách ảnh hóa đơn
        add_action('wp_ajax_dis_get_invoice_images', array($this, 'get_invoice_images'));
        
 
    }

    /**
     * Lấy chữ ký của user
     */
    public function get_user_signature() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật!', 'direct-image-signature'));
        }

        $user_id = get_current_user_id();
        $signature_id = get_user_meta($user_id, '_dis_signature_image', true);
        
        if ($signature_id) {
            $signature_url = wp_get_attachment_url($signature_id);
            wp_send_json_success(array(
                'signature_url' => $signature_url
            ));
        } else {
            wp_send_json_error(__('Chưa có chữ ký.', 'direct-image-signature'));
        }
    }

    /**
     * Upload chữ ký mới
     */
    public function upload_signature() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật!', 'direct-image-signature'));
        }

        // Kiểm tra file
        if (!isset($_FILES['signature'])) {
            wp_send_json_error(__('Không tìm thấy file.', 'direct-image-signature'));
        }

        $file = $_FILES['signature'];
        $user_id = get_current_user_id();

        // Kiểm tra loại file
        $allowed_types = array('image/jpeg', 'image/png');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Chỉ chấp nhận file JPG hoặc PNG.', 'direct-image-signature'));
        }

        // Upload file
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('signature', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        // Lưu ID của chữ ký mới
        update_user_meta($user_id, '_dis_signature_image', $attachment_id);

        // Xóa chữ ký cũ nếu có
        $old_signature_id = get_user_meta($user_id, '_dis_signature_image', true);
        if ($old_signature_id) {
            wp_delete_attachment($old_signature_id, true);
        }

        wp_send_json_success(array(
            'url' => wp_get_attachment_url($attachment_id)
        ));
    }

    /**
     * Lấy danh sách ảnh hóa đơn
     */
    public function get_invoice_images() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật!', 'direct-image-signature'));
        }

        // Kiểm tra invoice_id
        if (!isset($_POST['invoice_id'])) {
            wp_send_json_error(__('Thiếu thông tin hóa đơn.', 'direct-image-signature'));
        }

        $invoice_id = intval($_POST['invoice_id']);
        $list_img = get_field('list_img', $invoice_id);
        
        if (empty($list_img)) {
            wp_send_json_error(__('Không có ảnh nào cho hóa đơn này.', 'direct-image-signature'));
        }
        
        // Chuẩn bị dữ liệu ảnh
        $images = array();
        foreach ($list_img as $img) {
            $images[] = array(
                'url' => $img['url'],
                'width' => $img['width'],
                'height' => $img['height']
            );
        }
        
        wp_send_json_success(array(
            'title' => get_the_title($invoice_id),
            'images' => $images
        ));
    }

    
}

// Khởi tạo lớp
new DIS_Ajax_Handler(); 