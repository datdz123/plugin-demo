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
        // Thêm shortcode hiển thị danh sách hóa đơn
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
            // Thêm Tailwind CSS từ CDN
            wp_enqueue_style('tailwind-css', 'https://unpkg.com/tailwindcss@^2.2.19/dist/tailwind.min.css', array(), null);
            
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
                array(),
                '4.0.0',
                true
            );
            
            // Thêm script xử lý ký
            wp_enqueue_script(
                'dis-signature',
                plugins_url('assets/js/signature.js', dirname(__FILE__)),
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-resizable', 'signature-pad', 'fancybox-js'),
                '1.0.0',
                true
            );
            
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
                    'draw_signature' => __('Vẽ chữ ký', 'direct-image-signature'),
                    'save_signatures' => __('Lưu chữ ký', 'direct-image-signature'),
                    'no_images' => __('Không có ảnh nào cho hóa đơn này', 'direct-image-signature')
                )
            ));
            
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
        ?>
        <div id="dis-signature-popup" class="dis-signature-popup" style="display: none;">
            <div class="min-h-screen px-4 py-8">
                <div class="relative max-w-6xl mx-auto bg-white rounded-xl shadow-2xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-gray-900" id="dis-popup-title">
                                <?php echo esc_html__('Ký hóa đơn', 'direct-image-signature'); ?>
                            </h3>
                            <button type="button" class="dis-close-popup text-gray-400 hover:text-gray-500">
                                <span class="sr-only"><?php echo esc_html__('Đóng', 'direct-image-signature'); ?></span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                        <div class="mb-4">
                            <p class="text-gray-600">
                                <?php echo esc_html__('Click vào vị trí bạn muốn ký trên hình ảnh để thêm chữ ký.', 'direct-image-signature'); ?>
                            </p>
                        </div>
                        
                        <!-- Image slider -->
                        <div id="dis-image-slider" class="mb-4">
                            <!-- Sẽ được điền bởi JavaScript -->
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="dis-close-popup inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <?php echo esc_html__('Hủy', 'direct-image-signature'); ?>
                            </button>
                            <button type="button" id="dis-save-signatures" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <?php echo esc_html__('Lưu chữ ký', 'direct-image-signature'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Hiển thị danh sách hóa đơn
     */
    public function render_invoice_list() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            return '<div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 mb-4">
                <p>' . __('Bạn cần đăng nhập để xem hóa đơn.', 'direct-image-signature') . ' <a href="' . esc_url(site_url('/login/')) . '" class="text-blue-600 font-bold hover:underline">' . __('Đăng nhập', 'direct-image-signature') . '</a></p>
            </div>';
        }
        
        ob_start();
        
        // Thêm Tailwind CSS trực tiếp vào trang
        echo '<link href="https://unpkg.com/tailwindcss@^2.2.19/dist/tailwind.min.css" rel="stylesheet">';
        
        $current_user_id = get_current_user_id();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
        
        ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-blue-500 to-blue-700">
                <h2 class="text-2xl font-bold text-white"><?php echo esc_html__('Danh sách hóa đơn', 'direct-image-signature'); ?></h2>
                <p class="text-blue-100 mt-2"><?php echo esc_html__('Quản lý và theo dõi tất cả hóa đơn của bạn', 'direct-image-signature'); ?></p>
            </div>
            
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
                            <?php echo esc_html__('Hóa đơn của tôi', 'direct-image-signature'); ?>
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
        <?php
        
        return ob_get_clean();
    }

    /**
     * Hiển thị hóa đơn chờ ký
     */
    private function render_pending_invoices() {
        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'pending',
                    'compare' => '='
                )
            )
        );
        
        $this->display_invoices($args, 'pending');
    }

    /**
     * Hiển thị hóa đơn đã ký
     */
    private function render_signed_invoices() {
        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_dis_invoice_status',
                    'value' => 'signed',
                    'compare' => '='
                )
            )
        );
        
        $this->display_invoices($args, 'signed');
    }

    /**
     * Hiển thị hóa đơn của tôi
     */
    private function render_my_invoices() {
        $args = array(
            'post_type' => 'dis_invoice',
            'posts_per_page' => -1,
            'author' => get_current_user_id()
        );
        
        $this->display_invoices($args, 'my');
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
                        <h3 class="text-lg font-semibold text-yellow-800"><?php echo esc_html__('Hóa đơn của tôi', 'direct-image-signature'); ?></h3>
                        <p class="text-yellow-700">
                            <?php echo esc_html__('Đây là danh sách các hóa đơn bạn đã tạo. Bạn có thể quản lý và theo dõi trạng thái của chúng.', 'direct-image-signature'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
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
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <?php echo get_the_author(); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <span class="px-3 py-1 rounded-full text-sm <?php echo $invoice_status === 'signed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $invoice_status === 'signed' ? esc_html__('Đã ký', 'direct-image-signature') : esc_html__('Chờ ký', 'direct-image-signature'); ?>
                                    </span>
                                   
                                    <?php if (!empty($list_img)): ?>
                                        <a href="javascript:void(0);" 
                                           class="dis-sign-button inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium"
                                           data-invoice-id="<?php echo esc_attr($invoice_id); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <?php echo esc_html__('Ký', 'direct-image-signature'); ?>
                                        </a>
                                        
                                        <script type="text/javascript">
                                            jQuery(document).ready(function($) {
                                                $('.dis-sign-button[data-invoice-id="<?php echo esc_attr($invoice_id); ?>"]').on('click', function() {
                                                    var images = <?php echo json_encode($list_img); ?>;
                                                    var imageLinks = [];
                                                    
                                                    // Chuẩn bị danh sách ảnh cho Fancybox
                                                    if (images && images.length > 0) {
                                                        images.forEach(function(img, index) {
                                                            imageLinks.push({
                                                                src: img.url,
                                                                caption: '<?php echo esc_js(get_the_title()); ?> - <?php echo esc_js(__('Trang', 'direct-image-signature')); ?> ' + (index + 1)
                                                            });
                                                        });
                                                        
                                                        // Mở Fancybox với danh sách ảnh
                                                        Fancybox.show(imageLinks, {
                                                            toolbar: {
                                                                display: [
                                                                    "counter",
                                                                    "close",
                                                                ]
                                                            },
                                                            on: {
                                                                ready: (fancybox) => {
                                                                    // Thêm toolbar tùy chỉnh
                                                                    if (!document.querySelector('.dis-signature-toolbar')) {
                                                                        const toolbar = document.createElement('div');
                                                                        toolbar.className = 'dis-signature-toolbar';
                                                                        toolbar.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; background: #1e293b; color: white; padding: 10px; display: flex; justify-content: space-between; z-index: 10000;';
                                                                        
                                                                        toolbar.innerHTML = `
                                                                            <div class="flex items-center space-x-4">
                                                                                <button class="ve-chu-ky bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Vẽ chữ ký</button>
                                                                                <button class="tai-anh-chu-ky bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Tải ảnh chữ ký</button>
                                                                                <button class="xoa-chu-ky bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Xóa chữ ký</button>
                                                                                <div class="flex items-center">
                                                                                    <span class="mr-2">Kích thước:</span>
                                                                                    <input type="range" min="50" max="200" value="100" class="kich-thuoc" style="width: 100px;">
                                                                                </div>
                                                                                <div class="flex items-center">
                                                                                    <span class="mr-2">Màu sắc:</span>
                                                                                    <input type="color" value="#000000" class="mau-sac">
                                                                                </div>
                                                                            </div>
                                                                            <div>
                                                                                <button class="luu-hinh-anh bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded" data-invoice-id="<?php echo esc_attr($invoice_id); ?>">Lưu hình ảnh</button>
                                                                                <button class="huy bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Hủy</button>
                                                                            </div>
                                                                        `;
                                                                        
                                                                        document.body.appendChild(toolbar);
                                                                        
                                                                        // Xử lý sự kiện cho các nút
                                                                        toolbar.querySelector('.ve-chu-ky').addEventListener('click', function() {
                                                                            alert('Chức năng vẽ chữ ký sẽ được thêm vào đây');
                                                                        });
                                                                        
                                                                        toolbar.querySelector('.tai-anh-chu-ky').addEventListener('click', function() {
                                                                            const input = document.createElement('input');
                                                                            input.type = 'file';
                                                                            input.accept = 'image/*';
                                                                            input.click();
                                                                            
                                                                            input.onchange = function(e) {
                                                                                const file = e.target.files[0];
                                                                                if (file) {
                                                                                    const reader = new FileReader();
                                                                                    reader.onload = function(e) {
                                                                                        alert('Chức năng tải ảnh chữ ký sẽ được thêm vào đây');
                                                                                    };
                                                                                    reader.readAsDataURL(file);
                                                                                }
                                                                            };
                                                                        });
                                                                        
                                                                        toolbar.querySelector('.huy').addEventListener('click', function() {
                                                                            Fancybox.close();
                                                                        });
                                                                        
                                                                        toolbar.querySelector('.luu-hinh-anh').addEventListener('click', function() {
                                                                            const invoiceId = this.getAttribute('data-invoice-id');
                                                                            alert('Đã lưu chữ ký cho hóa đơn #' + invoiceId);
                                                                            Fancybox.close();
                                                                        });
                                                                    }
                                                                },
                                                                destroy: () => {
                                                                    // Xóa toolbar khi đóng Fancybox
                                                                    const toolbar = document.querySelector('.dis-signature-toolbar');
                                                                    if (toolbar) {
                                                                        toolbar.remove();
                                                                    }
                                                                }
                                                            }
                                                        });
                                                    } else {
                                                        alert('<?php echo esc_js(__('Không có ảnh nào cho hóa đơn này', 'direct-image-signature')); ?>');
                                                    }
                                                });
                                            });
                                        </script>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url(get_permalink()); ?>" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium text-sm">
                                            <span><?php echo esc_html__('Xem chi tiết', 'direct-image-signature'); ?></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
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
        </div>
        <?php
    }
}

// Khởi tạo lớp
new DIS_Invoice_List_Shortcode(); 