/**
 * JavaScript xử lý form nhập thông tin hóa đơn
 */

(function($) {
    'use strict';
    
    // Đối tượng xử lý form hóa đơn
    var DIS_Invoice_Form = {
        // Khởi tạo
        init: function() {
            this.setupEventListeners();
            this.setupDatepicker();
            this.setupCurrencyFormat();
        },
        
        // Thiết lập các sự kiện
        setupEventListeners: function() {
            var self = this;
            
            // Xử lý khi submit form
            $('#dis-upload-invoice-form').on('submit', function(e) {
                e.preventDefault();
                
                // Kiểm tra các trường bắt buộc
                if (!self.validateForm()) {
                    return;
                }
                
                // Tiếp tục xử lý submit form
                self.submitForm($(this));
            });
            
            // Tự động tạo số hóa đơn
            $('#dis-generate-invoice-number').on('click', function() {
                var prefix = 'INV';
                var date = new Date();
                var timestamp = date.getTime().toString().slice(-6);
                var invoiceNumber = prefix + '-' + date.getFullYear() + (date.getMonth() + 1).toString().padStart(2, '0') + date.getDate().toString().padStart(2, '0') + '-' + timestamp;
                
                $('#dis-invoice-number').val(invoiceNumber);
            });
        },
        
        // Thiết lập datepicker cho trường ngày
        setupDatepicker: function() {
            if ($.fn.datepicker) {
                $('.dis-datepicker').datepicker({
                    dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },
        
        // Thiết lập định dạng tiền tệ
        setupCurrencyFormat: function() {
            $('.dis-currency').on('input', function() {
                var value = $(this).val().replace(/[^0-9]/g, '');
                if (value) {
                    value = parseInt(value, 10).toLocaleString('vi-VN');
                    $(this).val(value);
                }
            });
        },
        
        // Kiểm tra form
        validateForm: function() {
            var isValid = true;
            var $message = $('#dis-upload-message');
            
            // Kiểm tra tiêu đề
            if ($('#dis-invoice-title').val().trim() === '') {
                $message.html('Vui lòng nhập tiêu đề hóa đơn')
                       .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                       .removeClass('hidden');
                isValid = false;
                return isValid;
            }
            
            // Kiểm tra số hóa đơn
            if ($('#dis-invoice-number').length && $('#dis-invoice-number').val().trim() === '') {
                $message.html('Vui lòng nhập số hóa đơn')
                       .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                       .removeClass('hidden');
                isValid = false;
                return isValid;
            }
            
            // Kiểm tra ngày hóa đơn
            if ($('#dis-invoice-date').length && $('#dis-invoice-date').val().trim() === '') {
                $message.html('Vui lòng chọn ngày hóa đơn')
                       .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                       .removeClass('hidden');
                isValid = false;
                return isValid;
            }
            
            // Kiểm tra số tiền
            if ($('#dis-invoice-amount').length && $('#dis-invoice-amount').val().trim() === '') {
                $message.html('Vui lòng nhập số tiền hóa đơn')
                       .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                       .removeClass('hidden');
                isValid = false;
                return isValid;
            }
            
            // Kiểm tra file hình ảnh
            if ($('#dis-invoice-images').val() === '') {
                $message.html('Vui lòng chọn ít nhất một ảnh hóa đơn')
                       .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                       .removeClass('hidden');
                isValid = false;
                return isValid;
            }
            
            return isValid;
        },
        
        // Submit form
        submitForm: function($form) {
            var $message = $('#dis-upload-message');
            var $button = $form.find('button[type="submit"]');
            var buttonText = $button.text();
            
            $message.html('').removeClass('bg-red-100 text-red-700 border-red-400 bg-green-100 text-green-700 border-green-400').addClass('hidden');
            $button.prop('disabled', true).text('Đang tải lên...');
            
            var formData = new FormData($form[0]);
            formData.append('action', 'dis_create_invoice');
            
            // Thêm dữ liệu ACF nếu có
            if ($('#dis-invoice-number').length) {
                formData.append('invoice_number', $('#dis-invoice-number').val());
            }
            
            if ($('#dis-invoice-date').length) {
                formData.append('invoice_date', $('#dis-invoice-date').val());
            }
            
            if ($('#dis-invoice-amount').length) {
                // Chuyển định dạng tiền tệ về số
                var amount = $('#dis-invoice-amount').val().replace(/\./g, '');
                formData.append('invoice_amount', amount);
            }
            
            if ($('#dis-invoice-currency').length) {
                formData.append('invoice_currency', $('#dis-invoice-currency').val());
            }
            
            if ($('#dis-invoice-customer').length) {
                formData.append('invoice_customer', $('#dis-invoice-customer').val());
            }
            
            $.ajax({
                url: dis_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message.html(response.data.message)
                               .addClass('bg-green-100 text-green-700 border border-green-400 p-3 rounded')
                               .removeClass('hidden');
                        $form.trigger('reset');
                        setTimeout(function() {
                            window.location.href = '?tab=my';
                        }, 1500);
                    } else {
                        $message.html(response.data)
                               .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                               .removeClass('hidden');
                        $button.prop('disabled', false).text(buttonText);
                    }
                },
                error: function() {
                    $message.html('Đã xảy ra lỗi. Vui lòng thử lại sau.')
                           .addClass('bg-red-100 text-red-700 border border-red-400 p-3 rounded')
                           .removeClass('hidden');
                    $button.prop('disabled', false).text(buttonText);
                }
            });
        }
    };
    
    // Khởi tạo khi trang đã tải xong
    $(document).ready(function() {
        DIS_Invoice_Form.init();
    });
    
})(jQuery); 