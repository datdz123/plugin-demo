<?php
/**
 * Lớp quản lý hóa đơn cho plugin Direct Image Signature
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp xử lý quản lý hóa đơn
 */
class DIS_Invoice_Management {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        // Thêm shortcode hiển thị dashboard hóa đơn
        add_shortcode('dis_invoice_dashboard', array($this, 'render_invoice_dashboard'));
    }

    /**
     * Hiển thị dashboard hóa đơn
     */
    public function render_invoice_dashboard() {
        // Kiểm tra đăng nhập
        if (!is_user_logged_in()) {
            return '<p class="text-center py-4">' . __('Bạn cần đăng nhập để xem hóa đơn.', 'direct-image-signature') . ' <a href="' . esc_url(site_url('/login/')) . '" class="text-blue-600 hover:underline">' . __('Đăng nhập', 'direct-image-signature') . '</a></p>';
        }
        
        ob_start();
        
        $current_user_id = get_current_user_id();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
        
        ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-8 pb-2 border-b text-gray-800"><?php echo esc_html__('Quản lý hóa đơn', 'direct-image-signature'); ?></h2>
            
            <div class="flex flex-wrap gap-8 mb-10 pb-8 border-b">
                <!-- Phần chữ ký -->
                <div class="flex-1 min-w-[300px]">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo esc_html__('Chữ ký của bạn', 'direct-image-signature'); ?></h3>
                    <div class="signature-container">
                        <?php $current_signature = DIS_User_Management::get_user_signature(); ?>
                        <canvas id="dis-signature-pad" class="border border-gray-300 rounded-md w-full max-w-md h-48 mb-4 bg-white"></canvas>
                        <?php if (!empty($current_signature)) : ?>
                            <div class="mt-4 mb-4">
                                <p class="text-sm text-gray-600 mb-2"><?php echo esc_html__('Chữ ký hiện tại:', 'direct-image-signature'); ?></p>
                                <div class="p-4 border border-gray-200 bg-gray-50 rounded-md inline-block">
                                    <img src="<?php echo esc_attr($current_signature); ?>" alt="<?php echo esc_attr__('Chữ ký hiện tại', 'direct-image-signature'); ?>" class="max-h-24">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="flex gap-3 mt-4">
                            <button type="button" id="dis-clear-pad" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                                <?php echo esc_html__('Xóa', 'direct-image-signature'); ?>
                            </button>
                            <button type="button" id="dis-save-signature" class="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded-md transition-colors">
                                <?php echo esc_html__('Lưu chữ ký', 'direct-image-signature'); ?>
                            </button>
                        </div>
                        <div id="dis-signature-message" class="mt-3 p-3 rounded-md hidden"></div>
                    </div>
                </div>
                
                <!-- Form tải lên hóa đơn -->
                <div class="flex-1 min-w-[300px]">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo esc_html__('Tải lên hóa đơn mới', 'direct-image-signature'); ?></h3>
                    <form id="dis-upload-invoice-form" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="dis-invoice-title" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo esc_html__('Tiêu đề hóa đơn', 'direct-image-signature'); ?>
                            </label>
                            <input type="text" id="dis-invoice-title" name="invoice_title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="dis-invoice-description" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo esc_html__('Mô tả', 'direct-image-signature'); ?>
                            </label>
                            <textarea id="dis-invoice-description" name="invoice_description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        
                        <div>
                            <label for="dis-invoice-images" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo esc_html__('Ảnh hóa đơn (Có thể chọn nhiều ảnh)', 'direct-image-signature'); ?>
                            </label>
                            <input type="file" id="dis-invoice-images" name="invoice_images[]" accept="image/*" multiple required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <?php wp_nonce_field('dis_create_invoice_nonce', 'dis_create_invoice_nonce'); ?>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded-md transition-colors">
                                <?php echo esc_html__('Tạo hóa đơn', 'direct-image-signature'); ?>
                            </button>
                        </div>
                        
                        <div id="dis-upload-message" class="mt-3 p-3 rounded-md hidden"></div>
                    </form>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs-container">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="?tab=pending" class="<?php echo $active_tab === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm">
                            <?php echo esc_html__('Hóa đơn chờ ký', 'direct-image-signature'); ?>
                        </a>
                        <a href="?tab=signed" class="<?php echo $active_tab === 'signed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm">
                            <?php echo esc_html__('Hóa đơn đã ký', 'direct-image-signature'); ?>
                        </a>
                        <a href="?tab=my" class="<?php echo $active_tab === 'my' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm">
                            <?php echo esc_html__('Hóa đơn của tôi', 'direct-image-signature'); ?>
                        </a>
                    </nav>
                </div>
                
                <div class="tab-content py-6">
                    <?php
                    // Sử dụng lớp DIS_Invoice_Tabs để hiển thị nội dung tab
                    switch ($active_tab) {
                        case 'pending':
                            DIS_Invoice_Tabs::render_pending_invoices();
                            break;
                        case 'signed':
                            DIS_Invoice_Tabs::render_signed_invoices();
                            break;
                        case 'my':
                            DIS_Invoice_Tabs::render_my_invoices();
                            break;
                        default:
                            DIS_Invoice_Tabs::render_pending_invoices();
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Khởi tạo signature pad
            var canvas = document.getElementById('dis-signature-pad');
            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)'
            });
            
            // Xóa chữ ký
            $('#dis-clear-pad').on('click', function() {
                signaturePad.clear();
            });
            
            // Lưu chữ ký
            $('#dis-save-signature').on('click', function() {
                if (signaturePad.isEmpty()) {
                    $('#dis-signature-message')
                        .html('<?php echo esc_js(__('Vui lòng tạo chữ ký trước khi lưu.', 'direct-image-signature')); ?>')
                        .addClass('bg-red-100 text-red-700 border border-red-400')
                        .removeClass('hidden bg-green-100 text-green-700 border-green-400');
                    return;
                }
                
                var $button = $(this);
                var $message = $('#dis-signature-message');
                var buttonText = $button.text();
                
                $button.prop('disabled', true).text('<?php echo esc_js(__('Đang lưu...', 'direct-image-signature')); ?>');
                $message.html('').removeClass('bg-red-100 text-red-700 border-red-400 bg-green-100 text-green-700 border-green-400').addClass('hidden');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'dis_update_signature',
                        signature_data: signaturePad.toDataURL(),
                        nonce: '<?php echo wp_create_nonce('dis_update_signature_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html(response.data)
                                   .addClass('bg-green-100 text-green-700 border border-green-400 p-3 rounded')
                                   .removeClass('hidden');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
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
            
            // Tải lên hóa đơn mới
            $('#dis-upload-invoice-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('#dis-upload-message');
                var $button = $form.find('button[type="submit"]');
                var buttonText = $button.text();
                
                $message.html('').removeClass('bg-red-100 text-red-700 border-red-400 bg-green-100 text-green-700 border-green-400').addClass('hidden');
                $button.prop('disabled', true).text('<?php echo esc_js(__('Đang tải lên...', 'direct-image-signature')); ?>');
                
                var formData = new FormData(this);
                formData.append('action', 'dis_create_invoice');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $message.html(response.data.message)
                                   .addClass('bg-green-100 text-green-700 border border-green-400 p-3 rounded')
                                   .removeClass('hidden');
                            $form.trigger('reset');
                            setTimeout(function() {
                                window.location.href = response.data.redirect || '?tab=my';
                            }, 1500);
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
}

// Khởi tạo lớp
new DIS_Invoice_Management(); 