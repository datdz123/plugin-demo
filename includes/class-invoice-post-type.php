<?php
/**
 * Lớp đăng ký custom post type cho hóa đơn
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp đăng ký custom post type cho hóa đơn
 */
class DIS_Invoice_Post_Type {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        // Thêm hook để set trạng thái mặc định khi tạo invoice mới
        add_action('wp_insert_post', array($this, 'set_default_invoice_status'), 10, 3);
    }

    /**
     * Đăng ký custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('Hóa đơn', 'direct-image-signature'),
            'singular_name'      => __('Hóa đơn', 'direct-image-signature'),
            'menu_name'          => __('Hóa đơn', 'direct-image-signature'),
            'name_admin_bar'     => __('Hóa đơn', 'direct-image-signature'),
            'add_new'            => __('Thêm mới', 'direct-image-signature'),
            'add_new_item'       => __('Thêm hóa đơn mới', 'direct-image-signature'),
            'new_item'           => __('Hóa đơn mới', 'direct-image-signature'),
            'edit_item'          => __('Sửa hóa đơn', 'direct-image-signature'),
            'view_item'          => __('Xem hóa đơn', 'direct-image-signature'),
            'all_items'          => __('Tất cả hóa đơn', 'direct-image-signature'),
            'search_items'       => __('Tìm hóa đơn', 'direct-image-signature'),
            'not_found'          => __('Không tìm thấy hóa đơn nào', 'direct-image-signature'),
            'not_found_in_trash' => __('Không có hóa đơn nào trong thùng rác', 'direct-image-signature')
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'invoice'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
        );

        register_post_type('dis_invoice', $args);
    }

    /**
     * Set trạng thái mặc định cho invoice mới
     */
    public function set_default_invoice_status($post_id, $post, $update) {
        // Chỉ xử lý khi tạo mới, không xử lý khi update
        if ($update) {
            return;
        }

        // Kiểm tra post type
        if ($post->post_type !== 'dis_invoice') {
            return;
        }

        // Kiểm tra nếu chưa có trạng thái thì set mặc định là pending
        $current_status = get_post_meta($post_id, '_dis_invoice_status', true);
        if (empty($current_status)) {
            update_post_meta($post_id, '_dis_invoice_status', 'pending');
        }
    }
}

// Khởi tạo lớp
new DIS_Invoice_Post_Type(); 