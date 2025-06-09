<?php
/**
 * Lớp tạo shortcode cho chức năng ký ảnh
 *
 * @package Direct_Image_Signature
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lớp tạo shortcode cho chức năng ký ảnh
 */
class DIS_Image_Sign_Shortcode {

    /**
     * Khởi tạo lớp
     */
    public function __construct() {
        // Thêm shortcode hiển thị công cụ ký ảnh
        add_shortcode('dis_image_sign', array($this, 'render_image_sign_tool'));
        
        // Thêm scripts và styles cho shortcode
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Hiển thị công cụ ký ảnh
     */
    public function render_image_sign_tool($atts) {
        $atts = shortcode_atts(array(
            'image_id' => 0,
            'width' => '100%',
            'height' => 'auto',
        ), $atts, 'dis_image_sign');
        
        $image_id = intval($atts['image_id']);
        $width = esc_attr($atts['width']);
        $height = esc_attr($atts['height']);
        
        ob_start();
        
        if ($image_id > 0) {
            $image_url = wp_get_attachment_url($image_id);
            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            
            if ($image_url) {
                ?>
                <div class="dis-image-sign-container" style="position: relative; display: inline-block; margin-bottom: 20px;">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="dis-signable-image" style="max-width: <?php echo $width; ?>; height: <?php echo $height; ?>;" />
                    <button class="dis-sign-button bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md shadow-sm transition-colors !opacity-100" style="position: absolute; bottom: 10px; right: 10px; z-index: 10;">
                        <?php echo esc_html__('Ký hình ảnh này', 'direct-image-signature'); ?>
                    </button>
                </div>
                <?php
            } else {
                echo '<p>' . esc_html__('Không tìm thấy hình ảnh.', 'direct-image-signature') . '</p>';
            }
        } else {
            ?>
            <div class="dis-image-uploader">
                <h3><?php echo esc_html__('Tải lên hình ảnh để ký', 'direct-image-signature'); ?></h3>
                <form id="dis-image-upload-form" enctype="multipart/form-data" class="mb-4">
                    <div class="form-group mb-3">
                        <label for="dis-upload-image" class="block mb-2"><?php echo esc_html__('Chọn hình ảnh', 'direct-image-signature'); ?></label>
                        <input type="file" id="dis-upload-image" name="image" accept="image/*" class="border p-2 w-full rounded">
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md">
                        <?php echo esc_html__('Tải lên', 'direct-image-signature'); ?>
                    </button>
                </form>
                
                <div id="dis-upload-preview" class="mt-4 hidden">
                    <h4 class="mb-2"><?php echo esc_html__('Xem trước hình ảnh', 'direct-image-signature'); ?></h4>
                    <div class="dis-image-sign-container" style="position: relative; display: inline-block;">
                        <img src="" alt="<?php echo esc_attr__('Xem trước', 'direct-image-signature'); ?>" id="dis-preview-image" class="dis-signable-image" style="max-width: 100%; height: auto;" />
                        <button class="dis-sign-button bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 !opacity-100 rounded-md shadow-sm transition-colors" style="position: absolute; bottom: 10px; right: 10px; z-index: 10;">
                            <?php echo esc_html__('Ký hình ảnh này', 'direct-image-signature'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#dis-image-upload-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var fileInput = $('#dis-upload-image')[0];
                    if (fileInput.files.length === 0) {
                        alert('<?php echo esc_js(__('Vui lòng chọn một hình ảnh.', 'direct-image-signature')); ?>');
                        return;
                    }
                    
                    var file = fileInput.files[0];
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('#dis-preview-image').attr('src', e.target.result);
                        $('#dis-upload-preview').removeClass('hidden');
                    };
                    
                    reader.readAsDataURL(file);
                });
            });
            </script>
            <?php
        }
        
        return ob_get_clean();
    }

    /**
     * Thêm scripts và styles cho shortcode
     */
    public function enqueue_scripts() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dis_image_sign')) {
            // Đã được thêm trong file direct-image-signature.php
            // Không cần thêm lại các scripts và styles ở đây
        }
    }
}

// Khởi tạo lớp
new DIS_Image_Sign_Shortcode(); 