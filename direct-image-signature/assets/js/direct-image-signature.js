jQuery(document).ready(function($) {
    // Biến toàn cục
    let fabricCanvas;
    let originalImageWidth, originalImageHeight;
    let originalImage = null;
    let originalSrc = '';
    let currentImg = null;
    let fancyboxInstance = null;
    let signatureObject = null;
    let isDrawingMode = true;
    
    // Thêm icon ký cho tất cả hình ảnh
    function addSignatureIconToImages() {
        console.log("Đang thêm icon ký vào hình ảnh...");
        
        // Tìm tất cả các hình ảnh trên trang và thêm icon ký
        $('img').not('.dis-excluded, .dis-signature-icon-img, .dis-result-img').each(function(index) {
            const img = $(this);
            
            // Gán ID cho hình ảnh
            if (!img.attr('data-dis-index')) {
                img.attr('data-dis-index', 'img-dis-' + index);
            }
            
            // Chỉ xử lý nếu chưa có wrapper
            if (!img.parent().hasClass('dis-image-wrapper')) {
                // Tạo wrapper
                img.wrap('<div class="dis-image-wrapper"></div>');
                
                // Tạo icon chữ ký
                const iconHtml = `
                    <div class="dis-signature-icon" data-img-id="${img.attr('data-dis-index')}">
                        <img src="${dis_ajax.plugin_url}assets/images/signature-icon.png" alt="Ký" class="dis-signature-icon-img">
                    </div>
                `;
                
                // Thêm icon vào wrapper
                img.parent().append(iconHtml);
                
                console.log("Đã thêm icon ký cho hình ảnh:", img.attr('src'));
            }
        });
    }
    
    // Thêm icon ký cho tất cả hình ảnh (gọi lại nhiều lần để đảm bảo bắt được tất cả hình ảnh)
    function initSignatureIcons() {
        // Gọi ngay lần đầu
        addSignatureIconToImages();
        
        // Gọi lại sau 1 giây
        setTimeout(function() {
            addSignatureIconToImages();
        }, 1000);
        
        // Gọi lại sau 3 giây
        setTimeout(function() {
            addSignatureIconToImages();
        }, 3000);
    }
    
    // Khởi tạo canvas ký
    function initSignatureCanvas(img) {
        console.log("Khởi tạo canvas ký cho hình ảnh:", img.src);
        
        // Lưu hình ảnh gốc
        currentImg = img;
        originalSrc = img.src;
        
        // Tải hình ảnh và kiểm tra hướng EXIF
        loadImageWithOrientation(originalSrc, function(correctedImg) {
            originalImage = correctedImg;
            originalImageWidth = correctedImg.width;
            originalImageHeight = correctedImg.height;
            
            // Mở Fancybox với hình ảnh đã điều chỉnh hướng
            openImageInFancybox(correctedImg.src);
        });
    }
    
    // Mở hình ảnh trong Fancybox
    function openImageInFancybox(src) {
        Fancybox.show([
            {
                src: src,
                type: 'image'
            }
        ], {
            on: {
                done: (fancybox) => {
                    fancyboxInstance = fancybox;
                    setTimeout(function() {
                        initCanvasOverImage();
                    }, 500);
                },
                close: (fancybox) => {
                    if (fabricCanvas) {
                        fabricCanvas.dispose();
                        fabricCanvas = null;
                    }
                    $('.dis-lightbox-canvas-wrapper').remove();
                    $('#dis-container').hide();
                }
            }
        });
    }
    
    // Khởi tạo canvas phủ lên hình ảnh trong Fancybox
    function initCanvasOverImage() {
        // Hiển thị container công cụ
        $('#dis-container').show();
        
        // Tìm hình ảnh trong Fancybox
        const lightboxImg = $('.fancybox__content img');
        if (!lightboxImg.length) {
            console.error("Không tìm thấy hình ảnh trong Fancybox");
            return;
        }
        
        // Lấy kích thước và vị trí của hình ảnh
        const imgWidth = lightboxImg.width();
        const imgHeight = lightboxImg.height();
        const imgOffset = lightboxImg.offset();
        
        console.log("Kích thước hình ảnh:", imgWidth, "x", imgHeight);
        console.log("Vị trí hình ảnh:", imgOffset.top, imgOffset.left);
        
        // Tạo wrapper cho canvas
        const canvasWrapper = $('<div class="dis-lightbox-canvas-wrapper"></div>');
        canvasWrapper.css({
            position: 'absolute',
            top: imgOffset.top,
            left: imgOffset.left,
            width: imgWidth,
            height: imgHeight,
            zIndex: 9999
        });
        
        // Thêm canvas vào wrapper
        canvasWrapper.append('<canvas id="dis-canvas"></canvas>');
        $('body').append(canvasWrapper);
        
        // Khởi tạo Fabric canvas
        if (fabricCanvas) {
            fabricCanvas.dispose();
        }
        
        fabricCanvas = new fabric.Canvas('dis-canvas', {
            selection: false
        });
        fabricCanvas.setWidth(imgWidth);
        fabricCanvas.setHeight(imgHeight);
        
        // Đặt hình ảnh gốc làm nền
        fabric.Image.fromURL(originalImage.src, function(img) {
            fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas), {
                scaleX: imgWidth / originalImageWidth,
                scaleY: imgHeight / originalImageHeight,
                originX: 'left',
                originY: 'top'
            });
        });
        
        // Thiết lập chế độ vẽ tự do ban đầu
        setDrawingMode(true);
        
        console.log("Đã khởi tạo canvas thành công");
    }
    
    // Thiết lập chế độ vẽ tay
    function setDrawingMode(enable) {
        if (!fabricCanvas) return;
        
        isDrawingMode = enable;
        fabricCanvas.isDrawingMode = enable;
        
        if (enable) {
            fabricCanvas.freeDrawingBrush.width = parseInt($('#dis-size').val());
            fabricCanvas.freeDrawingBrush.color = $('#dis-color').val();
            $('#dis-draw').addClass('active');
            $('#dis-upload').removeClass('active');
        } else {
            $('#dis-draw').removeClass('active');
        }
    }
    
    // Thêm ảnh chữ ký vào canvas
    function addSignatureImageToCanvas(imageUrl) {
        if (!fabricCanvas) return;
        
        // Chuyển sang chế độ không vẽ tay
        setDrawingMode(false);
        $('#dis-upload').addClass('active');
        
        // Xóa ảnh chữ ký hiện tại (nếu có)
        if (signatureObject) {
            fabricCanvas.remove(signatureObject);
        }
        
        // Thêm ảnh chữ ký mới
        fabric.Image.fromURL(imageUrl, function(img) {
            // Tính toán kích thước phù hợp
            const maxWidth = fabricCanvas.getWidth() * 0.5;
            const scale = maxWidth / img.width;
            
            img.set({
                left: fabricCanvas.getWidth() / 2 - (img.width * scale) / 2,
                top: fabricCanvas.getHeight() / 2 - (img.height * scale) / 2,
                scaleX: scale,
                scaleY: scale,
                cornerColor: 'rgba(0,0,255,0.5)',
                cornerSize: 10,
                transparentCorners: false
            });
            
            // Cho phép di chuyển và thay đổi kích thước
            img.setControlsVisibility({
                mt: true,
                mb: true,
                ml: true,
                mr: true,
                bl: true,
                br: true,
                tl: true,
                tr: true
            });
            
            fabricCanvas.add(img);
            fabricCanvas.setActiveObject(img);
            signatureObject = img;
            fabricCanvas.renderAll();
            
            // Đóng dialog upload
            $('.dis-upload-dialog').hide();
        });
    }
    
    // Tải và điều chỉnh hướng hình ảnh dựa trên EXIF
    function loadImageWithOrientation(src, callback) {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        
        img.onload = function() {
            // Tạo một đối tượng ảnh mới
            const correctedImg = {
                width: img.width,
                height: img.height,
                src: src
            };
            
            // Đọc thông tin EXIF
            EXIF.getData(img, function() {
                const orientation = EXIF.getTag(this, "Orientation");
                
                // Nếu không có thông tin orientation, trả về ảnh gốc
                if (!orientation) {
                    callback(correctedImg);
                    return;
                }
                
                // Tạo canvas để điều chỉnh hướng
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Thiết lập kích thước canvas
                canvas.width = img.width;
                canvas.height = img.height;
                
                // Áp dụng phép biến đổi dựa trên orientation
                switch (orientation) {
                    case 2:
                        ctx.transform(-1, 0, 0, 1, img.width, 0);
                        break;
                    case 3:
                        ctx.transform(-1, 0, 0, -1, img.width, img.height);
                        break;
                    case 4:
                        ctx.transform(1, 0, 0, -1, 0, img.height);
                        break;
                    case 5:
                        canvas.width = img.height;
                        canvas.height = img.width;
                        ctx.transform(0, 1, 1, 0, 0, 0);
                        break;
                    case 6:
                        canvas.width = img.height;
                        canvas.height = img.width;
                        ctx.transform(0, 1, -1, 0, img.height, 0);
                        break;
                    case 7:
                        canvas.width = img.height;
                        canvas.height = img.width;
                        ctx.transform(0, -1, 1, 0, 0, img.width);
                        break;
                    case 8:
                        canvas.width = img.height;
                        canvas.height = img.width;
                        ctx.transform(0, -1, -1, 0, img.height, img.width);
                        break;
                    default:
                        // Không cần biến đổi
                        break;
                }
                
                // Vẽ ảnh lên canvas với phép biến đổi
                ctx.drawImage(img, 0, 0);
                
                // Tạo URL cho ảnh đã điều chỉnh
                const dataURL = canvas.toDataURL('image/jpeg');
                
                // Cập nhật thông tin ảnh
                correctedImg.src = dataURL;
                correctedImg.width = canvas.width;
                correctedImg.height = canvas.height;
                
                callback(correctedImg);
            });
        };
        
        img.src = src;
    }
    
    // Upload ảnh chữ ký
    function uploadSignatureImage() {
        $('#dis-upload-submit').prop('disabled', true).text('Đang tải...');
        
        var formData = new FormData();
        formData.append('action', 'dis_upload_signature');
        formData.append('nonce', dis_ajax.nonce);
        formData.append('signature_image', $('#dis-signature-file')[0].files[0]);
        
        $.ajax({
            url: dis_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#dis-upload-submit').prop('disabled', false).text('Chèn vào ảnh');
                
                if (response.success) {
                    addSignatureImageToCanvas(response.data.url);
                } else {
                    alert('Lỗi: ' + response.data);
                }
            },
            error: function() {
                $('#dis-upload-submit').prop('disabled', false).text('Chèn vào ảnh');
                alert('Đã xảy ra lỗi khi upload file chữ ký');
            }
        });
    }
    
    // Xem trước file chữ ký
    function handleSignatureFilePreview() {
        var file = $('#dis-signature-file')[0].files[0];
        
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#dis-upload-preview-img').attr('src', e.target.result);
                $('.dis-upload-preview').show();
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Xử lý upload ảnh chữ ký
    function handleSignatureUpload() {
        var file = $('#dis-signature-file')[0].files[0];
        
        if (!file) {
            alert('Vui lòng chọn file ảnh chữ ký');
            return;
        }
        
        var fileType = file.type;
        if (fileType !== 'image/jpeg' && fileType !== 'image/png' && fileType !== 'image/gif') {
            alert('Vui lòng chọn file JPG, PNG hoặc GIF');
            return;
        }
        
        uploadSignatureImage();
    }
    
    // Lưu hình ảnh đã ký
    function saveSignedImage() {
        // Hiển thị loading
        $('.dis-loading').show();
        
        // Lấy dữ liệu hình ảnh từ canvas
        const dataURL = fabricCanvas.toDataURL({
            format: 'png',
            quality: 1,
            width: originalImageWidth,
            height: originalImageHeight
        });
        
        // Gửi dữ liệu hình ảnh lên server
        $.ajax({
            url: dis_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dis_save_image',
                nonce: dis_ajax.nonce,
                image_data: dataURL
            },
            success: function(response) {
                $('.dis-loading').hide();
                
                if (response.success) {
                    // Đóng Fancybox
                    if (fancyboxInstance) {
                        fancyboxInstance.close();
                        fancyboxInstance = null;
                    }
                    
                    // Xóa canvas
                    if (fabricCanvas) {
                        fabricCanvas.dispose();
                        fabricCanvas = null;
                    }
                    $('.dis-lightbox-canvas-wrapper').remove();
                    
                    // Hiển thị kết quả
                    showResultDialog(response.data.url);
                } else {
                    alert('Lỗi: ' + response.data);
                }
            },
            error: function() {
                $('.dis-loading').hide();
                alert('Đã xảy ra lỗi khi lưu hình ảnh');
            }
        });
    }
    
    // Hiển thị hộp thoại kết quả
    function showResultDialog(imageUrl) {
        $('#dis-result-img').attr('src', imageUrl);
        $('#dis-result-link').attr('href', imageUrl);
        $('#dis-result-fancy-link').attr('href', imageUrl);
        $('.dis-result-dialog').fadeIn();
    }
    
    // Gắn sự kiện cho các nút trong toolbar
    function bindEvents() {
        // Sự kiện click vào icon chữ ký
        $(document).on('click', '.dis-signature-icon', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Ngăn sự kiện lan tỏa
            
            const imgId = $(this).data('img-id');
            const img = $(`img[data-dis-index="${imgId}"]`)[0];
            
            if (img) {
                initSignatureCanvas(img);
            }
        });
        
        // Nút vẽ chữ ký
        $(document).on('click', '#dis-draw', function() {
            if (fabricCanvas) {
                setDrawingMode(true);
            }
        });
        
        // Nút upload ảnh chữ ký
        $(document).on('click', '#dis-upload', function() {
            $('#dis-draw').removeClass('active');
            $(this).addClass('active');
            setDrawingMode(false);
            $('.dis-upload-dialog').show();
        });
        
        // Xem trước file upload
        $(document).on('change', '#dis-signature-file', function() {
            handleSignatureFilePreview();
        });
        
        // Nút chèn ảnh chữ ký
        $(document).on('click', '#dis-upload-submit', function() {
            handleSignatureUpload();
        });
        
        // Nút hủy upload
        $(document).on('click', '#dis-upload-cancel', function() {
            $('.dis-upload-dialog').hide();
            $('#dis-signature-file').val('');
            $('.dis-upload-preview').hide();
        });
        
        // Nút xóa chữ ký
        $(document).on('click', '#dis-clear', function() {
            if (fabricCanvas) {
                // Xóa tất cả các object
                fabricCanvas.clear();
                
                // Thêm lại ảnh gốc
                const lightboxImg = $('.fancybox__content img');
                const imgWidth = lightboxImg.width();
                const imgHeight = lightboxImg.height();
                
                fabric.Image.fromURL(originalImage.src, function(img) {
                    fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas), {
                        scaleX: imgWidth / originalImageWidth,
                        scaleY: imgHeight / originalImageHeight,
                        originX: 'left',
                        originY: 'top'
                    });
                });
                
                // Reset chữ ký object
                signatureObject = null;
            }
        });
        
        // Điều chỉnh kích thước bút vẽ
        $(document).on('input', '#dis-size', function() {
            if (fabricCanvas && isDrawingMode) {
                const size = parseInt($(this).val());
                fabricCanvas.freeDrawingBrush.width = size;
            }
        });
        
        // Điều chỉnh màu bút vẽ
        $(document).on('input', '#dis-color', function() {
            if (fabricCanvas && isDrawingMode) {
                const color = $(this).val();
                fabricCanvas.freeDrawingBrush.color = color;
            }
        });
        
        // Nút lưu hình ảnh
        $(document).on('click', '#dis-save', function() {
            saveSignedImage();
        });
        
        // Nút hủy
        $(document).on('click', '#dis-cancel', function() {
            if (fancyboxInstance) {
                fancyboxInstance.close();
            }
        });
        
        // Nút đóng kết quả
        $(document).on('click', '#dis-result-close', function() {
            $('.dis-result-dialog').hide();
        });
    }
    
    // Khởi tạo plugin
    function init() {
        console.log("Khởi tạo plugin Direct Image Signature");
        
        // Thêm icon ký cho tất cả hình ảnh
        initSignatureIcons();
        
        // Gắn sự kiện
        bindEvents();
        
        // Kiểm tra và thêm lại icon khi tải thêm nội dung AJAX
        $(document).ajaxComplete(function() {
            console.log("AJAX hoàn tất, kiểm tra lại hình ảnh");
            setTimeout(function() {
                addSignatureIconToImages();
            }, 500);
        });
        
        // Kiểm tra lại sau khi trang đã tải hoàn tất
        $(window).on('load', function() {
            console.log("Trang đã tải hoàn tất, kiểm tra lại hình ảnh");
            setTimeout(function() {
                addSignatureIconToImages();
            }, 1000);
        });
    }
    
    // Chạy khởi tạo
    init();
}); 