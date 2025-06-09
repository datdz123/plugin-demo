<?php
/**
 * Lớp hiển thị tabs cho hóa đơn
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp hiển thị tabs cho hóa đơn
 */
class DIS_Invoice_Tabs {

    /**
     * Hiển thị tab hóa đơn chờ ký
     */
    public static function render_pending_invoices() {
        ?>
        <div class="tab-pane" id="pending">
            <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo esc_html__('Hóa đơn chờ ký', 'direct-image-signature'); ?></h3>
            
            <div class="message-box p-4 bg-blue-50 border border-blue-200 rounded-md mb-6">
                <p class="text-blue-700">
                    <?php echo esc_html__('Đây là danh sách các hóa đơn cần được ký. Bạn có thể xem chi tiết và ký hóa đơn.', 'direct-image-signature'); ?>
                </p>
            </div>
            
            <div class="invoices-list">
                <p class="text-center py-4 text-gray-500">
                    <?php echo esc_html__('Chức năng này sẽ được triển khai sau khi bạn tạo custom post type cho hóa đơn.', 'direct-image-signature'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Hiển thị tab hóa đơn đã ký
     */
    public static function render_signed_invoices() {
        ?>
        <div class="tab-pane" id="signed">
            <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo esc_html__('Hóa đơn đã ký', 'direct-image-signature'); ?></h3>
            
            <div class="message-box p-4 bg-green-50 border border-green-200 rounded-md mb-6">
                <p class="text-green-700">
                    <?php echo esc_html__('Đây là danh sách các hóa đơn đã được ký. Bạn có thể xem chi tiết và tải xuống.', 'direct-image-signature'); ?>
                </p>
            </div>
            
            <div class="invoices-list">
                <p class="text-center py-4 text-gray-500">
                    <?php echo esc_html__('Chức năng này sẽ được triển khai sau khi bạn tạo custom post type cho hóa đơn.', 'direct-image-signature'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Hiển thị tab hóa đơn của tôi
     */
    public static function render_my_invoices() {
        ?>
        <div class="tab-pane" id="my">
            <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo esc_html__('Hóa đơn của tôi', 'direct-image-signature'); ?></h3>
            
            <div class="message-box p-4 bg-yellow-50 border border-yellow-200 rounded-md mb-6">
                <p class="text-yellow-700">
                    <?php echo esc_html__('Đây là danh sách các hóa đơn bạn đã tạo. Bạn có thể quản lý và theo dõi trạng thái của chúng.', 'direct-image-signature'); ?>
                </p>
            </div>
            
            <div class="invoices-list">
                <p class="text-center py-4 text-gray-500">
                    <?php echo esc_html__('Chức năng này sẽ được triển khai sau khi bạn tạo custom post type cho hóa đơn.', 'direct-image-signature'); ?>
                </p>
            </div>
        </div>
        <?php
    }
} 