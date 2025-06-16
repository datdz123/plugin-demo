<?php
/**
 * Lớp xử lý logic của hóa đơn
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp xử lý logic của hóa đơn
 */
class DIS_Invoice_Handler {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        // Xử lý tạo hóa đơn mới
        add_action('wp_ajax_dis_create_invoice', array($this, 'handle_create_invoice'));
        
        // Xử lý tải lên ảnh hóa đơn
        add_action('wp_ajax_dis_upload_invoice_image', array($this, 'handle_upload_invoice_image'));
        
        // Xử lý ký hóa đơn
        add_action('wp_ajax_dis_sign_invoice', array($this, 'handle_sign_invoice'));
        
        // Xử lý xóa hóa đơn
        add_action('wp_ajax_dis_delete_invoice', array($this, 'handle_delete_invoice'));
    }

    /**
     * Xử lý tạo hóa đơn mới
     */
    public function handle_create_invoice() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Bạn cần đăng nhập để tạo hóa đơn.', 'direct-image-signature'));
        }
        
        // Kiểm tra nonce
        if (!isset($_POST['dis_create_invoice_nonce']) || !wp_verify_nonce($_POST['dis_create_invoice_nonce'], 'dis_create_invoice_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['invoice_title'])) {
            wp_send_json_error(__('Vui lòng nhập tiêu đề hóa đơn.', 'direct-image-signature'));
        }
        
        if (empty($_FILES['invoice_images'])) {
            wp_send_json_error(__('Vui lòng tải lên ít nhất một ảnh hóa đơn.', 'direct-image-signature'));
        }
        
        // Xử lý tạo hóa đơn - Phần này sẽ được thay thế bằng post type tự tạo
        wp_send_json_success(array(
            'message' => __('Hóa đơn đã được tạo thành công.', 'direct-image-signature'),
            'redirect' => admin_url('admin.php?page=dis-invoices')
        ));
    }

    /**
     * Xử lý tải lên ảnh hóa đơn
     */
    public function handle_upload_invoice_image() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Bạn cần đăng nhập để tải lên ảnh hóa đơn.', 'direct-image-signature'));
        }
        
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_upload_invoice_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra file upload
        if (!isset($_FILES['invoice_image']) || empty($_FILES['invoice_image']['tmp_name'])) {
            wp_send_json_error(__('Không tìm thấy file upload!', 'direct-image-signature'));
        }
        
        // Kiểm tra loại file
        $file_type = wp_check_filetype(basename($_FILES['invoice_image']['name']));
        $allowed_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf');
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(__('Loại file không được hỗ trợ. Vui lòng upload file JPG, PNG hoặc PDF.', 'direct-image-signature'));
        }
        
        // Upload file vào thư viện media
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('invoice_image', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'url' => $image_url
        ));
    }

    /**
     * Xử lý ký hóa đơn
     */
    public function handle_sign_invoice() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Bạn cần đăng nhập để ký hóa đơn.', 'direct-image-signature'));
        }
        
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_sign_invoice_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['invoice_id'])) {
            wp_send_json_error(__('Không tìm thấy ID hóa đơn.', 'direct-image-signature'));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        $user_id = get_current_user_id();
        
        // Kiểm tra người dùng có chữ ký chưa
        $user_signature = DIS_User_Management::get_user_signature($user_id);
        if (empty($user_signature)) {
            wp_send_json_error(__('Bạn cần tạo chữ ký trước khi ký hóa đơn.', 'direct-image-signature'));
        }
        
        // Xử lý ký hóa đơn - Phần này sẽ được thay thế bằng post type tự tạo
        wp_send_json_success(array(
            'message' => __('Hóa đơn đã được ký thành công.', 'direct-image-signature'),
            'redirect' => admin_url('admin.php?page=dis-invoices')
        ));
    }

    /**
     * Xử lý xóa hóa đơn
     */
    public function handle_delete_invoice() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Bạn cần đăng nhập để xóa hóa đơn.', 'direct-image-signature'));
        }
        
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_delete_invoice_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['invoice_id'])) {
            wp_send_json_error(__('Không tìm thấy ID hóa đơn.', 'direct-image-signature'));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        $user_id = get_current_user_id();
        
        // Xử lý xóa hóa đơn - Phần này sẽ được thay thế bằng post type tự tạo
        wp_send_json_success(array(
            'message' => __('Hóa đơn đã được xóa thành công.', 'direct-image-signature')
        ));
    }
}

// Khởi tạo lớp
new DIS_Invoice_Handler(); 