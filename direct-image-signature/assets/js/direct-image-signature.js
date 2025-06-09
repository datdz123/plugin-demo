/**
 * JavaScript chính cho plugin Direct Image Signature
 */

(function($) {
    console.log('Direct Image Signature plugin loaded');
    'use strict';
    
    // Đối tượng chính của plugin
    var DIS = {
        canvas: null,
        fabricCanvas: null,
        signaturePad: null,
        originalImageUrl: null,
        currentSignature: null,
        signatureScale: 1,
        isDrawing: false,
        
        // Khởi tạo
        init: function() {
            this.setupLightbox();
            this.setupEventListeners();
            this.setupSignaturePad();
        },
        
        // Thiết lập lightbox và công cụ ký
        setupLightbox: function() {
            // Tạo nút ký hình ảnh cho mỗi hình ảnh
            $('.wp-block-image img, .wp-caption img, figure img').each(function() {
                var $img = $(this);
                var $container = $img.parent();
                
                // Chỉ thêm nút nếu chưa có
                if ($container.find('.dis-sign-button').length === 0) {
                    var $button = $('<button>', {
                        'class': 'dis-sign-button bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md shadow-sm transition-colors',
                        text: 'Ký hình ảnh này'
                    });
                    
                    // Thêm nút vào container
                    $container.css('position', 'relative');
                    $container.append($button);
                    
                    // Định vị nút
                    $button.css({
                        'position': 'absolute',
                        'bottom': '10px',
                        'right': '10px',
                        'z-index': 10
                    });
                }
            });
        },
        
        // Thiết lập các sự kiện
        setupEventListeners: function() {
            var self = this;
            
            // Sự kiện khi nhấn nút ký hình ảnh
            $(document).on('click', '.dis-sign-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $img = $(this).parent().find('img').first();
                self.openImageEditor($img);
            });
            
            // Chuyển đổi giữa các công cụ
            $('#dis-draw, #dis-upload').on('click', function() {
                $('#dis-draw, #dis-upload').removeClass('bg-blue-500 active').addClass('bg-gray-700');
                $(this).removeClass('bg-gray-700').addClass('bg-blue-500 active');
                
                if ($(this).attr('id') === 'dis-draw') {
                    $('.dis-upload-dialog').hide();
                } else {
                    $('.dis-upload-dialog').show();
                }
            });
            
            // Xóa chữ ký
            $('#dis-clear').on('click', function() {
                if (self.signaturePad) {
                    self.signaturePad.clear();
                }
                self.removeSignature();
            });
            
            // Thay đổi kích thước chữ ký
            $('#dis-size').on('input', function() {
                self.signatureScale = parseFloat($(this).val()) / 10;
                self.updateSignatureObject();
            });
            
            // Thay đổi màu chữ ký
            $('#dis-color').on('input', function() {
                if (self.signaturePad) {
                    self.signaturePad.penColor = $(this).val();
                }
                self.updateSignatureColor($(this).val());
            });
            
            // Lưu hình ảnh
            $('#dis-save').on('click', function() {
                self.saveImage();
            });
            
            // Hủy bỏ
            $('#dis-cancel').on('click', function() {
                self.closeImageEditor();
            });
            
            // Xử lý xem trước tệp upload
            $('#dis-signature-file').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#dis-upload-preview-img').attr('src', e.target.result);
                        $('.dis-upload-preview').show();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Xử lý nút upload
            $('#dis-upload-submit').on('click', function() {
                self.uploadSignature();
            });
            
            // Đóng dialog upload
            $('#dis-upload-cancel').on('click', function() {
                $('.dis-upload-dialog').hide();
                $('#dis-draw').click();
            });
            
            // Đóng dialog kết quả
            $('#dis-result-close').on('click', function() {
                $('.dis-result-dialog').hide();
                self.closeImageEditor();
            });
        },
        
        // Thiết lập Signature Pad
        setupSignaturePad: function() {
            var canvas = document.getElementById('dis-signature-pad');
            if (canvas) {
                this.signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255, 255, 255, 0)',
                    penColor: document.getElementById('dis-color').value,
                    minWidth: 0.5,
                    maxWidth: 2.5
                });
            }
        },
        
        // Mở trình soạn thảo hình ảnh
        openImageEditor: function($img) {
            var self = this;
            
            // Lưu URL hình ảnh gốc
            self.originalImageUrl = $img.attr('src');
            
            // Hiển thị container
            $('#dis-container').fadeIn();
            
            // Tạo canvas
            if (!self.fabricCanvas) {
                self.canvas = document.createElement('canvas');
                self.canvas.id = 'dis-image-canvas';
                self.canvas.className = 'max-w-full mx-auto border border-gray-300 shadow-md';
                
                // Thêm canvas vào DOM trước các công cụ
                $('.dis-lightbox-toolbar').before(self.canvas);
                
                // Khởi tạo Fabric.js canvas
                self.fabricCanvas = new fabric.Canvas('dis-image-canvas');
                
                // Thiết lập sự kiện kéo thả
                self.fabricCanvas.on('mouse:down', function(options) {
                    if (options.target && options.target.type === 'image' && options.target.id === 'signature') {
                        self.isDrawing = false;
                    } else if (self.currentSignature && $('#dis-draw').hasClass('active')) {
                        self.isDrawing = true;
                        var pointer = self.fabricCanvas.getPointer(options.e);
                        var signature = new fabric.Image(self.currentSignature, {
                            left: pointer.x - (self.currentSignature.width * self.signatureScale) / 2,
                            top: pointer.y - (self.currentSignature.height * self.signatureScale) / 2,
                            scaleX: self.signatureScale,
                            scaleY: self.signatureScale,
                            id: 'signature',
                            selectable: true,
                            hasControls: true,
                            hasBorders: true
                        });
                        self.fabricCanvas.add(signature);
                        self.fabricCanvas.setActiveObject(signature);
                        self.fabricCanvas.renderAll();
                    }
                });
            }
            
            // Tải hình ảnh
            self.loadImage();
        },
        
        // Tải hình ảnh vào canvas
        loadImage: function() {
            var self = this;
            
            $('.dis-loading').show();
            
            fabric.Image.fromURL(self.originalImageUrl, function(img) {
                // Điều chỉnh kích thước canvas
                var maxWidth = window.innerWidth * 0.8;
                var maxHeight = window.innerHeight * 0.6;
                
                var scale = 1;
                if (img.width > maxWidth || img.height > maxHeight) {
                    scale = Math.min(maxWidth / img.width, maxHeight / img.height);
                }
                
                self.fabricCanvas.setWidth(img.width * scale);
                self.fabricCanvas.setHeight(img.height * scale);
                
                // Đặt hình ảnh làm nền
                img.set({
                    scaleX: scale,
                    scaleY: scale,
                    selectable: false,
                    evented: false,
                    id: 'background'
                });
                
                self.fabricCanvas.clear();
                self.fabricCanvas.add(img);
                self.fabricCanvas.renderAll();
                
                $('.dis-loading').hide();
            });
        },
        
        // Cập nhật đối tượng chữ ký
        updateSignatureObject: function() {
            var self = this;
            
            if (self.currentSignature && self.fabricCanvas) {
                // Tìm đối tượng chữ ký trên canvas
                var objects = self.fabricCanvas.getObjects();
                var signature = objects.find(function(obj) {
                    return obj.id === 'signature';
                });
                
                if (signature) {
                    signature.set({
                        scaleX: self.signatureScale,
                        scaleY: self.signatureScale
                    });
                    self.fabricCanvas.renderAll();
                }
            }
        },
        
        // Cập nhật màu sắc chữ ký
        updateSignatureColor: function(color) {
            var self = this;
            
            if (self.fabricCanvas) {
                // Tìm đối tượng chữ ký trên canvas
                var objects = self.fabricCanvas.getObjects();
                var signature = objects.find(function(obj) {
                    return obj.id === 'signature';
                });
                
                if (signature) {
                    signature.filters = [new fabric.Image.filters.BlendColor({
                        color: color,
                        mode: 'tint',
                        alpha: 1
                    })];
                    signature.applyFilters();
                    self.fabricCanvas.renderAll();
                }
            }
        },
        
        // Xóa chữ ký
        removeSignature: function() {
            var self = this;
            
            if (self.fabricCanvas) {
                // Tìm và xóa đối tượng chữ ký
                var objects = self.fabricCanvas.getObjects();
                var signature = objects.find(function(obj) {
                    return obj.id === 'signature';
                });
                
                if (signature) {
                    self.fabricCanvas.remove(signature);
                    self.fabricCanvas.renderAll();
                }
            }
        },
        
        // Upload chữ ký
        uploadSignature: function() {
            var self = this;
            var file = document.getElementById('dis-signature-file').files[0];
            
            if (!file) {
                alert('Vui lòng chọn một tệp hình ảnh.');
                return;
            }
            
            $('.dis-loading').show();
            
            var formData = new FormData();
            formData.append('action', 'dis_upload_signature');
            formData.append('nonce', dis_ajax.nonce);
            formData.append('signature_image', file);
            
            $.ajax({
                url: dis_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('.dis-loading').hide();
                    $('.dis-upload-dialog').hide();
                    
                    if (response.success) {
                        // Tải chữ ký vào canvas
                        fabric.Image.fromURL(response.data.url, function(img) {
                            self.currentSignature = img.getElement();
                            
                            var pointer = self.fabricCanvas.getPointer({ clientX: window.innerWidth / 2, clientY: window.innerHeight / 2 });
                            var signature = new fabric.Image(self.currentSignature, {
                                left: pointer.x - (img.width * self.signatureScale) / 2,
                                top: pointer.y - (img.height * self.signatureScale) / 2,
                                scaleX: self.signatureScale,
                                scaleY: self.signatureScale,
                                id: 'signature',
                                selectable: true,
                                hasControls: true,
                                hasBorders: true
                            });
                            
                            self.removeSignature();
                            self.fabricCanvas.add(signature);
                            self.fabricCanvas.setActiveObject(signature);
                            self.fabricCanvas.renderAll();
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    $('.dis-loading').hide();
                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            });
        },
        
        // Lưu hình ảnh
        saveImage: function() {
            var self = this;
            
            $('.dis-loading').show();
            
            // Ẩn viền và điều khiển của đối tượng chữ ký
            var signature = self.fabricCanvas.getObjects().find(function(obj) {
                return obj.id === 'signature';
            });
            
            if (signature) {
                signature.set({
                    selectable: false,
                    hasControls: false,
                    hasBorders: false
                });
                self.fabricCanvas.discardActiveObject();
                self.fabricCanvas.renderAll();
            }
            
            // Lấy dữ liệu hình ảnh
            var imageData = self.fabricCanvas.toDataURL({
                format: 'png',
                quality: 1
            });
            
            // Gửi lên server
            $.ajax({
                url: dis_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dis_save_image',
                    nonce: dis_ajax.nonce,
                    image_data: imageData
                },
                success: function(response) {
                    $('.dis-loading').hide();
                    
                    if (response.success) {
                        // Hiển thị hình ảnh kết quả
                        $('#dis-result-img, #dis-result-fancy-link').attr('src', response.data.url);
                        $('#dis-result-fancy-link, #dis-result-link').attr('href', response.data.url);
                        $('.dis-result-dialog').show();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    $('.dis-loading').hide();
                    alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
                }
            });
        },
        
        // Đóng trình soạn thảo hình ảnh
        closeImageEditor: function() {
            var self = this;
            
            // Xóa canvas
            if (self.fabricCanvas) {
                self.fabricCanvas.dispose();
                self.fabricCanvas = null;
            }
            
            if (self.canvas && self.canvas.parentNode) {
                self.canvas.parentNode.removeChild(self.canvas);
            }
            
            // Ẩn container
            $('#dis-container').hide();
            $('.dis-upload-dialog').hide();
            $('.dis-result-dialog').hide();
            
            // Reset các giá trị
            self.originalImageUrl = null;
            self.currentSignature = null;
            self.signatureScale = 1;
            $('#dis-size').val(10);
            $('#dis-color').val('#000000');
            $('#dis-draw').click();
        }
    };
    
    // Khởi tạo khi trang đã tải xong
    $(document).ready(function() {
        DIS.init();
    });
    
})(jQuery);
