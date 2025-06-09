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

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        // Thêm shortcode cho form đăng nhập
        add_shortcode('dis_login_form', array($this, 'render_login_form'));
        
        // Thêm shortcode cho form đăng ký
        add_shortcode('dis_register_form', array($this, 'render_register_form'));
        
        // Xử lý đăng nhập
        add_action('wp_ajax_nopriv_dis_user_login', array($this, 'handle_login'));
        
        // Xử lý đăng ký
        add_action('wp_ajax_nopriv_dis_user_register', array($this, 'handle_register'));
        
        // Xử lý cập nhật chữ ký người dùng
        add_action('wp_ajax_dis_update_signature', array($this, 'update_user_signature'));
        
        // Thêm metadata chữ ký khi tạo người dùng mới
        add_action('user_register', array($this, 'setup_new_user'));
    }

    /**
     * Hiển thị form đăng nhập
     *
     * @return string HTML của form đăng nhập
     */
    public function render_login_form() {
        // Nếu đã đăng nhập, chuyển hướng đến trang hóa đơn
        if (is_user_logged_in()) {
            return '<script>window.location.href = "' . home_url('/signature-dashboard/') . '";</script>';
        }
        
        ob_start();
        ?>
        <div class="max-w-md mx-auto my-8 bg-white p-8 rounded-lg shadow-md">
            <form id="dis-login-form">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-800"><?php echo esc_html__('Đăng nhập', 'direct-image-signature'); ?></h2>
                
                <div id="dis-form-message" class="mb-4 p-3 rounded hidden"></div>
                
                <div class="mb-4">
                    <label for="dis-username" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Tên đăng nhập hoặc Email', 'direct-image-signature'); ?></label>
                    <input type="text" id="dis-username" name="username" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="dis-password" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Mật khẩu', 'direct-image-signature'); ?></label>
                    <input type="password" id="dis-password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="dis-remember" name="remember" class="h-4 w-4 text-blue-600 rounded">
                        <span class="ml-2 text-sm text-gray-700"><?php echo esc_html__('Ghi nhớ đăng nhập', 'direct-image-signature'); ?></span>
                    </label>
                </div>
                
                <div class="mb-6">
                    <?php wp_nonce_field('dis_login_nonce', 'dis_login_nonce'); ?>
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <?php echo esc_html__('Đăng nhập', 'direct-image-signature'); ?>
                    </button>
                </div>
                
                <div class="text-center text-sm text-gray-600">
                    <p class="mb-2">
                        <?php echo esc_html__('Chưa có tài khoản?', 'direct-image-signature'); ?>
                        <a href="<?php echo esc_url(site_url('/register/')); ?>" class="text-blue-600 hover:underline">
                            <?php echo esc_html__('Đăng ký', 'direct-image-signature'); ?>
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="text-blue-600 hover:underline">
                            <?php echo esc_html__('Quên mật khẩu?', 'direct-image-signature'); ?>
                        </a>
                    </p>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#dis-login-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('#dis-form-message');
                var $button = $form.find('button[type="submit"]');
                var buttonText = $button.text();
                
                $message.html('').removeClass('bg-red-100 text-red-700 border-red-400 bg-green-100 text-green-700 border-green-400').addClass('hidden');
                $button.prop('disabled', true).text('<?php echo esc_js(__('Đang đăng nhập...', 'direct-image-signature')); ?>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'dis_user_login',
                        username: $('#dis-username').val(),
                        password: $('#dis-password').val(),
                        remember: $('#dis-remember').is(':checked') ? 1 : 0,
                        nonce: $('#dis_login_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html(response.data.message)
                                   .addClass('bg-green-100 text-green-700 border border-green-400 p-3 rounded')
                                   .removeClass('hidden');
                            window.location.href = response.data.redirect;
                        } else {
                            $message.html(response.data)
                                   .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                                   .removeClass('hidden');
                            $button.prop('disabled', false).text(buttonText);
                        }
                    },
                    error: function() {
                        $message.html('<?php echo esc_js(__('Đã xảy ra lỗi. Vui lòng thử lại sau.', 'direct-image-signature')); ?>')
                               .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                               .removeClass('hidden');
                        $button.prop('disabled', false).text(buttonText);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Hiển thị form đăng ký
     *
     * @return string HTML của form đăng ký
     */
    public function render_register_form() {
        // Nếu đã đăng nhập, chuyển hướng đến trang hóa đơn
        if (is_user_logged_in()) {
            return '<script>window.location.href = "' . home_url('/signature-dashboard/') . '";</script>';
        }
        
        ob_start();
        ?>
        <div class="max-w-md mx-auto my-8 bg-white p-8 rounded-lg shadow-md">
            <form id="dis-register-form">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-800"><?php echo esc_html__('Đăng ký', 'direct-image-signature'); ?></h2>
                
                <div id="dis-form-message" class="mb-4 p-3 rounded hidden"></div>
                
                <div class="mb-4">
                    <label for="dis-reg-username" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Tên đăng nhập', 'direct-image-signature'); ?></label>
                    <input type="text" id="dis-reg-username" name="username" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="dis-reg-email" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Email', 'direct-image-signature'); ?></label>
                    <input type="email" id="dis-reg-email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="dis-reg-password" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Mật khẩu', 'direct-image-signature'); ?></label>
                    <input type="password" id="dis-reg-password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label for="dis-reg-confirm-password" class="block text-sm font-medium text-gray-700 mb-1"><?php echo esc_html__('Xác nhận mật khẩu', 'direct-image-signature'); ?></label>
                    <input type="password" id="dis-reg-confirm-password" name="confirm_password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <?php wp_nonce_field('dis_register_nonce', 'dis_register_nonce'); ?>
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <?php echo esc_html__('Đăng ký', 'direct-image-signature'); ?>
                    </button>
                </div>
                
                <div class="text-center text-sm text-gray-600">
                    <p>
                        <?php echo esc_html__('Đã có tài khoản?', 'direct-image-signature'); ?>
                        <a href="<?php echo esc_url(site_url('/login/')); ?>" class="text-blue-600 hover:underline">
                            <?php echo esc_html__('Đăng nhập', 'direct-image-signature'); ?>
                        </a>
                    </p>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#dis-register-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('#dis-form-message');
                var $button = $form.find('button[type="submit"]');
                var buttonText = $button.text();
                
                // Kiểm tra xác nhận mật khẩu
                if ($('#dis-reg-password').val() !== $('#dis-reg-confirm-password').val()) {
                    $message.html('<?php echo esc_js(__('Mật khẩu xác nhận không khớp.', 'direct-image-signature')); ?>')
                           .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                           .removeClass('hidden');
                    return;
                }
                
                $message.html('').removeClass('bg-red-100 text-red-700 border-red-400 bg-green-100 text-green-700 border-green-400').addClass('hidden');
                $button.prop('disabled', true).text('<?php echo esc_js(__('Đang đăng ký...', 'direct-image-signature')); ?>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'dis_user_register',
                        username: $('#dis-reg-username').val(),
                        email: $('#dis-reg-email').val(),
                        password: $('#dis-reg-password').val(),
                        nonce: $('#dis_register_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html(response.data.message)
                                   .addClass('bg-green-100 text-green-700 border border-green-400 p-3 rounded')
                                   .removeClass('hidden');
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else {
                            $message.html(response.data)
                                   .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                                   .removeClass('hidden');
                            $button.prop('disabled', false).text(buttonText);
                        }
                    },
                    error: function() {
                        $message.html('<?php echo esc_js(__('Đã xảy ra lỗi. Vui lòng thử lại sau.', 'direct-image-signature')); ?>')
                               .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                               .removeClass('hidden');
                        $button.prop('disabled', false).text(buttonText);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Xử lý đăng nhập người dùng
     */
    public function handle_login() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_login_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['username']) || empty($_POST['password'])) {
            wp_send_json_error(__('Vui lòng điền đầy đủ thông tin.', 'direct-image-signature'));
        }
        
        // Đăng nhập
        $credentials = array(
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['remember']) ? (bool) $_POST['remember'] : false
        );
        
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        } else {
            wp_send_json_success(array(
                'message'  => __('Đăng nhập thành công. Đang chuyển hướng...', 'direct-image-signature'),
                'redirect' => home_url('/signature-dashboard/')
            ));
        }
    }

    /**
     * Xử lý đăng ký người dùng
     */
    public function handle_register() {
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_register_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
            wp_send_json_error(__('Vui lòng điền đầy đủ thông tin.', 'direct-image-signature'));
        }
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        // Kiểm tra tên đăng nhập
        if (username_exists($username)) {
            wp_send_json_error(__('Tên đăng nhập này đã tồn tại.', 'direct-image-signature'));
        }
        
        // Kiểm tra email
        if (email_exists($email)) {
            wp_send_json_error(__('Email này đã được sử dụng.', 'direct-image-signature'));
        }
        
        // Tạo người dùng mới
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        } else {
            // Thiết lập vai trò
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
            
            // Gửi email thông báo
            wp_new_user_notification($user_id, null, 'user');
            
            // Đăng nhập người dùng mới
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_send_json_success(array(
                'message'  => __('Đăng ký thành công. Đang chuyển hướng...', 'direct-image-signature'),
                'redirect' => home_url('/signature-dashboard/')
            ));
        }
    }

    /**
     * Cập nhật chữ ký của người dùng
     */
    public function update_user_signature() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Bạn cần đăng nhập để cập nhật chữ ký.', 'direct-image-signature'));
        }
        
        // Kiểm tra nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dis_update_signature_nonce')) {
            wp_send_json_error(__('Lỗi bảo mật! Vui lòng tải lại trang.', 'direct-image-signature'));
        }
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($_POST['signature_data'])) {
            wp_send_json_error(__('Không tìm thấy dữ liệu chữ ký.', 'direct-image-signature'));
        }
        
        $user_id = get_current_user_id();
        $signature_data = $_POST['signature_data'];
        
        // Lưu chữ ký vào metadata của người dùng
        update_user_meta($user_id, 'dis_signature', $signature_data);
        
        wp_send_json_success(__('Chữ ký đã được cập nhật thành công.', 'direct-image-signature'));
    }

    /**
     * Thiết lập người dùng mới
     *
     * @param int $user_id ID của người dùng mới
     */
    public function setup_new_user($user_id) {
        // Khởi tạo metadata cho người dùng mới
        add_user_meta($user_id, 'dis_signature', '', true);
    }

    /**
     * Lấy chữ ký của người dùng
     *
     * @param int $user_id ID của người dùng (mặc định là người dùng hiện tại)
     * @return string URL hoặc dữ liệu base64 của chữ ký
     */
    public static function get_user_signature($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return get_user_meta($user_id, 'dis_signature', true);
    }
}

// Khởi tạo lớp
new DIS_User_Management(); 