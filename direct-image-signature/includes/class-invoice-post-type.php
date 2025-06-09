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
        // Đăng ký custom post type
        add_action('init', array($this, 'register_invoice_post_type'));
        
        // Đăng ký custom meta box
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        
        // Lưu dữ liệu meta box
        add_action('save_post', array($this, 'save_meta_box_data'));
    }

    /**
     * Đăng ký custom post type cho hóa đơn
     */
    public function register_invoice_post_type() {
        $labels = array(
            'name'                  => _x('Hóa đơn', 'Post type general name', 'direct-image-signature'),
            'singular_name'         => _x('Hóa đơn', 'Post type singular name', 'direct-image-signature'),
            'menu_name'             => _x('Hóa đơn', 'Admin Menu text', 'direct-image-signature'),
            'name_admin_bar'        => _x('Hóa đơn', 'Add New on Toolbar', 'direct-image-signature'),
            'add_new'               => __('Thêm mới', 'direct-image-signature'),
            'add_new_item'          => __('Thêm hóa đơn mới', 'direct-image-signature'),
            'new_item'              => __('Hóa đơn mới', 'direct-image-signature'),
            'edit_item'             => __('Sửa hóa đơn', 'direct-image-signature'),
            'view_item'             => __('Xem hóa đơn', 'direct-image-signature'),
            'all_items'             => __('Tất cả hóa đơn', 'direct-image-signature'),
            'search_items'          => __('Tìm hóa đơn', 'direct-image-signature'),
            'parent_item_colon'     => __('Hóa đơn cha:', 'direct-image-signature'),
            'not_found'             => __('Không tìm thấy hóa đơn nào.', 'direct-image-signature'),
            'not_found_in_trash'    => __('Không tìm thấy hóa đơn nào trong thùng rác.', 'direct-image-signature'),
            'featured_image'        => __('Ảnh đại diện hóa đơn', 'direct-image-signature'),
            'set_featured_image'    => __('Đặt ảnh đại diện', 'direct-image-signature'),
            'remove_featured_image' => __('Xóa ảnh đại diện', 'direct-image-signature'),
            'use_featured_image'    => __('Sử dụng làm ảnh đại diện', 'direct-image-signature'),
            'archives'              => __('Lưu trữ hóa đơn', 'direct-image-signature'),
            'insert_into_item'      => __('Chèn vào hóa đơn', 'direct-image-signature'),
            'uploaded_to_this_item' => __('Đã tải lên cho hóa đơn này', 'direct-image-signature'),
            'filter_items_list'     => __('Lọc danh sách hóa đơn', 'direct-image-signature'),
            'items_list_navigation' => __('Điều hướng danh sách hóa đơn', 'direct-image-signature'),
            'items_list'            => __('Danh sách hóa đơn', 'direct-image-signature'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'hoa-don'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array('title', 'editor', 'author', 'thumbnail'),
        );

        register_post_type('dis_invoice', $args);
    }

    /**
     * Đăng ký meta box cho hóa đơn
     */
    public function register_meta_boxes() {
        add_meta_box(
            'dis_invoice_details',
            __('Thông tin hóa đơn', 'direct-image-signature'),
            array($this, 'render_invoice_details_meta_box'),
            'dis_invoice',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dis_invoice_images',
            __('Hình ảnh hóa đơn', 'direct-image-signature'),
            array($this, 'render_invoice_images_meta_box'),
            'dis_invoice',
            'normal',
            'high'
        );
        
        add_meta_box(
            'dis_invoice_status',
            __('Trạng thái hóa đơn', 'direct-image-signature'),
            array($this, 'render_invoice_status_meta_box'),
            'dis_invoice',
            'side',
            'default'
        );
    }

    /**
     * Hiển thị meta box thông tin hóa đơn
     * 
     * @param WP_Post $post Post object
     */
    public function render_invoice_details_meta_box($post) {
        // Nonce field để xác thực khi lưu
        wp_nonce_field('dis_save_invoice_details', 'dis_invoice_details_nonce');
        
        // Lấy dữ liệu đã lưu
        $invoice_description = get_post_meta($post->ID, '_dis_invoice_description', true);
        
        ?>
        <div class="dis-meta-box">
            <p>
                <label for="dis_invoice_description"><?php _e('Mô tả hóa đơn', 'direct-image-signature'); ?></label>
                <textarea id="dis_invoice_description" name="dis_invoice_description" class="large-text" rows="5"><?php echo esc_textarea($invoice_description); ?></textarea>
            </p>
        </div>
        <?php
    }

    /**
     * Hiển thị meta box hình ảnh hóa đơn
     * 
     * @param WP_Post $post Post object
     */
    public function render_invoice_images_meta_box($post) {
        // Nonce field để xác thực khi lưu
        wp_nonce_field('dis_save_invoice_images', 'dis_invoice_images_nonce');
        
        // Lấy dữ liệu đã lưu
        $invoice_images = get_post_meta($post->ID, '_dis_invoice_images', true);
        
        ?>
        <div class="dis-meta-box">
            <div class="dis-images-container">
                <?php if (!empty($invoice_images) && is_array($invoice_images)) : ?>
                    <div class="dis-images-grid">
                        <?php foreach ($invoice_images as $image_id) : 
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                            if ($image_url) :
                        ?>
                            <div class="dis-image-item">
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                                <a href="#" class="dis-remove-image" data-image-id="<?php echo esc_attr($image_id); ?>">
                                    <?php _e('Xóa', 'direct-image-signature'); ?>
                                </a>
                            </div>
                        <?php 
                            endif;
                        endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php _e('Chưa có hình ảnh nào.', 'direct-image-signature'); ?></p>
                <?php endif; ?>
            </div>
            
            <input type="hidden" name="dis_invoice_images" id="dis_invoice_images" value="<?php echo !empty($invoice_images) ? esc_attr(implode(',', $invoice_images)) : ''; ?>">
            
            <p>
                <button type="button" class="button" id="dis_add_images">
                    <?php _e('Thêm hình ảnh', 'direct-image-signature'); ?>
                </button>
            </p>
            
            <script>
            jQuery(document).ready(function($) {
                var frame;
                
                // Thêm hình ảnh
                $('#dis_add_images').on('click', function(e) {
                    e.preventDefault();
                    
                    if (frame) {
                        frame.open();
                        return;
                    }
                    
                    frame = wp.media({
                        title: '<?php _e('Chọn hình ảnh hóa đơn', 'direct-image-signature'); ?>',
                        button: {
                            text: '<?php _e('Thêm vào hóa đơn', 'direct-image-signature'); ?>'
                        },
                        multiple: true
                    });
                    
                    frame.on('select', function() {
                        var attachments = frame.state().get('selection').toJSON();
                        var imageIds = $('#dis_invoice_images').val().split(',').filter(Boolean);
                        var imagesGrid = $('.dis-images-grid');
                        
                        if (!imagesGrid.length) {
                            $('.dis-images-container').html('<div class="dis-images-grid"></div>');
                            imagesGrid = $('.dis-images-grid');
                        }
                        
                        $.each(attachments, function(index, attachment) {
                            if ($.inArray(attachment.id.toString(), imageIds) === -1) {
                                imageIds.push(attachment.id);
                                
                                imagesGrid.append(
                                    '<div class="dis-image-item">' +
                                    '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                                    '<a href="#" class="dis-remove-image" data-image-id="' + attachment.id + '">' +
                                    '<?php _e('Xóa', 'direct-image-signature'); ?>' +
                                    '</a>' +
                                    '</div>'
                                );
                            }
                        });
                        
                        $('#dis_invoice_images').val(imageIds.join(','));
                    });
                    
                    frame.open();
                });
                
                // Xóa hình ảnh
                $(document).on('click', '.dis-remove-image', function(e) {
                    e.preventDefault();
                    
                    var imageId = $(this).data('image-id').toString();
                    var imageIds = $('#dis_invoice_images').val().split(',').filter(Boolean);
                    var index = imageIds.indexOf(imageId);
                    
                    if (index !== -1) {
                        imageIds.splice(index, 1);
                        $('#dis_invoice_images').val(imageIds.join(','));
                        $(this).parent('.dis-image-item').remove();
                        
                        if (imageIds.length === 0) {
                            $('.dis-images-container').html('<p><?php _e('Chưa có hình ảnh nào.', 'direct-image-signature'); ?></p>');
                        }
                    }
                });
            });
            </script>
            
            <style>
            .dis-images-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                grid-gap: 10px;
                margin-bottom: 15px;
            }
            .dis-image-item {
                position: relative;
                border: 1px solid #ddd;
                padding: 5px;
                text-align: center;
            }
            .dis-image-item img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 0 auto 5px;
            }
            .dis-remove-image {
                color: #a00;
                text-decoration: none;
                font-size: 12px;
            }
            .dis-remove-image:hover {
                color: #dc3232;
            }
            </style>
        </div>
        <?php
    }

    /**
     * Hiển thị meta box trạng thái hóa đơn
     * 
     * @param WP_Post $post Post object
     */
    public function render_invoice_status_meta_box($post) {
        // Nonce field để xác thực khi lưu
        wp_nonce_field('dis_save_invoice_status', 'dis_invoice_status_nonce');
        
        // Lấy dữ liệu đã lưu
        $invoice_status = get_post_meta($post->ID, '_dis_invoice_status', true);
        if (empty($invoice_status)) {
            $invoice_status = 'pending';
        }
        
        ?>
        <div class="dis-meta-box">
            <p>
                <label for="dis_invoice_status"><?php _e('Trạng thái', 'direct-image-signature'); ?></label>
                <select id="dis_invoice_status" name="dis_invoice_status" class="widefat">
                    <option value="pending" <?php selected($invoice_status, 'pending'); ?>>
                        <?php _e('Chờ ký', 'direct-image-signature'); ?>
                    </option>
                    <option value="signed" <?php selected($invoice_status, 'signed'); ?>>
                        <?php _e('Đã ký', 'direct-image-signature'); ?>
                    </option>
                </select>
            </p>
        </div>
        <?php
    }

    /**
     * Lưu dữ liệu meta box
     * 
     * @param int $post_id Post ID
     */
    public function save_meta_box_data($post_id) {
        // Kiểm tra nếu đang tự động lưu
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Kiểm tra loại post
        if (get_post_type($post_id) !== 'dis_invoice') {
            return;
        }
        
        // Kiểm tra quyền người dùng
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Lưu mô tả hóa đơn
        if (isset($_POST['dis_invoice_details_nonce']) && wp_verify_nonce($_POST['dis_invoice_details_nonce'], 'dis_save_invoice_details')) {
            if (isset($_POST['dis_invoice_description'])) {
                update_post_meta($post_id, '_dis_invoice_description', sanitize_textarea_field($_POST['dis_invoice_description']));
            }
        }
        
        // Lưu hình ảnh hóa đơn
        if (isset($_POST['dis_invoice_images_nonce']) && wp_verify_nonce($_POST['dis_invoice_images_nonce'], 'dis_save_invoice_images')) {
            if (isset($_POST['dis_invoice_images'])) {
                $image_ids = explode(',', sanitize_text_field($_POST['dis_invoice_images']));
                $image_ids = array_filter($image_ids);
                update_post_meta($post_id, '_dis_invoice_images', $image_ids);
            } else {
                delete_post_meta($post_id, '_dis_invoice_images');
            }
        }
        
        // Lưu trạng thái hóa đơn
        if (isset($_POST['dis_invoice_status_nonce']) && wp_verify_nonce($_POST['dis_invoice_status_nonce'], 'dis_save_invoice_status')) {
            if (isset($_POST['dis_invoice_status'])) {
                update_post_meta($post_id, '_dis_invoice_status', sanitize_text_field($_POST['dis_invoice_status']));
            }
        }
    }
}

// Khởi tạo lớp
new DIS_Invoice_Post_Type(); 