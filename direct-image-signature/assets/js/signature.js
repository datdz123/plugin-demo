// Thêm log để kiểm tra xem file có được tải hay không

jQuery(document).ready(function($) {
    // Biến lưu trữ thông tin
    let signaturePad = null;
    let currentSlide = 0;
    let signatures = {};
    let signaturePositions = {};
    let uploadedSignature = null;
    let isPopupOpen = false; // Biến kiểm soát trạng thái popup
    let currentInvoiceId = null; // Lưu ID hóa đơn hiện tại
    let invoiceImages = []; // Lưu danh sách ảnh hóa đơn
    

    
    // Kiểm tra xem #dis-container có tồn tại không
    if ($('#dis-container').length === 0) {
        console.error('ERROR: #dis-container not found! Signature functionality will not work properly.');
        console.log('Please make sure the dis_add_to_footer function is being called correctly.');
    } else {
        console.log('#dis-container found, signature functionality should work properly');
    }
    
 
    
    // Xử lý khi click vào nút ký
    $(document).on('click', '.dis-sign-button', function() {
        console.log('Sign button clicked');
        console.log('Button data:', $(this).data());
        
        if (isPopupOpen) {
            console.log('Popup already open, ignoring click');
            return; // Nếu popup đã mở thì không mở lại
        }
        
        isPopupOpen = true;
        currentInvoiceId = $(this).data('invoice-id');
        console.log('Opening signature popup for invoice ID:', currentInvoiceId);
        
        // Lấy danh sách ảnh của hóa đơn
        $.ajax({
            url: dis_signature.ajaxurl,
            type: 'POST',
            data: {
                action: 'dis_get_invoice_images',
                nonce: dis_signature.nonce,
                invoice_id: currentInvoiceId
            },
            success: function(response) {
                console.log('Got invoice images response:', response);
                if (response.success && response.data.images) {
                    // Reset biến lưu trữ chữ ký
                    signatures[currentInvoiceId] = {};
                    signaturePositions[currentInvoiceId] = {};
                    currentSlide = 0;
                    invoiceImages = response.data.images;
                    
                    // Tạo modal để hiển thị ảnh và canvas ký
                    createSignatureModal(response.data.title);
                    
                } else {
                    console.error('Error in invoice images response:', response);
                    alert(dis_signature.i18n.no_images);
                    isPopupOpen = false; // Reset trạng thái popup nếu có lỗi
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert(dis_signature.i18n.error);
                isPopupOpen = false; // Reset trạng thái popup nếu có lỗi
            }
        });
    });
    
    // Tạo modal để hiển thị ảnh và canvas ký
    function createSignatureModal(title) {
        // Xóa modal cũ nếu có
        $('#dis-signature-modal').remove();
        
        // Tạo modal mới
        const $modal = $(`
            <div id="dis-signature-modal" class="fixed inset-0 z-50 overflow-auto bg-black bg-opacity-75 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-[1400px] w-full relative">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h3 class="text-xl font-semibold">${title} - ${dis_signature.i18n.page} <span id="current-page-num">1</span>/${invoiceImages.length}</h3>
                        <button id="close-signature-modal" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-4">
                        <div id="signature-container" class="relative border rounded-lg">
                            <!-- Ảnh và canvas sẽ được thêm vào đây -->
                        </div>
                    </div>
                    <div class="p-4 border-t flex justify-between">
                        <div>
                            <button id="prev-page" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                ${dis_signature.i18n.page} trước
                            </button>
                            <button id="next-page" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                ${dis_signature.i18n.page} sau
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <div>
                            <div class="inline-flex rounded-md shadow-sm gap-3" role="group">
                                <button id="add-signature" class="px-4 py-2 bg-indigo-600 text-white rounded-l hover:bg-indigo-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                    </svg>
                                    Vẽ chữ ký
                                </button>
                                <button id="upload-signature" class="px-4 py-2 bg-blue-600 text-white rounded-r hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Tải lên chữ ký
                                </button>
                                  <button id="save-all-signatures" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 ">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                ${dis_signature.i18n.save_signatures}
                            </button>
                            </div>
                          
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Thêm modal vào body
        $('body').append($modal);
        
        // Hiển thị trang đầu tiên
        showPage(0);
        
        // Xử lý sự kiện đóng modal
        $('#close-signature-modal').on('click', function() {
            $('#dis-signature-modal').remove();
            isPopupOpen = false;
        });
        
        // Xử lý sự kiện chuyển trang
        $('#prev-page').on('click', function() {
            if (currentSlide > 0) {
                showPage(currentSlide - 1);
            }
        });
        
        $('#next-page').on('click', function() {
            if (currentSlide < invoiceImages.length - 1) {
                showPage(currentSlide + 1);
            }
        });
        
        // Xử lý sự kiện click vào nút add-signature
        $('#add-signature').on('click', function() {
            // Xóa tất cả các container chữ ký hiện có
            $('#signature-container').find('.signature-canvas-container, .signature-upload-container, .saved-signature-container').remove();
            
            // Bật chế độ thêm chữ ký
            const $container = $('#signature-container');
            
            // Thêm lớp overlay để xác định vị trí click
            const $overlay = $('<div class="signature-overlay"></div>');
            $overlay.css({
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                zIndex: 20,
                cursor: 'crosshair'
            });
            
            $container.append($overlay);
            
            // Hiển thị thông báo
            const $message = $('<div class="signature-message">Nhấp vào vị trí bạn muốn thêm chữ ký</div>');
            $message.css({
                position: 'absolute',
                top: '10px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: 'rgba(0,0,0,0.7)',
                color: 'white',
                padding: '8px 15px',
                borderRadius: '5px',
                zIndex: 21
            });
            
            $container.append($message);
            
            // Xử lý sự kiện click vào overlay
            $overlay.on('click', function(e) {
                // Lấy vị trí click
                const offset = $(this).offset();
                const relativeX = e.pageX - offset.left;
                const relativeY = e.pageY - offset.top;
                
                // Xóa overlay và thông báo
                $overlay.remove();
                $message.remove();
                
                // Khởi tạo canvas ký tại vị trí đã chọn
                initSignatureCanvas(relativeX, relativeY);
            });
        });

        // Xử lý sự kiện upload chữ ký
        $('#upload-signature').on('click', function() {
            // Bật chế độ thêm chữ ký
            const $container = $('#signature-container');
            
            // Thêm lớp overlay để xác định vị trí click
            const $overlay = $('<div class="signature-overlay"></div>');
            $overlay.css({
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                zIndex: 20,
                cursor: 'crosshair'
            });
            
            $container.append($overlay);
            
            // Hiển thị thông báo
            const $message = $('<div class="signature-message">Nhấp vào vị trí bạn muốn thêm chữ ký</div>');
            $message.css({
                position: 'absolute',
                top: '10px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: 'rgba(0,0,0,0.7)',
                color: 'white',
                padding: '8px 15px',
                borderRadius: '5px',
                zIndex: 21
            });
            
            $container.append($message);
            
            // Xử lý sự kiện click vào overlay
            $overlay.on('click', function(e) {
                // Lấy vị trí click
                const offset = $(this).offset();
                const relativeX = e.pageX - offset.left;
                const relativeY = e.pageY - offset.top;
                
                // Xóa overlay và thông báo
                $overlay.remove();
                $message.remove();
                
                // Khởi tạo khung upload chữ ký tại vị trí đã chọn
                initSignatureUpload(relativeX, relativeY);
            });
        });
        
        // Xử lý sự kiện lưu tất cả chữ ký
        $('#save-all-signatures').on('click', function() {
            console.log('Save button clicked');
            console.log('Current signatures object:', signatures);
            console.log('Current positions object:', signaturePositions);
            saveSignatures();
        });
    }
    
    // Hiển thị trang hóa đơn
    function showPage(pageIndex) {
        if (pageIndex < 0 || pageIndex >= invoiceImages.length) {
            console.error('Invalid page index:', pageIndex);
            return;
        }
        
        currentSlide = pageIndex;
        
        // Cập nhật số trang hiện tại
        $('#current-page-num').text(pageIndex + 1);
        
        // Lấy thông tin ảnh
        const image = invoiceImages[pageIndex];
        
        // Xóa nội dung cũ
        const $container = $('#signature-container');
        $container.empty();
        
        // Tạo phần tử ảnh
        const $img = $('<img>', {
            src: image.url,
            id: 'invoice-image',
            class: 'max-w-full h-auto object-contain'
        });
        
        // Thêm ảnh vào container
        $container.append($img);
        
        // Khi ảnh đã tải xong
        $img.on('load', function() {
            // Thiết lập kích thước container phù hợp với ảnh
            const containerWidth = $container.parent().width();
            const imgWidth = $(this).width();
            const imgHeight = $(this).height();
            
            // Tính toán tỷ lệ để hiển thị ảnh full width
            const ratio = imgHeight / imgWidth;
            const newWidth = containerWidth;
            const newHeight = containerWidth * ratio;
            
            // Thiết lập kích thước mới cho ảnh và container
            $(this).css({
                width: '100%',
                height: 'auto',
                maxHeight: '70vh'
            });
            
            $container.css({
                width: '100%',
                height: 'auto',
                position: 'relative'
            });
            
            // Hiển thị chữ ký đã lưu nếu có
            if (signatures[currentInvoiceId] && signatures[currentInvoiceId][currentSlide]) {
                const pageSignatures = signatures[currentInvoiceId][currentSlide];
                const pagePositions = signaturePositions[currentInvoiceId][currentSlide];
                
                console.log('Displaying signatures for page', currentSlide, ':', pageSignatures);
                console.log('Signature positions:', pagePositions);
                
                // Kiểm tra nếu là mảng chữ ký
                if (Array.isArray(pageSignatures)) {
                    // Hiển thị từng chữ ký
                    pageSignatures.forEach((sigData, index) => {
                        if (pagePositions && pagePositions[index]) {
                            showSavedSignature(sigData, pagePositions[index]);
                        } else {
                            console.error('Missing position data for signature', index);
                        }
                    });
                } else {
                    // Nếu chỉ có một chữ ký
                    showSavedSignature(pageSignatures, pagePositions);
                }
            } else {
                console.log('No signatures found for page', currentSlide);
            }
        });
    }
    
    function initSignatureCanvas(x, y) {
        console.log('Initializing signature canvas at position:', x, y);
    
        const $container = $('#signature-container');
        
        // Xóa tất cả các container chữ ký hiện có (cả canvas và upload)
        $container.find('.signature-canvas-container, .signature-upload-container').remove();
    
        const $image = $('#invoice-image');
        if ($image.length === 0) {
            console.error('Image not found');
            return;
        }
    
        const imageWidth = $image.width();
        const imageHeight = $image.height();
        console.log('Image dimensions:', imageWidth, 'x', imageHeight);
    
        const $canvasContainer = $('<div class="signature-canvas-container"></div>');
        $container.append($canvasContainer);
    
        const $signatureBox = $('<div class="signature-box"></div>');
        $canvasContainer.append($signatureBox);
    
        const boxWidth = Math.min(300, imageWidth * 0.7);
        const boxHeight = Math.min(150, imageHeight * 0.3);
    
        let boxLeft, boxTop;
        if (typeof x === 'number' && typeof y === 'number') {
            boxLeft = Math.max(0, Math.min(x - boxWidth / 2, imageWidth - boxWidth));
            boxTop = Math.max(0, Math.min(y - boxHeight / 2, imageHeight - boxHeight));
        } else {
            boxLeft = (imageWidth - boxWidth) / 2;
            boxTop = (imageHeight - boxHeight) / 2;
        }
    
        console.log('Signature box position and size:', boxLeft, boxTop, boxWidth, boxHeight);
    
        $signatureBox.css({
            position: 'absolute',
            width: boxWidth + 'px',
            height: boxHeight + 'px',
            left: boxLeft + 'px',
            top: boxTop + 'px',
            border: '2px dashed #2196F3',
            backgroundColor: 'rgba(255, 255, 255, 0.3)',
            cursor: 'move',
            zIndex: 20
        });
    
        const $canvas = $('<canvas class="signature-canvas"></canvas>');
        $signatureBox.append($canvas);
    
        $canvas.attr('width', boxWidth);
        $canvas.attr('height', boxHeight);
        $canvas.css({
            width: '100%',
            height: '100%',
            cursor: 'crosshair'
        });
    
        // Khởi tạo SignaturePad
        try {
            console.log('Initializing SignaturePad');
            signaturePad = new SignaturePad($canvas[0], {
                minWidth: 1,
                maxWidth: 3,
                penColor: '#000000',
                backgroundColor: 'rgba(255, 255, 255, 0)',
                velocityFilterWeight: 0.7
            });
            console.log('SignaturePad initialized successfully');
    
            // Thêm sự kiện để theo dõi
            $canvas.on('mousedown touchstart', function(e) {
                console.log('Canvas mousedown/touchstart event detected');
            });
    
            $canvas.on('mouseup touchend', function(e) {
                console.log('Canvas mouseup/touchend event detected');
                console.log('Is signature empty after event?', signaturePad.isEmpty());
            });
    
            if (typeof signaturePad.addEventListener === 'function') {
                signaturePad.addEventListener('beginStroke', function() {
                    console.log('Signature drawing started');
                });
                signaturePad.addEventListener('endStroke', function() {
                    console.log('Signature drawing ended');
                    console.log('Is signature empty?', signaturePad.isEmpty());
                });
            }
        } catch (error) {
            console.error('Error initializing SignaturePad:', error);
            alert('Không thể khởi tạo công cụ ký. Vui lòng thử lại.');
            $canvasContainer.remove();
            return;
        }
    
        // Thêm thanh công cụ với các nút điều khiển
        const $toolbar = $('<div class="signature-toolbar"></div>');
        
        // Thêm điều khiển kích thước
        const $sizeControl = $('<div class="flex items-center mr-4 bg-white px-3 py-1 rounded">' +
            '<label class="text-gray-700 mr-2">Kích thước:</label>' +
            '<input type="range" class="signature-size w-24" min="1" max="10" value="3" />' +
        '</div>');
        
        // Thêm điều khiển màu sắc
        const $colorControl = $('<div class="flex items-center mr-4 bg-white px-3 py-1 rounded">' +
            '<label class="text-gray-700 mr-2">Màu sắc:</label>' +
            '<input type="color" class="signature-color h-8" value="#000000" />' +
        '</div>');

        // Các nút điều khiển
        const $clearButton = $('<button class="clear-signature-btn px-3 py-1 mr-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">' + dis_signature.i18n.clear + '</button>');
        const $saveButton = $('<button class="save-signature-btn px-3 py-1 mr-2 bg-green-500 text-white rounded hover:bg-green-600">' + dis_signature.i18n.apply + '</button>');
        const $cancelButton = $('<button class="cancel-signature-btn px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">' + dis_signature.i18n.cancel + '</button>');

        // Thêm tất cả vào toolbar
        $toolbar.append($sizeControl)
               .append($colorControl)
               .append($clearButton)
               .append($saveButton)
               .append($cancelButton);

        $canvasContainer.append($toolbar);

        // Điều chỉnh CSS cho thanh công cụ
        $toolbar.css({
            position: 'absolute',
            bottom: '-60px',
            left: '0',
            width: '100%',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '10px',
            zIndex: '25',
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            borderRadius: '5px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
        });

        // Xử lý sự kiện thay đổi kích thước
        $sizeControl.find('.signature-size').on('input', function() {
            const size = parseFloat($(this).val());
            signaturePad.minWidth = size;
            signaturePad.maxWidth = size * 2;
        });

        // Xử lý sự kiện thay đổi màu
        $colorControl.find('.signature-color').on('input', function() {
            signaturePad.penColor = $(this).val();
        });

        // Xử lý sự kiện nút Xóa
        $clearButton.on('click', function() {
            signaturePad.clear();
            console.log('Signature cleared');
        });

        // Xử lý sự kiện nút Lưu/Áp dụng
        $saveButton.on('click', function() {
            console.log('Save button clicked');
            if (signaturePad.isEmpty()) {
                alert(dis_signature.i18n.empty_signature);
                return;
            }

            try {
                // Lấy dữ liệu chữ ký
                const signatureData = signaturePad.toDataURL();
                console.log('Generated signature data length:', signatureData.length);
                
                // Xóa chữ ký cũ nếu có
                if (signatures[currentInvoiceId] && signatures[currentInvoiceId][currentSlide]) {
                    signatures[currentInvoiceId][currentSlide] = [];
                    signaturePositions[currentInvoiceId][currentSlide] = [];
                    // Xóa hiển thị chữ ký cũ
                    $container.find('.saved-signature-container').remove();
                }
                
                // Khởi tạo cấu trúc dữ liệu nếu chưa có
                if (!signatures[currentInvoiceId]) {
                    console.log('Creating new signatures object for invoice:', currentInvoiceId);
                    signatures[currentInvoiceId] = {};
                }
                
                if (!signatures[currentInvoiceId][currentSlide]) {
                    console.log('Creating new signatures array for slide:', currentSlide);
                    signatures[currentInvoiceId][currentSlide] = [];
                }
                
                // Lưu vị trí chữ ký
                const position = {
                    top: $signatureBox.position().top,
                    left: $signatureBox.position().left,
                    width: $signatureBox.width(),
                    height: $signatureBox.height()
                };
                
                // Thêm chữ ký và vị trí vào mảng
                signatures[currentInvoiceId][currentSlide].push(signatureData);
                
                if (!signaturePositions[currentInvoiceId]) {
                    signaturePositions[currentInvoiceId] = {};
                }
                
                if (!signaturePositions[currentInvoiceId][currentSlide]) {
                    signaturePositions[currentInvoiceId][currentSlide] = [];
                }
                
                signaturePositions[currentInvoiceId][currentSlide].push(position);
                
                console.log('Signature saved successfully');
                console.log('Current signatures:', signatures);
                console.log('Current positions:', signaturePositions);
                
                // Hiển thị chữ ký đã lưu
                showSavedSignature(signatureData, position);
                
                // Xóa canvas
                $canvasContainer.remove();
            } catch (error) {
                console.error('Error saving signature:', error);
                alert('Có lỗi khi lưu chữ ký. Vui lòng thử lại.');
            }
        });

        // Xử lý sự kiện nút Hủy
        $cancelButton.on('click', function() {
            console.log('Cancel button clicked');
            $canvasContainer.remove();
        });

        // Làm cho khung chữ ký có thể kéo thả
        $signatureBox.draggable({
            containment: 'parent',
            handle: $canvas, // Chỉ cho phép kéo thả bằng canvas
            start: function(event, ui) {
                $(this).css('cursor', 'move');
                event.stopPropagation(); // Ngăn sự kiện bubble lên
            },
            drag: function(event, ui) {
                event.stopPropagation();
            },
            stop: function(event, ui) {
                $(this).css('cursor', 'default');
                updateSignaturePad();
                event.stopPropagation();
            }
        });

        // Cho phép thay đổi kích thước
        $signatureBox.resizable({
            containment: 'parent',
            minWidth: 100,
            minHeight: 50,
            handles: 'all',
            start: function(event, ui) {
                event.stopPropagation();
            },
            resize: function(event, ui) {
                updateSignaturePad();
                event.stopPropagation();
            },
            stop: function(event, ui) {
                event.stopPropagation();
            }
        });

        function updateSignaturePad() {
            console.log('Updating SignaturePad');
            const width = $signatureBox.width();
            const height = $signatureBox.height();

            let signatureData = null;
            let currentColor = signaturePad.penColor;
            let currentMaxWidth = signaturePad.maxWidth;
            let currentMinWidth = signaturePad.minWidth;

            if (signaturePad && !signaturePad.isEmpty()) {
                signatureData = signaturePad.toDataURL();
                console.log('Saved signature data before resize');
            }

            $canvas.attr('width', width);
            $canvas.attr('height', height);

            signaturePad = new SignaturePad($canvas[0], {
                minWidth: currentMinWidth,
                maxWidth: currentMaxWidth,
                penColor: currentColor,
                backgroundColor: 'rgba(255, 255, 255, 0)',
                velocityFilterWeight: 0.7
            });

            if (signatureData) {
                signaturePad.fromDataURL(signatureData);
                console.log('Restored signature data after resize');
            }

            $canvas.on('mousedown touchstart', function(e) {
                console.log('Canvas mousedown/touchstart event detected');
            });

            $canvas.on('mouseup touchend', function(e) {
                console.log('Canvas mouseup/touchend event detected');
                console.log('Is signature empty after event?', signaturePad.isEmpty());
            });

            if (typeof signaturePad.addEventListener === 'function') {
                signaturePad.addEventListener('beginStroke', function() {
                    console.log('Signature drawing started');
                });
                signaturePad.addEventListener('endStroke', function() {
                    console.log('Signature drawing ended');
                    console.log('Is signature empty?', signaturePad.isEmpty());
                });
            }
        }
    }
    
    // Hiển thị chữ ký đã lưu
    function showSavedSignature(signatureData, position) {
        const $container = $('#signature-container');
        const $image = $('#invoice-image');
        
        // Tạo container cho chữ ký
        const $signatureContainer = $('<div class="saved-signature-container"></div>');
        $container.append($signatureContainer);
        
        // Tạo ảnh chữ ký
        const $signature = $('<img class="saved-signature">');
        
        // Thiết lập style cho container
        $signatureContainer.css({
            position: 'absolute',
            top: position.top + 'px',
            left: position.left + 'px',
            width: position.width + 'px',
            height: position.height + 'px',
            cursor: 'move',
            zIndex: 20
        });
        
        // Thiết lập style cho ảnh chữ ký
        $signature.css({
            width: '100%',
            height: '100%',
            objectFit: 'contain'
        });
        
        // Gán source cho ảnh
        $signature.attr('src', signatureData);
        $signatureContainer.append($signature);
        
        // Thêm nút xóa chữ ký
        const $removeButton = $('<button class="remove-signature-btn">×</button>');
        $signatureContainer.append($removeButton);
        
        // Làm cho chữ ký có thể di chuyển
        $signatureContainer.draggable({
            containment: 'parent',
            stop: function(event, ui) {
                // Cập nhật vị trí mới
                const newPosition = {
                    top: ui.position.top,
                    left: ui.position.left,
                    width: position.width,
                    height: position.height
                };
                signaturePositions[currentInvoiceId][currentSlide][0] = newPosition;
            }
        });
        
        // Cho phép thay đổi kích thước
        $signatureContainer.resizable({
            containment: 'parent',
            minWidth: 100,
            minHeight: 50,
            handles: 'all',
            stop: function(event, ui) {
                // Cập nhật kích thước mới
                const newPosition = {
                    top: ui.position.top,
                    left: ui.position.left,
                    width: ui.size.width,
                    height: ui.size.height
                };
                signaturePositions[currentInvoiceId][currentSlide][0] = newPosition;
            }
        });
        
        // Xử lý sự kiện click vào nút xóa
        $removeButton.on('click', function() {
            // Xóa chữ ký khỏi mảng dữ liệu
            signatures[currentInvoiceId][currentSlide] = [];
            signaturePositions[currentInvoiceId][currentSlide] = [];
            // Xóa hiển thị chữ ký
            $signatureContainer.remove();
        });
    }
    
    function saveSignatures() {
        if (!signatures || typeof signatures !== 'object') {
            console.error('Signatures object is not initialized properly');
            alert(dis_signature.i18n.error);
            return;
        }
    
        if (!signatures[currentInvoiceId]) {
            console.error('No signatures object for invoice ID:', currentInvoiceId);
            alert(dis_signature.i18n.no_signatures);
            return;
        }
    
        let hasSignatures = false;
        for (const pageIndex in signatures[currentInvoiceId]) {
            const pageSignatures = signatures[currentInvoiceId][pageIndex];
            if (Array.isArray(pageSignatures) && pageSignatures.length > 0) {
                hasSignatures = true;
                break;
            }
        }
    
        if (!hasSignatures) {
            console.error('No actual signatures found for invoice ID:', currentInvoiceId);
            alert(dis_signature.i18n.no_signatures);
            return;
        }
    
        console.log('Saving merged images for invoice ID:', currentInvoiceId);
    
        // Tạo hình ảnh kết hợp cho mỗi trang
        const mergedImages = {};
    
        // Hiển thị thông báo đang xử lý
        const $loadingMessage = $('<div class="dis-loading-message">Đang xử lý hình ảnh...</div>');
        $loadingMessage.css({
            position: 'fixed',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            backgroundColor: 'rgba(0,0,0,0.7)',
            color: 'white',
            padding: '15px 20px',
            borderRadius: '5px',
            zIndex: 9999
        });
        $('body').append($loadingMessage);
    
        // Hàm tạo hình ảnh kết hợp
        function createMergedImage(pageIndex, callback) {
            if (!invoiceImages[pageIndex] || !signatures[currentInvoiceId][pageIndex]) {
                callback();
                return;
            }
    
            const imageUrl = invoiceImages[pageIndex].url;
            const pageSignatures = signatures[currentInvoiceId][pageIndex];
            const pagePositions = signaturePositions[currentInvoiceId][pageIndex];
    
            // Tạo một canvas mới để vẽ ảnh gốc
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                
                // Vẽ ảnh gốc
                ctx.drawImage(img, 0, 0, img.width, img.height);
                
                // Vẽ từng chữ ký lên ảnh
                let signaturesProcessed = 0;
                
                function processSignature(index) {
                    if (index >= pageSignatures.length) {
                        mergedImages[pageIndex] = canvas.toDataURL('image/png');
                        callback();
                        return;
                    }
                    
                    const sigImg = new Image();
                    sigImg.onload = function() {
                        const position = pagePositions[index];
                        
                        // Lấy thông tin về kích thước thật của ảnh gốc và ảnh hiển thị
                        const $displayImg = $('#invoice-image');
                        const displayWidth = $displayImg.width();
                        const displayHeight = $displayImg.height();
                        
                        // Tính toán tỷ lệ scale dựa trên chiều rộng hoặc chiều cao, chọn cái nhỏ hơn
                        const displayRatio = displayWidth / displayHeight;
                        const originalRatio = img.width / img.height;
                        
                        let scale;
                        if (displayRatio > originalRatio) {
                            scale = img.height / displayHeight;
                        } else {
                            scale = img.width / displayWidth;
                        }
                        
                        // Tính offset để căn giữa ảnh
                        const displayX = (displayWidth - (img.width / scale)) / 2;
                        const displayY = (displayHeight - (img.height / scale)) / 2;
                        
                        // Tính toán vị trí thực tế trên ảnh gốc
                        const actualLeft = Math.round((position.left - displayX) * scale);
                        const actualTop = Math.round((position.top - displayY) * scale);
                        
                        // Tính toán kích thước thực tế của chữ ký giữ nguyên tỷ lệ
                        const sigRatio = sigImg.width / sigImg.height;
                        let actualWidth, actualHeight;
                        
                        if (position.width / position.height > sigRatio) {
                            // Nếu khung chữ ký rộng hơn, lấy chiều cao làm chuẩn
                            actualHeight = Math.round(position.height * scale);
                            actualWidth = Math.round(actualHeight * sigRatio);
                        } else {
                            // Nếu khung chữ ký cao hơn, lấy chiều rộng làm chuẩn
                            actualWidth = Math.round(position.width * scale);
                            actualHeight = Math.round(actualWidth / sigRatio);
                        }
                        
                        console.log('Signature original ratio:', sigRatio);
                        console.log('Display dimensions:', displayWidth, 'x', displayHeight);
                        console.log('Original dimensions:', img.width, 'x', img.height);
                        console.log('Scale factor:', scale);
                        console.log('Display offsets:', displayX, displayY);
                        console.log('Display position:', position);
                        console.log('Actual position:', actualLeft, actualTop, actualWidth, actualHeight);
                        
                        // Tạo canvas tạm để xử lý chữ ký
                        const tempCanvas = document.createElement('canvas');
                        const tempCtx = tempCanvas.getContext('2d');
                        
                        // Đặt kích thước cho canvas tạm
                        tempCanvas.width = actualWidth;
                        tempCanvas.height = actualHeight;
                        
                        // Vẽ chữ ký vào canvas tạm với chất lượng cao
                        tempCtx.imageSmoothingEnabled = true;
                        tempCtx.imageSmoothingQuality = 'high';
                        tempCtx.drawImage(sigImg, 0, 0, actualWidth, actualHeight);
                        
                        // Vẽ chữ ký từ canvas tạm lên canvas chính
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(tempCanvas, actualLeft, actualTop);
                        
                        processSignature(index + 1);
                    };
                    sigImg.onerror = function() {
                        console.error('Error loading signature image');
                        processSignature(index + 1);
                    };
                    sigImg.src = pageSignatures[index];
                }
                
                processSignature(0);
            };
            
            img.onerror = function() {
                console.error('Error loading original image');
                callback();
            };
            
            img.src = imageUrl;
        }
    
        // Xử lý từng trang một
        const pageIndices = Object.keys(signatures[currentInvoiceId]);
        let processedPages = 0;
        
        function processNextPage(index) {
            if (index >= pageIndices.length) {
                // Tất cả các trang đã được xử lý, gửi dữ liệu lên server
                sendDataToServer();
                return;
            }
            
            const pageIndex = pageIndices[index];
            createMergedImage(pageIndex, function() {
                processedPages++;
                processNextPage(index + 1);
            });
        }
        
        function sendDataToServer() {
            // Chỉ gửi dữ liệu hình ảnh đã ghép
            const mergedImagesJSON = JSON.stringify(mergedImages);
            
            console.log('Merged images data:', mergedImages);
            
            $.ajax({
                url: dis_signature.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dis_save_signatures',
                    nonce: dis_signature.nonce,
                    invoice_id: currentInvoiceId,
                    merged_images: mergedImagesJSON
                },
                success: function(response) {
                    $loadingMessage.remove();
                    console.log('Signatures saved response:', response);
                    if (response.success) {
                        alert(response.data.message || 'Lưu chữ ký thành công');
                        $('#dis-signature-modal').remove();
                        isPopupOpen = false;
                        if (response.data.reload) window.location.reload();
                    } else {
                        console.error('Error saving signatures:', response);
                        alert(response.data.message || dis_signature.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    $loadingMessage.remove();
                    console.error('AJAX error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    alert(dis_signature.i18n.error);
                }
            });
        }
        
        // Bắt đầu xử lý
        processNextPage(0);
    }

    // Hàm khởi tạo khung upload chữ ký
    function initSignatureUpload(x, y) {
        console.log('Initializing signature upload at position:', x, y);
    
        const $container = $('#signature-container');
        
        // Xóa tất cả các container chữ ký hiện có (cả canvas và upload)
        $container.find('.signature-canvas-container, .signature-upload-container').remove();
    
        const $image = $('#invoice-image');
        if ($image.length === 0) {
            console.error('Image not found');
            return;
        }
    
        const imageWidth = $image.width();
        const imageHeight = $image.height();
        console.log('Image dimensions:', imageWidth, 'x', imageHeight);
    
        const $uploadContainer = $('<div class="signature-upload-container"></div>');
        $container.append($uploadContainer);
    
        const $signatureBox = $('<div class="signature-box"></div>');
        $uploadContainer.append($signatureBox);
    
        const boxWidth = Math.min(300, imageWidth * 0.7);
        const boxHeight = Math.min(150, imageHeight * 0.3);
    
        let boxLeft, boxTop;
        if (typeof x === 'number' && typeof y === 'number') {
            boxLeft = Math.max(0, Math.min(x - boxWidth / 2, imageWidth - boxWidth));
            boxTop = Math.max(0, Math.min(y - boxHeight / 2, imageHeight - boxHeight));
        } else {
            boxLeft = (imageWidth - boxWidth) / 2;
            boxTop = (imageHeight - boxHeight) / 2;
        }
    
        console.log('Signature box position and size:', boxLeft, boxTop, boxWidth, boxHeight);
    
        $signatureBox.css({
            position: 'absolute',
            width: boxWidth + 'px',
            height: boxHeight + 'px',
            left: boxLeft + 'px',
            top: boxTop + 'px',
            border: '2px dashed #2196F3',
            backgroundColor: 'rgba(255, 255, 255, 0.3)',
            cursor: 'move',
            zIndex: 20,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
        });
    
        // Thêm form upload
        const $uploadForm = $('<div class="upload-form"></div>');
        $signatureBox.append($uploadForm);
    
        // Thêm input file
        const $fileInput = $('<input type="file" id="signature-file" accept="image/*" style="display:none;">');
        const $uploadButton = $('<button class="upload-button px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>Chọn ảnh</button>');
        $uploadForm.append($fileInput).append($uploadButton);
    
        $uploadButton.on('click', function() {
            $fileInput.click();
        });
    
        // Xử lý khi chọn file
        $fileInput.on('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Xóa nội dung cũ
                    $uploadForm.empty();
                    
                    // Tạo ảnh preview
                    const $preview = $('<img class="signature-preview" src="' + e.target.result + '" style="max-width:100%; max-height:100%; object-fit:contain;">');
                    $uploadForm.append($preview);
                    
                    // Làm cho khung chữ ký có thể kéo thả và thay đổi kích thước
                    try {
                        $signatureBox.draggable({
                            containment: 'parent',
                            start: function() { $(this).css('cursor', 'move'); },
                            stop: function() {
                                $(this).css('cursor', 'default');
                            }
                        });
                
                        $signatureBox.resizable({
                            containment: 'parent',
                            minWidth: 100,
                            minHeight: 50,
                            handles: 'all',
                            resize: function(event, ui) {
                                // Đảm bảo ảnh preview luôn fit trong box
                                $preview.css({
                                    maxWidth: '100%',
                                    maxHeight: '100%',
                                    objectFit: 'contain'
                                });
                            }
                        });
                    } catch (error) {
                        console.error('Error initializing draggable/resizable:', error);
                    }
                    
                    // Thêm thanh công cụ
                    addToolbar($uploadContainer, $signatureBox, e.target.result);
                };
                
                reader.readAsDataURL(file);
            }
        });
    
        // Làm cho khung chữ ký có thể kéo thả và thay đổi kích thước
        try {
            $signatureBox.draggable({
                containment: 'parent',
                start: function() { $(this).css('cursor', 'move'); },
                stop: function() {
                    $(this).css('cursor', 'default');
                }
            });
    
            $signatureBox.resizable({
                containment: 'parent',
                minWidth: 100,
                minHeight: 50,
                handles: 'all'
            });
        } catch (error) {
            console.error('Error initializing draggable/resizable:', error);
        }
    
        // Thêm nút hủy
        const $cancelButton = $('<button class="cancel-upload-btn px-2 py-1 bg-red-500 text-white rounded-full absolute top-2 right-2 hover:bg-red-600 flex items-center justify-center" style="width:24px;height:24px;">×</button>');
        $uploadContainer.append($cancelButton);
    
        $cancelButton.on('click', function() {
            $uploadContainer.remove();
        });
    }

    // Hàm thêm thanh công cụ cho ảnh đã upload
    function addToolbar($container, $signatureBox, imageData) {
        // Thêm thanh công cụ
        const $toolbar = $('<div class="signature-toolbar"></div>');
        const $saveButton = $('<button class="save-signature-btn px-3 py-1 mr-2 bg-green-500 text-white rounded hover:bg-green-600">' + dis_signature.i18n.apply + '</button>');
        const $cancelButton = $('<button class="cancel-signature-btn px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">' + dis_signature.i18n.cancel + '</button>');
    
        $toolbar.append($saveButton).append($cancelButton);
        $container.append($toolbar);
    
        // Điều chỉnh CSS cho thanh công cụ để đảm bảo nó hiển thị đúng
        $toolbar.css({
            position: 'absolute',
            bottom: '-50px',
            left: '0',
            width: '100%',
            textAlign: 'center',
            padding: '10px',
            zIndex: '25',
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            borderRadius: '0 0 5px 5px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
        });
    
        // Xử lý sự kiện nút Lưu/Áp dụng
        $saveButton.on('click', function() {
            console.log('Save uploaded signature button clicked');
    
            try {
                // Xóa chữ ký cũ nếu có
                if (signatures[currentInvoiceId] && signatures[currentInvoiceId][currentSlide]) {
                    signatures[currentInvoiceId][currentSlide] = [];
                    signaturePositions[currentInvoiceId][currentSlide] = [];
                    // Xóa hiển thị chữ ký cũ
                    $('#signature-container').find('.saved-signature-container').remove();
                }
                
                // Lưu vị trí chữ ký
                const position = {
                    top: $signatureBox.position().top,
                    left: $signatureBox.position().left,
                    width: $signatureBox.width(),
                    height: $signatureBox.height()
                };
                
                // Khởi tạo cấu trúc dữ liệu nếu chưa có
                if (!signatures[currentInvoiceId]) {
                    console.log('Creating new signatures object for invoice:', currentInvoiceId);
                    signatures[currentInvoiceId] = {};
                }
                
                if (!signatures[currentInvoiceId][currentSlide]) {
                    console.log('Creating new signatures array for slide:', currentSlide);
                    signatures[currentInvoiceId][currentSlide] = [];
                }
                
                // Thêm chữ ký và vị trí vào mảng
                signatures[currentInvoiceId][currentSlide].push(imageData);
                
                if (!signaturePositions[currentInvoiceId]) {
                    signaturePositions[currentInvoiceId] = {};
                }
                
                if (!signaturePositions[currentInvoiceId][currentSlide]) {
                    signaturePositions[currentInvoiceId][currentSlide] = [];
                }
                
                signaturePositions[currentInvoiceId][currentSlide].push(position);
                
                console.log('Signature saved successfully');
                console.log('Current signatures:', signatures);
                console.log('Current positions:', signaturePositions);
                
                // Hiển thị chữ ký đã lưu
                showSavedSignature(imageData, position);
                
                // Xóa container
                $container.remove();
            } catch (error) {
                console.error('Error saving signature:', error);
                alert('Có lỗi khi lưu chữ ký. Vui lòng thử lại.');
            }
        });
    
        // Xử lý sự kiện nút Hủy
        $cancelButton.on('click', function() {
            console.log('Cancel button clicked');
            $container.remove();
        });
    }
}); 