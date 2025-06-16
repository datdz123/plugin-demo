<?php
/**
 * Lớp quản lý người dùng cho plugin Direct Image Signature
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Lớp xử lý đăng nhập, đăng ký và quản lý người dùng
 */
class DIS_User_Management {
    public function __construct() {
        add_action('wp_ajax_dis_user_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_dis_user_login', array($this, 'handle_login'));
    
    
        add_action('wp_ajax_dis_user_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_dis_user_register', array($this, 'handle_register'));
    }
    
       /**
     * Xử lý đăng nhập người dùng
     */
    public function handle_login() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(__('Thiếu thông tin bảo mật!', 'direct-image-signature'));
            return;
        }

        // Kiểm tra nonce với cách linh hoạt hơn
        if (!wp_verify_nonce($_POST['nonce'], 'dis_auth_nonce') && !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
            return;
        }

        // Kiểm tra dữ liệu
        if (!isset($_POST['username']) || empty($_POST['username'])) {
            wp_send_json_error(__('Vui lòng nhập tên đăng nhập hoặc email.', 'direct-image-signature'));
            return;
        }

        if (!isset($_POST['password']) || empty($_POST['password'])) {
            wp_send_json_error(__('Vui lòng nhập mật khẩu.', 'direct-image-signature'));
            return;
        }

        // Lấy thông tin đăng nhập
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;

        // Xử lý đăng nhập
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ), is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
            return;
        }

        // Đăng nhập thành công
        wp_send_json_success(array(
            'message' => __('Đăng nhập thành công.', 'direct-image-signature'),
            'redirect' => home_url()
        ));
    }

    /**
     * Xử lý đăng ký người dùng
     */
    public function handle_register() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(__('Thiếu thông tin bảo mật!', 'direct-image-signature'));
            return;
        }

        // Kiểm tra nonce với cách linh hoạt hơn
        if (!wp_verify_nonce($_POST['nonce'], 'dis_auth_nonce') && !wp_verify_nonce($_POST['nonce'], 'dis_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
            return;
        }

        // Kiểm tra dữ liệu
        $errors = array();
        
        // Kiểm tra username
        if (!isset($_POST['username']) || empty($_POST['username'])) {
            $errors['username'] = __('Vui lòng nhập tên đăng nhập.', 'direct-image-signature');
        } elseif (username_exists(sanitize_user($_POST['username']))) {
            $errors['username'] = __('Tên đăng nhập đã tồn tại.', 'direct-image-signature');
        }
        
        // Kiểm tra email
        if (!isset($_POST['email']) || empty($_POST['email'])) {
            $errors['email'] = __('Vui lòng nhập địa chỉ email.', 'direct-image-signature');
        } elseif (!is_email($_POST['email'])) {
            $errors['email'] = __('Địa chỉ email không hợp lệ.', 'direct-image-signature');
        } elseif (email_exists($_POST['email'])) {
            $errors['email'] = __('Địa chỉ email đã được sử dụng.', 'direct-image-signature');
        }
        
        // Kiểm tra mật khẩu
        if (!isset($_POST['password']) || empty($_POST['password'])) {
            $errors['password'] = __('Vui lòng nhập mật khẩu.', 'direct-image-signature');
        } elseif (strlen($_POST['password']) < 8) {
            $errors['password'] = __('Mật khẩu phải có ít nhất 8 ký tự.', 'direct-image-signature');
        }
        
        // Kiểm tra xác nhận mật khẩu
        if (isset($_POST['confirm_password']) && $_POST['password'] !== $_POST['confirm_password']) {
            $errors['confirm_password'] = __('Mật khẩu xác nhận không khớp.', 'direct-image-signature');
        }
        
        // Nếu có lỗi, trả về thông báo lỗi
        if (!empty($errors)) {
            $error_messages = '';
            foreach ($errors as $error) {
                $error_messages .= '<li>' . $error . '</li>';
            }
            wp_send_json_error(array('message' => '<ul>' . $error_messages . '</ul>'));
            return;
        }
        
        // Lấy thông tin đăng ký
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        // Thông tin bổ sung
        $firstname = isset($_POST['firstname']) ? sanitize_text_field($_POST['firstname']) : '';
        $lastname = isset($_POST['lastname']) ? sanitize_text_field($_POST['lastname']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // Tạo người dùng mới
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }
        
        // Cập nhật thông tin bổ sung
        if (!empty($firstname)) {
            update_user_meta($user_id, 'first_name', $firstname);
        }
        
        if (!empty($lastname)) {
            update_user_meta($user_id, 'last_name', $lastname);
        }
        
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone', $phone);
        }
        
        // Đặt vai trò là Subscriber
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        // Đăng nhập tự động sau khi đăng ký
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Đăng ký thành công
        wp_send_json_success(array(
            'message' => __('Đăng ký thành công.', 'direct-image-signature'),
            'redirect' => home_url()
        ));
    }
}
 


// Khởi tạo lớp
new DIS_User_Management(); 