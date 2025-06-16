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
        
        // Thêm popup vào footer
        add_action('wp_footer', array($this, 'render_signature_popup'));
        
        // Thêm modal tạo hóa đơn vào footer
        add_action('wp_footer', array($this, 'render_create_invoice_modal'));
    }
    
    /**
     * Thêm scripts và styles


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
            <div class="p-4 sm:p-6 bg-gradient-to-r from-blue-500 to-blue-700">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white"><?php echo esc_html__('Danh sách hóa đơn', 'direct-image-signature'); ?></h2>
                        <p class="text-blue-100 mt-2 text-sm sm:text-base"><?php echo esc_html__('Quản lý và theo dõi tất cả hóa đơn của bạn', 'direct-image-signature'); ?></p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                        <button id="createInvoiceBtn" class="bg-white text-blue-600 px-3 sm:px-4 py-2 rounded-md hover:bg-blue-50 transition-colors flex items-center justify-center w-full sm:w-auto text-sm sm:text-base">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <?php echo esc_html__('Tạo hóa đơn', 'direct-image-signature'); ?>
                        </button>
                        
                        <div class="relative w-full sm:w-auto">
                            <button id="userDropdownBtn" class="flex items-center justify-center sm:justify-start space-x-2 text-white hover:text-blue-100 focus:outline-none w-full sm:w-auto p-2 sm:p-0 bg-blue-600 sm:bg-transparent rounded-md sm:rounded-none">
                                <span class="font-medium text-sm sm:text-base"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-full sm:w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-center sm:text-left">
                                    <?php echo esc_html__('Đăng xuất', 'direct-image-signature'); ?>
                                </a>
                            </div>
                        </div>
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
                <div class="bg-gray-100 px-2 sm:px-4">
                    <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto scrollbar-hide">
                        <a href="?tab=pending" class="<?php echo $active_tab === 'pending' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="truncate"><?php echo esc_html__('Hóa đơn chờ ký', 'direct-image-signature'); ?></span>
                        </a>
                        <a href="?tab=signed" class="<?php echo $active_tab === 'signed' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="truncate"><?php echo esc_html__('Hóa đơn đã ký', 'direct-image-signature'); ?></span>
                        </a>
                        <a href="?tab=my" class="<?php echo $active_tab === 'my' ? 'border-b-2 border-blue-500 text-blue-600 font-medium' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="truncate"><?php echo esc_html__('Hóa đơn đã hoàn thành', 'direct-image-signature'); ?></span>
                        </a>
                    </nav>
                </div>
                
                <div class="tab-content p-3 sm:p-6">
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
                            <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-lg mb-2" title="<?php the_title_attribute(); ?>">
                                        <?php the_title(); ?>
                                    </h4>
                                    <?php if (!empty($excerpt)): ?>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo esc_html($excerpt); ?></p>
                                    <?php endif; ?>
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center text-sm text-gray-500 gap-4">
                                        <span class="flex items-center whitespace-nowrap">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <?php echo get_the_date(); ?>
                                        </span>
                                        <span class="flex items-center w-full">
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
                                                echo '<div class="mt-4 space-y-4 w-full">';
                                                echo '<div class="text-sm font-medium text-gray-700">Danh sách người ký:</div>';
                                                echo '<div class="flex flex-wrap gap-2">';
                                                
                                                foreach ($signature_users as $signer_id) {
                                                    $user_info = get_userdata($signer_id);
                                                    if ($user_info) {
                                                        $has_signed = in_array($signer_id, $signed_users);
                                                        $current_user = $signer_id == get_current_user_id();
                                                        
                                                        echo '<div class="flex-shrink-0 flex items-center justify-between bg-gray-50 p-2 rounded-lg ' . ($current_user ? 'border border-blue-200' : '') . ' min-w-[200px] max-w-full">';
                                                        
                                                        // Tên người ký và trạng thái
                                                        echo '<div class="flex items-center gap-2 flex-1 min-w-0">';
                                                        if ($has_signed) {
                                                            echo '<svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                                            echo '<span class="text-sm text-gray-700 truncate">' . esc_html($user_info->display_name) . ' <span class="text-green-600 text-xs whitespace-nowrap">(Đã ký)</span></span>';
                                                        } else {
                                                            echo '<svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                            echo '<span class="text-sm text-gray-700 truncate">' . esc_html($user_info->display_name) . ' <span class="text-yellow-600 text-xs whitespace-nowrap">(Chưa ký)</span></span>';
                                                        }
                                                        echo '</div>';
                                                        
                                                        // Thời gian ký (nếu đã ký)
                                                        if ($has_signed) {
                                                            $signed_info = get_post_meta($invoice_id, '_dis_signed_info', true);
                                                            if (isset($signed_info[$signer_id]['time'])) {
                                                                $sign_time = new DateTime($signed_info[$signer_id]['time']);
                                                                echo '<span class="text-xs text-gray-500 ml-2 whitespace-nowrap">' . $sign_time->format('d/m/Y H:i') . '</span>';
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
                                                
                                                echo '<div class="w-full mt-4">';
                                                echo '<div class="flex justify-between text-xs text-gray-600 mb-1">';
                                                echo '<span>Tiến độ ký: ' . $total_signed . '/' . $total_signers . '</span>';
                                                echo '<span>' . round($progress) . '%</span>';
                                                echo '</div>';
                                                echo '<div class="w-full bg-gray-200 rounded-full h-2">';
                                                echo '<div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: ' . $progress . '%"></div>';
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
                                   
                                    <?php if (!empty($list_img)): ?>
                                        <div class="flex flex-row space-x-2">
                                            <?php if ($user_status === 'pending'): ?>
                                                <a href="javascript:void(0);" 
                                                   class="dis-sign-button inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium min-w-[120px] justify-center"
                                                   data-invoice-id="<?php echo esc_attr($invoice_id); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                    <?php echo esc_html__('Ký', 'direct-image-signature'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="javascript:void(0);" 
                                               class="dis-view-button inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors text-sm font-medium min-w-[120px] justify-center"
                                               data-invoice-id="<?php echo esc_attr($invoice_id); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <?php echo esc_html__('Xem hóa đơn', 'direct-image-signature'); ?>
                                            </a>
                                        </div>
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

    /**
     * Render modal tạo hóa đơn
     */
    public function render_create_invoice_modal() {
        if (!is_user_logged_in()) return;
        ?>
        <!-- Modal tạo hóa đơn -->
        <div id="createInvoiceModal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 overflow-hidden relative">
                <div class="p-4 border-b flex justify-between items-center bg-blue-600 text-white">
                    <h3 class="text-xl font-semibold"><?php echo esc_html__('Tạo hóa đơn mới', 'direct-image-signature'); ?></h3>
                    <button id="closeInvoiceModal" class="text-white hover:text-blue-200 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="create_invoice">
                    <?php wp_nonce_field('create_invoice_nonce', 'create_invoice_nonce'); ?>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="invoice_title" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo esc_html__('Tiêu đề hóa đơn', 'direct-image-signature'); ?> *
                            </label>
                            <input type="text" id="invoice_title" name="invoice_title" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="invoice_images" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo esc_html__('Hình ảnh hóa đơn', 'direct-image-signature'); ?> *
                            </label>
                            <div class="flex flex-col space-y-2">
                                <div class="relative">
                                    <input type="file" id="invoice_images" name="invoice_images[]" multiple required accept="image/*"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div id="image_preview" class="grid grid-cols-4 gap-4 mt-2"></div>
                            </div>
                        </div>
                        
                        <div>
    <label for="invoice_position" class="block text-sm font-medium text-gray-700 mb-1">
        Lọc theo chức vụ
    </label>
    <select id="invoice_position" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
        <option value="">Tất cả chức vụ</option>

        <?php 
        // Lấy toàn bộ user
        $users = get_users(['number' => -1]);
        $positions = [];

        foreach ($users as $user) {
            $user_id = $user->ID;
            $chuc_vu = get_field('chuc_vu', 'user_' . $user_id);
            if (!empty($chuc_vu)) {
                $positions[$chuc_vu] = true;
            }
        }
        $positions = array_keys($positions);

        foreach ($positions as $position) {
            echo '<option value="'.esc_attr($position).'">'.esc_html($position).'</option>';
        }
        ?>
    </select>
</div>

<select id="invoice_signers" name="invoice_signers[]" multiple="" required="" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
    <?php 
    // In toàn bộ user
    foreach ($users as $user) {
        $user_id = $user->ID;
        $user_name = $user->display_name;
        $chuc_vu = get_field('chuc_vu', 'user_' . $user_id);
        echo '<option value="'.esc_attr($user_id).'" data-position="'.esc_attr($chuc_vu).'">'.$user_name.' ('.esc_html($chuc_vu).')</option>';
    }
    ?>
</select>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const positionSelect = document.getElementById('invoice_position');
    const signerSelect = document.getElementById('invoice_signers');
    const allOptions = Array.from(signerSelect.options); // lưu toàn bộ option gốc

    positionSelect.addEventListener('change', function() {
        const selectedPosition = this.value;

        // Xóa toàn bộ option cũ
        signerSelect.innerHTML = '';

        // Lọc lại option
        allOptions.forEach(option => {
            if (selectedPosition === '' || option.dataset.position === selectedPosition) {
                signerSelect.appendChild(option);
            }
        });
    });
});
</script>

                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" id="cancelCreateInvoice" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            <?php echo esc_html__('Hủy', 'direct-image-signature'); ?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md">
                            <?php echo esc_html__('Tạo hóa đơn', 'direct-image-signature'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
 
    
}

// Khởi tạo lớp
new DIS_Invoice_List_Shortcode(); 