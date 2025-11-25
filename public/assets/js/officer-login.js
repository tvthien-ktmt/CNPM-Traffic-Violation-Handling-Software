// Cấu hình mặc định
const defaultConfig = {
  system_title: "Hệ thống quản lý xử phạt giao thông",
  system_subtitle: "Bộ Công An - Cục Cảnh Sát Giao Thông",
  form_title: "Đăng nhập hệ thống",
  phone_label: "Số điện thoại",
  password_label: "Mật khẩu",
  remember_label: "Nhớ đăng nhập",
  login_button: "Đăng nhập",
  footer_text: "© 2025 Bộ Công An Việt Nam - Cục Cảnh Sát Giao Thông",
  home_link: "← Về trang chủ",
};

// Hàm cập nhật giao diện
async function onConfigChange(config) {
  document.getElementById("system-title").textContent =
    config.system_title || defaultConfig.system_title;

  document.getElementById("system-subtitle").textContent =
    config.system_subtitle || defaultConfig.system_subtitle;

  document.getElementById("form-title").textContent =
    config.form_title || defaultConfig.form_title;

  document.getElementById("phone-label").textContent =
    config.phone_label || defaultConfig.phone_label;

  document.getElementById("password-label").textContent =
    config.password_label || defaultConfig.password_label;

  document.getElementById("remember-label").textContent =
    config.remember_label || defaultConfig.remember_label;

  document.getElementById("login-button-text").textContent =
    config.login_button || defaultConfig.login_button;

  document.getElementById("footer-text").textContent =
    config.footer_text || defaultConfig.footer_text;

  // ❗ Không dùng innerHTML, tránh xóa href
  document.getElementById("home-link").textContent =
    config.home_link || defaultConfig.home_link;
}

// Khởi tạo SDK
if (window.elementSdk) {
  window.elementSdk.init({
    defaultConfig: defaultConfig,
    onConfigChange: onConfigChange,
    mapToCapabilities: () => ({
      recolorables: [],
      borderables: [],
      fontEditable: undefined,
      fontSizeable: undefined,
    }),
    mapToEditPanelValues: (config) =>
      new Map([
        ["system_title", config.system_title || defaultConfig.system_title],
        [
          "system_subtitle",
          config.system_subtitle || defaultConfig.system_subtitle,
        ],
        ["form_title", config.form_title || defaultConfig.form_title],
        ["phone_label", config.phone_label || defaultConfig.phone_label],
        [
          "password_label",
          config.password_label || defaultConfig.password_label,
        ],
        [
          "remember_label",
          config.remember_label || defaultConfig.remember_label,
        ],
        ["login_button", config.login_button || defaultConfig.login_button],
        ["footer_text", config.footer_text || defaultConfig.footer_text],
        ["home_link", config.home_link || defaultConfig.home_link],
      ]),
  });
}

// Xử lý form đăng nhập
document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("login-form");
  const phoneInput = document.getElementById("phone");
  const passwordInput = document.getElementById("password");
  const errorMessage = document.getElementById("error-message");
  const loginBtn = document.getElementById("login-btn");

  // Xóa lỗi khi nhập
  phoneInput.addEventListener("input", () => {
    phoneInput.classList.remove("error");
    errorMessage.classList.remove("show");
  });

  passwordInput.addEventListener("input", () => {
    passwordInput.classList.remove("error");
    errorMessage.classList.remove("show");
  });

  // Xử lý form submit
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const phone = phoneInput.value.trim();
    const password = passwordInput.value.trim();

    if (!phone || !password) return;

    loginBtn.classList.add("loading");
    loginBtn.disabled = true;

    // Giả lập kiểm tra đăng nhập
    setTimeout(() => {
      loginBtn.classList.remove("loading");
      loginBtn.disabled = false;

      const isSuccess = false;

      if (isSuccess) {
        showNotification(
          "success",
          "Đăng nhập thành công",
          "Đang chuyển hướng..."
        );
      } else {
        showLoginError();
      }
    }, 1500);
  });

  // Validate chỉ nhập số
  phoneInput.addEventListener("input", () => {
    phoneInput.value = phoneInput.value.replace(/[^0-9]/g, "");
  });

  // ❗ Không ngăn redirect – chỉ hiển thị thông báo
  document.getElementById("home-link").addEventListener("click", function () {
    showNotification("info", "Thông báo", "Đang chuyển về trang chủ...");
  });
});

// Hàm hiển thị thông báo
function showNotification(type, title, message) {
  const colors = {
    success: "bg-green-600",
    error: "bg-red-600",
    warning: "bg-yellow-600",
    info: "bg-blue-600",
  };

  const icons = {
    success:
      '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>',
    error:
      '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>',
    warning: '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
    info: '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>',
  };

  const notification = document.createElement("div");
  notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center fade-in`;
  notification.innerHTML = `
        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
            ${icons[type]}
        </svg>
        <div>
            <p class="font-semibold">${title}</p>
            <p class="text-sm mt-1">${message}</p>
        </div>
    `;

  document.body.appendChild(notification);

  setTimeout(() => notification.remove(), 3500);
}

// Hàm hiển thị lỗi đăng nhập
function showLoginError() {
  const phoneInput = document.getElementById("phone");
  const passwordInput = document.getElementById("password");
  const errorMessage = document.getElementById("error-message");

  phoneInput.classList.add("error");
  passwordInput.classList.add("error");
  errorMessage.classList.add("show");

  setTimeout(() => errorMessage.classList.remove("show"), 5000);
}
