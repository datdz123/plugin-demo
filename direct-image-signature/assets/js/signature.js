jQuery(document).ready(function($) {
    // Biến lưu trữ thông tin
    let signaturePad = null;
    let currentSlide = 0;
    let signatures = {};
    let uploadedSignature = null;
    
    // Xử lý khi click vào nút ký
    $(document).on('click', '.dis-sign-button', function() {
        const invoiceId = $(this).data('invoice-id');
        
        // Lấy danh sách ảnh của hóa đơn
        $.ajax({
            url: dis_signature.ajaxurl,
            type: 'POST',
            data: {
                action: 'dis_get_invoice_images',
                nonce: dis_signature.nonce,
                invoice_id: invoiceId
            },
            success: function(response) {
                if (response.success && response.data.images) {
                    // Reset biến lưu trữ chữ ký
                    signatures[invoiceId] = {};
                    currentSlide = 0;
                    
                    // Tạo danh sách ảnh cho Fancybox
                    const images = response.data.images;
                    const fancyboxItems = [];
                    
                    images.forEach((image, index) => {
                        fancyboxItems.push({
                            src: image.url,
                            caption: `${response.data.title} - ${dis_signature.i18n.page} ${index + 1}`
                        });
                    });
                    
                    // Mở Fancybox
                    Fancybox.show(fancyboxItems, {
                        dragToClose: false,
                        toolbar: {
                            display: [
                                "counter",
                                "close"
                            ]
                        },
                        on: {
                            ready: (fancybox) => {
                                // Thêm toolbar tùy chỉnh
                                addSignatureToolbar(invoiceId);
                            },
                            destroy: () => {
                                // Xóa toolbar khi đóng Fancybox
                                $('.dis-signature-toolbar').remove();
                            },
                            change: (fancybox, slide) => {
                                // Cập nhật slide hiện tại
                                currentSlide = slide.index;
                            }
                        }
                    });
                } else {
                    alert(dis_signature.i18n.no_images);
                }
            },
            error: function() {
                alert(dis_signature.i18n.error);
            }
        });
    });
    
    // Thêm toolbar tùy chỉnh vào Fancybox
    function addSignatureToolbar(invoiceId) {
        if ($('.dis-signature-toolbar').length === 0) {
            const toolbar = $(`
                <div class="dis-signature-toolbar" style="position: fixed; top: 0; left: 0; right: 0; background: #1e293b; color: white; padding: 10px; display: flex; justify-content: space-between; z-index: 10000;">
                    <div class="flex items-center space-x-4">
                        <button class="dis-draw-signature bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            ${dis_signature.i18n.draw_signature}
                        </button>
                        <button class="dis-upload-signature-btn bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                            ${dis_signature.i18n.upload_signature}
                        </button>
                        <input type="file" class="dis-upload-signature-input" accept="image/*" style="display: none;">
                    </div>
                    <div>
                        <button class="dis-save-all-signatures bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded" data-invoice-id="${invoiceId}">
                            ${dis_signature.i18n.save_signatures}
                        </button>
                    </div>
                </div>
            `);
            
            $('body').append(toolbar);
            
            // Xử lý sự kiện vẽ chữ ký
            $('.dis-draw-signature').on('click', function() {
                showSignaturePad(invoiceId);
            });
            
            // Xử lý sự kiện upload chữ ký
            $('.dis-upload-signature-btn').on('click', function() {
                $('.dis-upload-signature-input').click();
            });
            
            $('.dis-upload-signature-input').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    uploadedSignature = e.target.result;
                    addSignatureToImage(invoiceId, uploadedSignature);
                };
                reader.readAsDataURL(file);
            });
            
            // Xử lý sự kiện lưu tất cả chữ ký
            $('.dis-save-all-signatures').on('click', function() {
                saveAllSignatures(invoiceId);
            });
        }
    }
    
    // Hiển thị signature pad
    function showSignaturePad(invoiceId) {
        // Ẩn signature pad hiện tại nếu có
        $('#dis-signature-pad-container').remove();
        
        // Tạo container cho signature pad
        const padContainer = $(`
            <div id="dis-signature-pad-container" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white p-4 rounded-lg shadow-lg" style="width: 90%; max-width: 600px;">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">${dis_signature.i18n.draw_signature}</h3>
                        <button type="button" class="dis-close-pad text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="border border-gray-300 mb-4" style="height: 200px;">
                        <canvas id="dis-signature-canvas"></canvas>
                    </div>
                    <div class="flex justify-between">
                        <button type="button" class="dis-clear-signature bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                            ${dis_signature.i18n.clear}
                        </button>
                        <div>
                            <button type="button" class="dis-cancel-signature bg-gray-300 hover:bg-gray-400 text-gray-800 px-3 py-1 rounded text-sm mr-2">
                                ${dis_signature.i18n.cancel}
                            </button>
                            <button type="button" class="dis-apply-signature bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                ${dis_signature.i18n.apply}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(padContainer);
        
        // Khởi tạo signature pad
        const canvas = document.getElementById('dis-signature-canvas');
        const container = $('#dis-signature-pad-container .border');
        canvas.width = container.width();
        canvas.height = container.height();
        
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 2.5
        });
        
        // Xử lý sự kiện đóng pad
        $('.dis-close-pad, .dis-cancel-signature').on('click', function() {
            $('#dis-signature-pad-container').remove();
        });
        
        // Xử lý sự kiện xóa chữ ký
        $('.dis-clear-signature').on('click', function() {
            signaturePad.clear();
        });
        
        // Xử lý sự kiện áp dụng chữ ký
        $('.dis-apply-signature').on('click', function() {
            if (signaturePad.isEmpty()) {
                alert(dis_signature.i18n.empty_signature);
                return;
            }
            
            const signatureData = signaturePad.toDataURL();
            addSignatureToImage(invoiceId, signatureData);
            
            // Đóng signature pad
            $('#dis-signature-pad-container').remove();
        });
    }
    
    // Thêm chữ ký vào ảnh
    function addSignatureToImage(invoiceId, signatureData) {
        const fancyboxContainer = $('.fancybox__content');
        const imageContainer = fancyboxContainer.find('.fancybox__slide--current');
        
        if (imageContainer.length === 0) return;
        
        // Khởi tạo đối tượng signatures cho invoice và slide nếu chưa có
        if (!signatures[invoiceId]) {
            signatures[invoiceId] = {};
        }
        if (!signatures[invoiceId][currentSlide]) {
            signatures[invoiceId][currentSlide] = [];
        }
        
        const signatureId = 'sig-' + Date.now();
        signatures[invoiceId][currentSlide].push({
            id: signatureId,
            data: signatureData,
            x: 50, // Mặc định ở giữa
            y: 50  // Mặc định ở giữa
        });
        
        // Thêm chữ ký vào ảnh
        const signatureContainer = $(`
            <div class="dis-signature-container" data-id="${signatureId}" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 20;">
                <img src="${signatureData}" class="dis-signature-image" style="max-width: 150px; max-height: 80px; cursor: move;">
                <button type="button" class="dis-remove-signature" data-id="${signatureId}" style="position: absolute; top: -10px; right: -10px; width: 20px; height: 20px; border-radius: 50%; background-color: #ef4444; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; font-weight: bold;">&times;</button>
            </div>
        `);
        
        imageContainer.css('position', 'relative');
        imageContainer.append(signatureContainer);
        
        // Làm cho chữ ký có thể di chuyển
        signatureContainer.draggable({
            containment: imageContainer,
            stop: function(event, ui) {
                // Cập nhật vị trí trong đối tượng signatures
                const id = $(this).data('id');
                const signature = signatures[invoiceId][currentSlide].find(sig => sig.id === id);
                if (signature) {
                    const containerWidth = imageContainer.width();
                    const containerHeight = imageContainer.height();
                    const position = ui.position;
                    
                    signature.x = (position.left + $(this).width() / 2) / containerWidth * 100;
                    signature.y = (position.top + $(this).height() / 2) / containerHeight * 100;
                }
            }
        });
        
        // Xử lý sự kiện xóa chữ ký
        signatureContainer.find('.dis-remove-signature').on('click', function() {
            const id = $(this).data('id');
            const signature = signatures[invoiceId][currentSlide].find(sig => sig.id === id);
            if (signature) {
                // Xóa khỏi đối tượng signatures
                signatures[invoiceId][currentSlide] = signatures[invoiceId][currentSlide].filter(sig => sig.id !== id);
                
                // Xóa khỏi DOM
                $(`.dis-signature-container[data-id="${id}"]`).remove();
            }
        });
    }
    
    // Lưu tất cả chữ ký
    function saveAllSignatures(invoiceId) {
        // Kiểm tra xem có chữ ký nào không
        let hasSignatures = false;
        if (signatures[invoiceId]) {
            for (const slideIndex in signatures[invoiceId]) {
                if (signatures[invoiceId][slideIndex].length > 0) {
                    hasSignatures = true;
                    break;
                }
            }
        }
        
        if (!hasSignatures) {
            alert(dis_signature.i18n.no_signatures);
            return;
        }
        
        // Gửi dữ liệu lên server
        $.ajax({
            url: dis_signature.ajaxurl,
            type: 'POST',
            data: {
                action: 'dis_save_signatures',
                nonce: dis_signature.nonce,
                invoice_id: invoiceId,
                signatures: JSON.stringify(signatures[invoiceId])
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    Fancybox.close();
                    window.location.reload();
                } else {
                    alert(response.data || dis_signature.i18n.error);
                }
            },
            error: function() {
                alert(dis_signature.i18n.error);
            }
        });
    }
}); 