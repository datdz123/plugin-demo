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
        
        // Tạo hóa đơn mới
        add_action('wp_ajax_dis_create_invoice', array($this, 'create_invoice'));
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

    /**
     * Tạo hóa đơn mới
     */
    public function create_invoice() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_create_invoice_nonce')) {
            wp_send_json_error(array('message' => __('Lỗi bảo mật!', 'direct-image-signature')));
            return;
        }

        // Kiểm tra dữ liệu
        if (empty($_POST['invoice_title'])) {
            wp_send_json_error(array('message' => __('Vui lòng nhập tiêu đề hóa đơn.', 'direct-image-signature')));
            return;
        }

        if (empty($_FILES['invoice_images']) || empty($_FILES['invoice_images']['name'][0])) {
            wp_send_json_error(array('message' => __('Vui lòng chọn ít nhất một ảnh hóa đơn.', 'direct-image-signature')));
            return;
        }

        if (empty($_POST['invoice_signers'])) {
            wp_send_json_error(array('message' => __('Vui lòng chọn ít nhất một người ký.', 'direct-image-signature')));
            return;
        }

        // Lấy thông tin từ form
        $title = sanitize_text_field($_POST['invoice_title']);
        $description = isset($_POST['invoice_description']) ? sanitize_textarea_field($_POST['invoice_description']) : '';
        $signers = array_map('intval', $_POST['invoice_signers']);

        // Tạo post type hóa đơn mới
        $invoice_data = array(
            'post_title'    => $title,
            'post_content'  => $description,
            'post_status'   => 'publish',
            'post_type'     => 'dis_invoice',
            'post_author'   => get_current_user_id()
        );

        // Chèn post và lấy ID
        $invoice_id = wp_insert_post($invoice_data);

        if (is_wp_error($invoice_id)) {
            wp_send_json_error(array('message' => $invoice_id->get_error_message()));
            return;
        }

        // Xử lý upload ảnh
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $list_img = array();
        $file_count = count($_FILES['invoice_images']['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['invoice_images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Tạo file array cho từng ảnh
            $file = array(
                'name'     => $_FILES['invoice_images']['name'][$i],
                'type'     => $_FILES['invoice_images']['type'][$i],
                'tmp_name' => $_FILES['invoice_images']['tmp_name'][$i],
                'error'    => $_FILES['invoice_images']['error'][$i],
                'size'     => $_FILES['invoice_images']['size'][$i]
            );

            $_FILES['invoice_image'] = $file;
            $attachment_id = media_handle_upload('invoice_image', $invoice_id);

            if (is_wp_error($attachment_id)) {
                continue;
            }

            $image_url = wp_get_attachment_url($attachment_id);
            $list_img[] = array(
                'ID'  => $attachment_id,
                'url' => $image_url,
                'alt' => $title . ' - ' . ($i + 1)
            );
        }

        // Lưu danh sách ảnh vào ACF field
        update_field('list_img', $list_img, $invoice_id);

        // Lưu danh sách người ký vào ACF field
        update_field('signature', $signers, $invoice_id);

        // Set trạng thái mặc định là pending
        update_post_meta($invoice_id, '_dis_invoice_status', 'pending');

        // Gửi email cho người ký đầu tiên
        $this->send_email_to_first_signer($invoice_id, $signers);

        // Trả về kết quả thành công
        wp_send_json_success(array(
            'message' => __('Tạo hóa đơn thành công!', 'direct-image-signature'),
            'invoice_id' => $invoice_id
        ));
    }

    /**
     * Gửi email cho người ký đầu tiên
     */
    private function send_email_to_first_signer($invoice_id, $signers) {
        if (empty($signers)) return;

        $first_signer_id = $signers[0];
        $user = get_user_by('id', $first_signer_id);
        
        if (!$user) return;

        // Lấy link trang chủ với tab pending
        $home_url = home_url('/?tab=pending');

        // Lấy nội dung template từ Options Page
        $subject = get_field('invoice_email_subject', 'option');
        if (empty($subject)) {
            $subject = __('Bạn có một hóa đơn cần ký', 'direct-image-signature');
        }
        
        $body_template = get_field('invoice_email_body', 'option');
        if (empty($body_template)) {
            $body_template = "Xin chào {customer_name},\n\nBạn có một hóa đơn mới cần ký: {invoice_title}.\n\nVui lòng truy cập vào đường dẫn sau để ký hóa đơn: {invoice_link}\n\nCảm ơn bạn!";
        }

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

        // Đánh dấu đã gửi mail
        update_post_meta($invoice_id, '_dis_first_mail_sent', true);
    }
}

// Khởi tạo lớp
new DIS_Ajax_Handler(); 