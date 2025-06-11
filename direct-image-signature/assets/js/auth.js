jQuery(document).ready(function($) {
    // Debug: Hiển thị nonce
    console.log('DIS Auth Nonce:', dis_auth ? dis_auth.nonce : 'undefined');
    console.log('DIS Auth AJAX URL:', dis_auth ? dis_auth.ajaxurl : 'undefined');
    
    // Hàm hiển thị lỗi cho input
    function showError(inputElement, message) {
        const errorDiv = inputElement.siblings('.error-message');
        if (errorDiv.length === 0) {
            inputElement.parent().append(`<div class="error-message text-red-500 text-sm mt-1">${message}</div>`);
        } else {
            errorDiv.text(message);
        }
        inputElement.addClass('border-red-500');
    }

    // Hàm xóa lỗi cho input
    function clearError(inputElement) {
        inputElement.siblings('.error-message').remove();
        inputElement.removeClass('border-red-500');
    }

    // Hàm validate mật khẩu
    function validatePassword(password) {
        if (password.length < 8) {
            return 'Mật khẩu phải có ít nhất 8 ký tự';
        }
        if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
            return 'Mật khẩu phải chứa cả chữ cái và số';
        }
        return '';
    }

    // Hiển thị popup đăng nhập/đăng ký
    function showAuthPopup() {
        Swal.fire({
            title: false,
            html: `
                <div class="w-full mx-auto">
                    <!-- Tab navigation -->
                    <div class="flex">
                        <div class="w-1/2 py-4 text-center cursor-pointer border-b-2 border-blue-500 text-blue-500 font-semibold text-lg transition-all" data-tab="login">Đăng nhập</div>
                        <div class="w-1/2 py-4 text-center cursor-pointer text-gray-500 font-semibold text-lg hover:text-gray-700 transition-all" data-tab="register">Đăng ký</div>
                    </div>
                    
                    <div class="p-8">
                        <!-- Form đăng nhập -->
                        <div class="block" data-content="login">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Chào mừng trở lại!</h3>
                            <p class="text-gray-600 mb-8">Vui lòng đăng nhập để tiếp tục</p>
                            
                            <form id="dis-login-form" class="space-y-4">
                                <div>
                                    <input type="text" id="login-username" name="username" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Email hoặc tên đăng nhập">
                                </div>
                                
                                <div>
                                    <div class="relative">
                                        <input type="password" id="login-password" name="password" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="Mật khẩu">
                                        <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input id="remember-me" name="remember-me" type="checkbox" 
                                            class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                                        <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer">Ghi nhớ đăng nhập</label>
                                    </div>
                                    <div class="text-sm">
                                        <a href="#" class="font-medium text-blue-500 hover:text-blue-600 transition-colors">Quên mật khẩu?</a>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="submit" 
                                        class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 text-white text-lg font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Đăng Nhập
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Form đăng ký -->
                        <div class="hidden" data-content="register">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Tạo tài khoản mới</h3>
                            <p class="text-gray-600 mb-8">Điền thông tin của bạn để bắt đầu</p>
                            
                            <form id="dis-register-form" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <input type="text" id="register-firstname" name="firstname" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="Tên">
                                    </div>
                                    <div>
                                        <input type="text" id="register-lastname" name="lastname" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="Họ">
                                    </div>
                                </div>
                                
                                <div>
                                    <input type="email" id="register-email" name="email" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Email">
                                </div>
                                
                                <div>
                                    <input type="tel" id="register-phone" name="phone"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Số điện thoại">
                                </div>
                                
                                <div>
                                    <input type="text" id="register-username" name="username" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Tên đăng nhập">
                                </div>
                                
                                <div>
                                    <div class="relative">
                                        <input type="password" id="register-password" name="password" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="Mật khẩu">
                                        <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="relative">
                                        <input type="password" id="register-confirm-password" name="confirm_password" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="Nhập lại mật khẩu">
                                        <button type="button" class="toggle-password absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <button type="submit"
                                        class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 text-white text-lg font-semibold rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Tạo Tài Khoản
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: '500px',
            padding: 0,
            customClass: {
                popup: "!overflow-visible",
                closeButton: "!text-gray-400 !hover:text-gray-600 !focus:outline-none !top-4 !right-4 !z-10"
            },
            didOpen: () => {
                // Xử lý chuyển tab
                $("[data-tab]").click(function() {
                    const tab = $(this).data("tab");
                    $("[data-tab]").removeClass("border-b-2 border-blue-500 text-blue-500").addClass("text-gray-500");
                    $(this).removeClass("text-gray-500").addClass("border-b-2 border-blue-500 text-blue-500");
                    $("[data-content]").addClass("hidden");
                    $(`[data-content="${tab}"]`).removeClass("hidden");
                });

                // Xử lý hiển thị/ẩn mật khẩu
                $(".toggle-password").click(function() {
                    const input = $(this).siblings('input');
                    const type = input.attr('type') === 'password' ? 'text' : 'password';
                    input.attr('type', type);
                    
                    // Thay đổi icon
                    if (type === 'text') {
                        $(this).html(`
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        `);
                    } else {
                        $(this).html(`
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        `);
                    }
                });

                // Xử lý form đăng nhập
                $("#dis-login-form").submit(function(e) {
                    e.preventDefault();
                    
                    // Xóa tất cả thông báo lỗi cũ
                    $('.error-message').remove();
                    $('input').removeClass('border-red-500');
                    
                    const username = $("#login-username").val();
                    const password = $("#login-password").val();
                    const remember = $("#remember-me").is(":checked") ? 1 : 0;
                    
                    // Validate
                    let hasError = false;
                    
                    if (!username) {
                        showError($("#login-username"), "Vui lòng nhập tên đăng nhập hoặc email");
                        hasError = true;
                    }
                    
                    if (!password) {
                        showError($("#login-password"), "Vui lòng nhập mật khẩu");
                        hasError = true;
                    }
                    
                    if (hasError) return;
                    
                    $.ajax({
                        url: dis_auth.ajaxurl,
                        type: "POST",
                        data: {
                            action: "dis_user_login",
                            username: username,
                            password: password,
                            remember: remember,
                            nonce: dis_auth.nonce
                        },
                        beforeSend: function() {
                            console.log('Sending login request with nonce:', dis_auth.nonce);
                            // Disable form
                            $("#dis-login-form input, #dis-login-form button").prop('disabled', true);
                            $("#dis-login-form button[type='submit']").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
                        },
                        success: function(response) {
                            console.log('Login response:', response);
                            if (response.success) {
                                window.location.reload();
                            } else {
                                // Xử lý thông báo lỗi từ server
                                let errorMessage = "Tên đăng nhập hoặc mật khẩu không đúng";
                                
                                if (response.data) {
                                    errorMessage = typeof response.data === 'string' 
                                        ? response.data 
                                        : (response.data.message || errorMessage);
                                }
                                
                                console.error('Login error:', response.data);
                                
                                // Hiển thị lỗi dưới form
                                showError($("#login-password"), errorMessage);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', status, error, xhr.responseText);
                            showError($("#login-password"), "Đã xảy ra lỗi, vui lòng thử lại sau");
                        },
                        complete: function() {
                            // Enable form
                            $("#dis-login-form input, #dis-login-form button").prop('disabled', false);
                            $("#dis-login-form button[type='submit']").text('Đăng Nhập');
                        }
                    });
                });

                // Xử lý form đăng ký
                $("#dis-register-form").submit(function(e) {
                    e.preventDefault();
                    
                    // Xóa tất cả thông báo lỗi cũ
                    $('.error-message').remove();
                    $('input').removeClass('border-red-500');
                    
                    const firstname = $("#register-firstname").val();
                    const lastname = $("#register-lastname").val();
                    const email = $("#register-email").val();
                    const phone = $("#register-phone").val();
                    const username = $("#register-username").val();
                    const password = $("#register-password").val();
                    const confirm_password = $("#register-confirm-password").val();
                    
                    // Validate
                    let hasError = false;
                    
                    if (!firstname) {
                        showError($("#register-firstname"), "Vui lòng nhập tên");
                        hasError = true;
                    }
                    
                    if (!lastname) {
                        showError($("#register-lastname"), "Vui lòng nhập họ");
                        hasError = true;
                    }
                    
                    if (!email) {
                        showError($("#register-email"), "Vui lòng nhập email");
                        hasError = true;
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        showError($("#register-email"), "Email không hợp lệ");
                        hasError = true;
                    }
                    
                    if (!username) {
                        showError($("#register-username"), "Vui lòng nhập tài khoản");
                        hasError = true;
                    }
                    
                    const passwordError = validatePassword(password);
                    if (passwordError) {
                        showError($("#register-password"), passwordError);
                        hasError = true;
                    }
                    
                    if (password !== confirm_password) {
                        showError($("#register-confirm-password"), "Mật khẩu xác nhận không khớp");
                        hasError = true;
                    }
                    
                    if (hasError) return;
                    
                    $.ajax({
                        url: dis_auth.ajaxurl,
                        type: "POST",
                        data: {
                            action: "dis_user_register",
                            username: username,
                            email: email,
                            password: password,
                            confirm_password: confirm_password,
                            firstname: firstname,
                            lastname: lastname,
                            phone: phone,
                            nonce: dis_auth.nonce
                        },
                        beforeSend: function() {
                            // Disable form
                            $("#dis-register-form input, #dis-register-form button").prop('disabled', true);
                            $("#dis-register-form button[type='submit']").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.reload();
                            } else {
                                // Xử lý thông báo lỗi từ server
                                let errorMessage = "Không thể đăng ký tài khoản";
                                
                                if (response.data) {
                                    errorMessage = typeof response.data === 'string' 
                                        ? response.data 
                                        : (response.data.message || errorMessage);
                                }
                                
                                // Hiển thị lỗi dưới form
                                if (errorMessage.includes("email")) {
                                    showError($("#register-email"), errorMessage);
                                } else if (errorMessage.includes("tên đăng nhập")) {
                                    showError($("#register-username"), errorMessage);
                                } else {
                                    showError($("#register-password"), errorMessage);
                                }
                            }
                        },
                        error: function() {
                            showError($("#register-password"), "Đã xảy ra lỗi, vui lòng thử lại sau");
                        },
                        complete: function() {
                            // Enable form
                            $("#dis-register-form input, #dis-register-form button").prop('disabled', false);
                            $("#dis-register-form button[type='submit']").text('Đăng Ký');
                        }
                    });
                });
            }
        });
    }
    
    // Gắn sự kiện click cho link đăng nhập
    $(document).on("click", ".dis-login-link", function(e) {
        e.preventDefault();
        showAuthPopup();
    });
}); 