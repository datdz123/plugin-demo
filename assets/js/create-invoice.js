jQuery(document).ready(function($) {
    console.log('create-invoice.js loaded');
    
    // Kiểm tra xem modal có tồn tại không
    console.log('Modal element:', $('#createInvoiceModal').length);
    console.log('Modal initial display:', $('#createInvoiceModal').css('display'));
    
    // Xử lý hiển thị modal tạo hóa đơn
    $('#createInvoiceBtn').on('click', function() {
        console.log('Create invoice button clicked');
        var modal = $('#createInvoiceModal');
        console.log('Modal before removing hidden:', modal.attr('class'));
        modal.removeClass('hidden');
        console.log('Modal after removing hidden:', modal.attr('class'));
        console.log('Modal display after click:', modal.css('display'));
    });
    
    // Đóng modal
    $('#closeInvoiceModal, #cancelCreateInvoice').on('click', function() {
        console.log('Close modal button clicked');
        $('#createInvoiceModal').addClass('hidden');
        // Reset form khi đóng modal
        $('#createInvoiceModal form')[0].reset();
        // Xóa preview hình ảnh
        $('#image_preview').empty();
        // Reset danh sách người ký
        $('#invoice_signers option').show();
    });

    // Xử lý xem trước hình ảnh
    $('#invoice_images').on('change', function() {
        const preview = $('#image_preview');
        preview.empty(); // Xóa các preview cũ

        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = $('<div>').addClass('relative group');
                        const img = $('<img>')
                            .attr('src', e.target.result)
                            .addClass('w-full h-32 object-cover rounded-lg');
                        
                        // Thêm nút xóa
                        const removeBtn = $('<button>')
                            .addClass('absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 m-1 opacity-0 group-hover:opacity-100 transition-opacity')
                            .html('<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>')
                            .on('click', function(e) {
                                e.preventDefault();
                                div.remove();
                                
                                // Cập nhật input file
                                const dt = new DataTransfer();
                                const input = $('#invoice_images')[0];
                                const { files } = input;
                                
                                for(let i = 0; i < files.length; i++) {
                                    if(i !== index) {
                                        dt.items.add(files[i]);
                                    }
                                }
                                
                                input.files = dt.files;
                            });

                        div.append(img).append(removeBtn);
                        preview.append(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // Xử lý lọc người ký theo chức vụ
    $('#invoice_position').on('change', function() {
        const selectedPosition = $(this).val();
        const signerSelect = $('#invoice_signers');
        
        if (selectedPosition === '') {
            // Hiển thị tất cả người ký
            signerSelect.find('option').show();
        } else {
            // Ẩn/hiện người ký theo chức vụ
            signerSelect.find('option').each(function() {
                const position = $(this).data('position');
                if (position === selectedPosition) {
                    $(this).show();
                } else {
                    $(this).hide();
                    // Bỏ chọn nếu option bị ẩn
                    if ($(this).is(':selected')) {
                        $(this).prop('selected', false);
                    }
                }
            });
        }
    });
}); 