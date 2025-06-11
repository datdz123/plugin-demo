<?php
/**
 * Lớp tạo shortcode hiển thị danh sách hóa đơn
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp tạo shortcode hiển thị danh sách hóa đơn
 */
class DIS_Invoice_List_Shortcode {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        add_shortcode('dis_invoice_list', array($this, 'render_invoice_list'));
        
        // Thêm scripts và styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Thêm popup vào footer
        add_action('wp_footer', array($this, 'render_signature_popup'));
    }
    
    /**
     * Thêm scripts và styles
     */
    public function enqueue_scripts() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dis_invoice_list')) {
            error_log('DIS: Enqueuing scripts for invoice list shortcode - START');
            
            // Thêm Tailwind CSS từ CDN
            wp_enqueue_style('tailwind-css', 'https://unpkg.com/tailwindcss@^2.2.19/dist/tailwind.min.css', array(), null);
            
            // Thêm jQuery UI CSS
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2');
            
            // Thêm CSS cho chức năng ký
            wp_enqueue_style(
                'dis-signature-css',
                plugins_url('assets/css/signature.css', dirname(__FILE__)),
                array('jquery-ui'),
                filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/signature.css')
            );
            
            // Thêm jQuery UI cho chức năng kéo thả và resize
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-resizable');
            
            // Thêm thư viện Fancybox
            wp_enqueue_style('fancybox-css', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css', array(), '5.0.0');
            wp_enqueue_script('fancybox-js', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', array('jquery'), '5.0.0', true);
            
            // Thêm thư viện Signature Pad
            wp_enqueue_script(
                'signature-pad',
                'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js',
                array('jquery'),
                '4.0.0',
                false // Thay đổi từ true thành false để tải trong head thay vì footer
            );
            
            // Thêm script xử lý ký
            $timestamp = time(); // Sử dụng timestamp để tránh cache
            error_log('DIS: Enqueuing signature.js with timestamp: ' . $timestamp);
            
            wp_enqueue_script(
                'dis-signature',
                plugins_url('assets/js/signature.js', dirname(__FILE__)),
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-resizable', 'signature-pad', 'fancybox-js'),
                $timestamp,
                true
            );
            
            error_log('DIS: Localizing script with nonce and translations');
            
            // Localize script
            wp_localize_script('dis-signature', 'dis_signature', array(
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
                    'draw_signature' => __('Ký tại đây', 'direct-image-signature'),
                    'save_signatures' => __('Lưu chữ ký', 'direct-image-signature'),
                    'no_images' => __('Không có ảnh nào cho hóa đơn này', 'direct-image-signature'),
                    'scale_up' => __('Phóng to', 'direct-image-signature'),
                    'scale_down' => __('Thu nhỏ', 'direct-image-signature')
                )
            ));
            
            // Thêm inline script để kiểm tra thư viện SignaturePad
            wp_add_inline_script('signature-pad', 'console.log("SignaturePad library loaded in head");');
            
            // Thêm inline script để kiểm tra script signature.js
            wp_add_inline_script('dis-signature', 'console.log("Inline script check: signature.js should be loaded");');
            
            // Thêm style cho popup
            wp_add_inline_style('tailwind-css', '
                .dis-signature-container {
                    position: relative;
                }
                .dis-signature-item {
                    cursor: pointer;
                }
                .dis-signature-item .dis-remove-signature {
                    display: none;
                }
                .dis-signature-item:hover .dis-remove-signature {
                    display: flex;
                }
                #dis-signature-pad-wrapper {
                    cursor: move;
                }
                .dis-sign-button {
                    transition: all 0.3s ease;
                }
                .dis-sign-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .dis-upload-signature {
                    display: none;
                }
                .f-button.is-close-btn {
                    background-color: #f3f4f6;
                    color: #374151;
                }
                .f-button.is-close-btn:hover {
                    background-color: #e5e7eb;
                }
                .f-button.is-primary {
                    background-color: #2563eb;
                    color: white;
                }
                .f-button.is-primary:hover {
                    background-color: #1d4ed8;
                }
            ');
            
        }
    }

    /**
     * Render popup ký
     */
    public function render_signature_popup() {
        if (!is_user_logged_in()) return;
        
        // Không cần render popup vì sẽ sử dụng Fancybox
    }

    /**
     * Hiển thị danh sách hóa đơn
     */
    public function render_invoice_list() {
      
        ob_start();
        
        // Thêm Tailwind CSS trực tiếp vào trang
        echo '<link href="https://unpkg.com/tailwindcss@^2.2.19/dist/tailwind.min.css" rel="stylesheet">';
        
        $current_user_id = get_current_user_id();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
        
        ?>
        <div class="px-4 mx-auto"> 
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-blue-500 to-blue-700 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white"><?php echo esc_html__('Danh sách hóa đơn', 'direct-image-signature'); ?></h2>
                    <p class="text-blue-100 mt-2"><?php echo esc_html__('Quản lý và theo dõi tất cả hóa đơn của bạn', 'direct-image-signature'); ?></p>
                </div>
                
                <div class="relative">
                    <button id="userDropdownBtn" class="flex items-center space-x-2 text-white hover:text-blue-100 focus:outline-none">
                        <span class="font-medium"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <?php echo esc_html__('Đăng xuất', 'direct-image-signature'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#userDropdownBtn').click(function(e) {
                    e.stopPropagation();
                    $('#userDropdown').toggleClass('hidden');
                });

                $(document).click(function(e) {
                    if (!$(e.target).closest('#userDropdownBtn, #userDropdown').length) {
                        $('#userDropdown').addClass('hidden');
                    }
                });
            });
            </script>
            
            <!-- Tabs -->
            <div class="tabs-container">
                <div class="bg-gray-100 px-4">
                    <nav class="flex space-x-4 overflow-x-auto">
                        <a href="?tab=pending" class="<?php echo $active_tab === 'pending' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo esc_html__('Hóa đơn chờ ký', 'direct-image-signature'); ?>
                        </a>
                        <a href="?tab=signed" class="<?php echo $active_tab === 'signed' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <?php echo esc_html__('Hóa đơn đã ký', 'direct-image-signature'); ?>
                        </a>
                        <a href="?tab=my" class="<?php echo $active_tab === 'my' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <?php echo esc_html__('Hóa đơn đã hoàn thành ', 'direct-image-signature'); ?>
                        </a>
                    </nav>
                </div>
                
                <div class="tab-content p-6">
                    <?php
                    switch ($active_tab) {
                        case 'pending':
                            $this->render_pending_invoices();
                            break;
                        case 'signed':
                            $this->render_signed_invoices();
                            break;
                        case 'my':
                            $this->render_my_invoices();
                            break;
                        default:
                            $this->render_pending_invoices();
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Hiển thị hóa đơn chờ ký
     */
    private function render_pending_invoices() {
        $current_user = wp_get_current_user();
        $is_admin = in_array('administrator', $current_user->roles);
        $current_user_id = get_current_user_id();

        if (!$is_admin) {
            // User thường chỉ thấy hóa đơn họ được phân công ký và chưa ký
            $meta_query = array(
                'relation' => 'AND',
                // Được phân công ký
                array(
                    'key' => 'signature',
                    'value' => $current_user_id,
                    'compare' => 'LIKE'
                ),
                array(
                    'relation' => 'OR',
                    // Chưa có ai ký
                    array(
                        'key' => '_dis_signed_users',
                        'compare' => 'NOT EXISTS'
                    ),
                    // Hoặc user này chưa ký
                    array(
                        'key' => '_dis_signed_users',
                        'value' => $current_user_id,
                        'compare' => 'NOT LIKE'
                    )
                ),
                // Trạng thái là pending hoặc signed
                array(
                    'key' => '_dis_invoice_status',
                    'value' => array('pending', 'signed'),
                    'compare' => 'IN'
                )
            );
        } else {
            // Admin thấy tất cả hóa đơn chưa hoàn thành
            $meta_query = array(
                array(
                    'key' => '_dis_invoice_status',
                    'value' => array('pending', 'signed'),
                    'compare' => 'IN'
                )
            );
        }

        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'meta_query' => $meta_query
        );
        
        $this->display_invoices($args, 'pending');
    }

    /**
     * Hiển thị hóa đơn đã ký
     */
    private function render_signed_invoices() {
        $current_user = wp_get_current_user();
        $is_admin = in_array('administrator', $current_user->roles);
        $current_user_id = get_current_user_id();

        if (!$is_admin) {
            // User thường chỉ thấy hóa đơn họ đã ký
            $meta_query = array(
                'relation' => 'AND',
                // Được phân công ký
                array(
                    'key' => 'signature',
                    'value' => $current_user_id,
                    'compare' => 'LIKE'
                ),
                // Đã ký
                array(
                    'key' => '_dis_signed_users',
                    'value' => $current_user_id,
                    'compare' => 'LIKE'
                ),
                // Trạng thái hóa đơn là signed
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'signed',
                    'compare' => '='
                )
            );
        } else {
            // Admin thấy tất cả hóa đơn đã ký một phần
            $meta_query = array(
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'signed',
                    'compare' => '='
                )
            );
        }

        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'meta_query' => $meta_query
        );
        
        $this->display_invoices($args, 'signed');
    }

    /**
     * Hiển thị hóa đơn đã hoàn thành
     */
    private function render_my_invoices() {
        $current_user = wp_get_current_user();
        $is_admin = in_array('administrator', $current_user->roles);
        $current_user_id = get_current_user_id();

        if (!$is_admin) {
            // User thường chỉ thấy hóa đơn đã hoàn thành mà họ tham gia ký
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'signature',
                    'value' => serialize((string)$current_user_id),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'done',
                    'compare' => '='
                )
            );
        } else {
            // Admin thấy tất cả hóa đơn đã hoàn thành
            $meta_query = array(
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'done',
                    'compare' => '='
                )
            );
        }

        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'meta_query' => $meta_query
        );
        
        $this->display_invoices($args, 'done');
    }

    /**
     * Hiển thị danh sách hóa đơn theo query
     * 
     * @param array $args WP_Query arguments
     * @param string $tab Tab hiện tại
     */
    private function display_invoices($args, $tab) {
        $invoices = new WP_Query($args);
        ?>
        <div class="tab-pane" id="<?php echo esc_attr($tab); ?>">
            <?php if ($tab === 'pending'): ?>
                <div class="flex items-center p-4 mb-6 bg-blue-50 border-l-4 border-blue-500 rounded-md">
                    <div class="mr-4 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800"><?php echo esc_html__('Hóa đơn chờ ký', 'direct-image-signature'); ?></h3>
                        <p class="text-blue-700">
                            <?php echo esc_html__('Đây là danh sách các hóa đơn cần được ký. Bạn có thể xem chi tiết và ký hóa đơn.', 'direct-image-signature'); ?>
                        </p>
                    </div>
                </div>
            <?php elseif ($tab === 'signed'): ?>
                <div class="flex items-center p-4 mb-6 bg-green-50 border-l-4 border-green-500 rounded-md">
                    <div class="mr-4 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-green-800"><?php echo esc_html__('Hóa đơn đã ký', 'direct-image-signature'); ?></h3>
                        <p class="text-green-700">
                            <?php echo esc_html__('Đây là danh sách các hóa đơn đã được ký. Bạn có thể xem chi tiết và tải xuống.', 'direct-image-signature'); ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex items-center p-4 mb-6 bg-yellow-50 border-l-4 border-yellow-500 rounded-md">
                    <div class="mr-4 text-yellow-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800"><?php echo esc_html__('Hóa đơn đã hoàn thành ', 'direct-image-signature'); ?></h3>
                        <p class="text-yellow-700">
                            <?php echo esc_html__('Đây là danh sách các hóa đơn bạn đã tạo. Bạn có thể quản lý và theo dõi trạng thái của chúng.', 'direct-image-signature'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(!is_user_logged_in()): ?>
              <div class="flex flex-col items-center justify-center p-8 text-center bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-600 text-lg"><?php echo esc_html__('Bạn cần đăng nhập để xem hóa đơn.', 'direct-image-signature'); ?></p>
                    <a href="#" class="dis-login-link text-blue-600 font-bold hover:underline"><?php echo esc_html__('Đăng nhập', 'direct-image-signature'); ?></a>
                </div>
            <?php else: ?>
           
            <?php if ($invoices->have_posts()): ?>
                <div class="space-y-4">
                    <?php while ($invoices->have_posts()): $invoices->the_post(); 
                        $invoice_id = get_the_ID();
                        $invoice_status = get_post_meta($invoice_id, '_dis_invoice_status', true);
                        $invoice_description = get_post_meta($invoice_id, '_dis_invoice_description', true);
                        $excerpt = !empty($invoice_description) ? wp_trim_words($invoice_description, 10) : '';
                        $list_img = get_field('list_img', $invoice_id); // Lấy danh sách ảnh từ ACF
                    ?>
                        <div class="bg-white border rounded-lg p-4 hover:shadow-md transition-shadow duration-300">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg mb-1" title="<?php the_title_attribute(); ?>">
                                        <?php the_title(); ?>
                                    </h4>
                                    <?php if (!empty($excerpt)): ?>
                                        <p class="text-gray-600 text-sm mb-2"><?php echo esc_html($excerpt); ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <?php echo get_the_date(); ?>
                                        </span>
                                        <span class="flex items-center">
                                            <?php 
                                            $signature_users = get_field('signature', $invoice_id);
                                            $signed_users = get_post_meta($invoice_id, '_dis_signed_users', true);
                                            $invoice_status = get_post_meta($invoice_id, '_dis_invoice_status', true);
                                            $current_user_id = get_current_user_id();
                                            
                                            if (!is_array($signature_users)) {
                                                $signature_users = array($signature_users);
                                            }
                                            $signature_users = array_filter($signature_users); // Loại bỏ giá trị null/rỗng
                                            
                                            if (!is_array($signed_users)) {
                                                $signed_users = array();
                                            }

                                            if (!empty($signature_users)) {
                                                echo '<div class="flex flex-col space-y-2 mt-2">';
                                                echo '<div class="text-sm font-medium text-gray-700">Danh sách người ký:</div>';
                                                echo '<div class="grid grid-cols-1 gap-2">';
                                                
                                                foreach ($signature_users as $signer_id) {
                                                    $user_info = get_userdata($signer_id);
                                                    if ($user_info) {
                                                        $has_signed = in_array($signer_id, $signed_users);
                                                        $current_user = $signer_id == get_current_user_id();
                                                        
                                                        echo '<div class="flex items-center justify-between bg-gray-50 p-2 rounded-lg ' . ($current_user ? 'border border-blue-200' : '') . '">';
                                                        
                                                        // Tên người ký và trạng thái
                                                        echo '<div class="flex items-center gap-2">';
                                                        if ($has_signed) {
                                                            echo '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                                            echo '<span class="text-sm text-gray-700">' . esc_html($user_info->display_name) . ' <span class="text-green-600 text-xs">(Đã ký)</span></span>';
                                                        } else {
                                                            echo '<svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                            echo '<span class="text-sm text-gray-700">' . esc_html($user_info->display_name) . ' <span class="text-yellow-600 text-xs">(Chưa ký)</span></span>';
                                                        }
                                                        echo '</div>';
                                                        
                                                        // Thời gian ký (nếu đã ký)
                                                        if ($has_signed) {
                                                            $signed_info = get_post_meta($invoice_id, '_dis_signed_info', true);
                                                            if (isset($signed_info[$signer_id]['time'])) {
                                                                $sign_time = new DateTime($signed_info[$signer_id]['time']);
                                                                echo '<span class="text-xs text-gray-500">' . $sign_time->format('d/m/Y H:i') . '</span>';
                                                            }
                                                        }
                                                        
                                                        echo '</div>';
                                                    }
                                                }
                                                
                                                echo '</div>';
                                                
                                                // Hiển thị tiến độ ký
                                                $total_signers = count($signature_users);
                                                $total_signed = count($signed_users);
                                                $progress = ($total_signed / $total_signers) * 100;
                                                
                                                echo '<div class="mt-2">';
                                                echo '<div class="flex justify-between text-xs text-gray-600 mb-1">';
                                                echo '<span>Tiến độ ký: ' . $total_signed . '/' . $total_signers . '</span>';
                                                echo '<span>' . round($progress) . '%</span>';
                                                echo '</div>';
                                                echo '<div class="w-full bg-gray-200 rounded-full h-2">';
                                                echo '<div class="bg-blue-600 h-2 rounded-full" style="width: ' . $progress . '%"></div>';
                                                echo '</div>';
                                                echo '</div>';
                                                
                                                echo '</div>';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <?php
                                    // Xác định trạng thái cho người dùng hiện tại
                                    $user_status = in_array($current_user_id, $signed_users) ? 'signed' : 'pending';
                                    $status_class = $user_status === 'signed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                    $status_text = $user_status === 'signed' ? esc_html__('Đã ký', 'direct-image-signature') : esc_html__('Chờ ký', 'direct-image-signature');
                                    
                                    if ($invoice_status === 'done') {
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        $status_text = esc_html__('Hoàn thành', 'direct-image-signature');
                                    }
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-sm <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                   
                                    <?php if (!empty($list_img) && $user_status === 'pending'): ?>
                                        <a href="javascript:void(0);" 
                                           class="dis-sign-button inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium"
                                           data-invoice-id="<?php echo esc_attr($invoice_id); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <?php echo esc_html__('Ký', 'direct-image-signature'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-8 text-center bg-gray-50 rounded-lg border border-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-600 text-lg"><?php echo esc_html__('Không có hóa đơn nào.', 'direct-image-signature'); ?></p>
                    <p class="text-gray-500 mt-2"><?php echo esc_html__('Hóa đơn sẽ xuất hiện ở đây sau khi được tạo.', 'direct-image-signature'); ?></p>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
 
    
}

// Khởi tạo lớp
new DIS_Invoice_List_Shortcode(); 