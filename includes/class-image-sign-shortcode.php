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
       
    }

    /**
     * Hiển thị công cụ ký ảnh
     */
    public function render_image_sign_tool() {
        if (is_admin()) {
            return;
        }
        
        
        ?>
        <div id="dis-container" style="display: none;">
            <div class="dis-lightbox-toolbar bg-gray-800 p-4 flex flex-wrap items-center justify-center gap-4">
                <button id="dis-draw" class="dis-button bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active">Vẽ chữ ký</button>
                <button id="dis-upload" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Tải ảnh chữ ký</button>
                <button id="dis-clear" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Xóa chữ ký</button>
                <div class="dis-size-control flex items-center gap-2">
                    <label for="dis-size" class="text-white">Kích thước:</label>
                    <input type="range" id="dis-size" min="1" max="30" value="10" class="w-24">
                </div>
                <div class="dis-color-control flex items-center gap-2">
                    <label for="dis-color" class="text-white">Màu sắc:</label>
                    <input type="color" id="dis-color" value="#000000">
                </div>
                <button id="dis-save" class="dis-button dis-save bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Lưu hình ảnh</button>
                <button id="dis-cancel" class="dis-button bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600">Hủy</button>
            </div>
            
            <div class="dis-upload-dialog fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[9999]" style="display: none;">
                <div class="dis-upload-dialog-content bg-white rounded-lg p-6 max-w-md w-full">
                    <h3 class="text-xl font-bold mb-4 border-b pb-2">Tải lên ảnh chữ ký</h3>
                    <form id="dis-upload-form" enctype="multipart/form-data">
                        <div class="dis-upload-field mb-4">
                            <label for="dis-signature-file" class="block mb-2 font-semibold">Chọn ảnh chữ ký (JPG, PNG, GIF):</label>
                            <input type="file" id="dis-signature-file" name="signature_image" accept="image/jpeg, image/png, image/gif" class="border p-2 w-full rounded">
                        </div>
                        <div class="dis-upload-preview p-4 border rounded bg-gray-50" style="display: none;">
                            <img id="dis-upload-preview-img" src="" alt="Xem trước" class="max-h-48 mx-auto">
                        </div>
                        <div class="dis-upload-buttons flex justify-end gap-3 mt-4">
                            <button type="button" id="dis-upload-submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Chèn vào ảnh</button>
                            <button type="button" id="dis-upload-cancel" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="dis-result-dialog fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-[9999]" style="display: none;">
                <div class="dis-result-dialog-content bg-white rounded-lg p-6 max-w-lg w-full">
                    <h3 class="text-xl font-bold mb-2">Hình ảnh đã được ký</h3>
                    <p class="mb-4 text-gray-600">Bạn có thể tải xuống hoặc nhấn vào hình ảnh để xem.</p>
                    <div class="dis-result-image p-4 border rounded bg-gray-50 mb-4 text-center">
                        <a href="" id="dis-result-fancy-link" data-fancybox="results">
                            <img id="dis-result-img" src="" alt="Hình ảnh đã ký" class="max-h-64 mx-auto">
                        </a>
                    </div>
                    <div class="dis-result-buttons flex justify-center gap-4">
                        <a href="" id="dis-result-link" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" download>Tải xuống</a>
                        <button id="dis-result-close" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Đóng</button>
                    </div>
                </div>
            </div>
            
            <div class="dis-loading fixed inset-0 bg-black bg-opacity-70 flex flex-col items-center justify-center z-[9999]" style="display: none;">
                <div class="dis-spinner w-12 h-12 border-4 border-t-blue-500 border-blue-200 rounded-full animate-spin mb-4"></div>
                <p class="text-white">Đang xử lý...</p>
            </div>
        </div>
      
        <?php
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