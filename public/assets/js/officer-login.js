// File: officer-login.js - HOÀN CHỈNH
(function () {
  "use strict";

  console.log("✅ Officer Login JS loading...");

  // Kiểm tra đã chạy chưa - tránh double execution
  if (window.officerLoginLoaded) {
    console.log("⚠️ Script already loaded, skipping...");
    return;
  }
  window.officerLoginLoaded = true;

  // Hàm hiển thị notification
  function showNotification(type, title, message, duration = 5000) {
    // Remove existing notification
    const existing = document.querySelector(".global-notification");
    if (existing) existing.remove();

    // Create notification
    const notification = document.createElement("div");
    notification.className = `global-notification fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center max-w-md`;

    // Set colors based on type
    const colors = {
      success: "bg-green-600 text-white",
      error: "bg-red-600 text-white",
      warning: "bg-yellow-600 text-white",
      info: "bg-blue-600 text-white",
    };

    notification.className += " " + (colors[type] || colors.info);
    notification.style.animation = "slideIn 0.3s ease-out";

    // Icons
    const icons = {
      success: "✅",
      error: "❌",
      warning: "⚠️",
      info: "ℹ️",
    };

    notification.innerHTML = `
            <div class="mr-3 text-xl">${icons[type] || "ℹ️"}</div>
            <div class="flex-1">
                <p class="font-semibold">${title}</p>
                <p class="text-sm mt-1 opacity-90">${message}</p>
            </div>
            <button class="ml-4 opacity-70 hover:opacity-100" onclick="this.parentElement.remove()">
                ×
            </button>
        `;

    document.body.appendChild(notification);

    // Auto remove
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, duration);
  }

  // Main initialization
  document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ DOM loaded");

    const loginForm = document.getElementById("login-form");
    if (!loginForm) {
      console.log("ℹ️ Not on login page");
      return;
    }

    console.log("✅ Found login form");

    // Get form elements
    const phoneInput = document.getElementById("phone");
    const passwordInput = document.getElementById("password");
    const loginBtn = document.getElementById("login-btn");
    const rememberCheckbox = document.getElementById("remember-checkbox");
    const forgotLink = document.getElementById("forgot-link");
    const homeLink = document.getElementById("home-link");

    console.log("📋 Elements:", {
      phone: !!phoneInput,
      password: !!passwordInput,
      loginBtn: !!loginBtn,
      remember: !!rememberCheckbox,
      forgot: !!forgotLink,
      home: !!homeLink,
    });

    // 1. Phone input formatting
    if (phoneInput) {
      phoneInput.addEventListener("input", function () {
        // Remove non-numeric characters
        let value = this.value.replace(/\D/g, "");

        // Format: 090 123 4567
        if (value.length > 0) {
          if (value.startsWith("0")) {
            if (value.length <= 4) {
              value = value;
            } else if (value.length <= 7) {
              value = value.substring(0, 4) + " " + value.substring(4);
            } else {
              value =
                value.substring(0, 4) +
                " " +
                value.substring(4, 7) +
                " " +
                value.substring(7, 11);
            }
          }
        }

        this.value = value;
      });

      // Auto focus
      setTimeout(() => {
        phoneInput.focus();
        console.log("🔍 Focused on phone input");
      }, 300);
    }

    // 2. Remember me functionality
    if (rememberCheckbox && phoneInput) {
      // Load from localStorage
      const savedPhone = localStorage.getItem("csgt_remembered_phone");
      if (savedPhone && !phoneInput.value) {
        phoneInput.value = savedPhone;
        rememberCheckbox.checked = true;
      }

      rememberCheckbox.addEventListener("change", function () {
        if (this.checked && phoneInput.value) {
          localStorage.setItem(
            "csgt_remembered_phone",
            phoneInput.value.replace(/\s/g, "")
          );
        } else {
          localStorage.removeItem("csgt_remembered_phone");
        }
      });
    }

    // 3. Forgot password link
    if (forgotLink) {
      forgotLink.addEventListener("click", function (e) {
        e.preventDefault();
        showNotification(
          "info",
          "Quên mật khẩu",
          "Vui lòng liên hệ quản trị viên để được hỗ trợ.",
          4000
        );
      });
    }

    // 4. Home link (no special handling needed)
    if (homeLink) {
      console.log("✅ Home link found");
      // Let it work normally
    }

    // 5. FORM SUBMIT HANDLER - QUAN TRỌNG NHẤT
    loginForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      console.log("📝 Form submit triggered");

      // Validation
      if (!phoneInput || !phoneInput.value.trim()) {
        showNotification("error", "Lỗi", "Vui lòng nhập số điện thoại");
        phoneInput?.focus();
        return;
      }

      if (!passwordInput || !passwordInput.value.trim()) {
        showNotification("error", "Lỗi", "Vui lòng nhập mật khẩu");
        passwordInput?.focus();
        return;
      }

      // Show loading
      const originalBtnText = loginBtn ? loginBtn.innerHTML : "";
      if (loginBtn) {
        loginBtn.innerHTML =
          '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xử lý...';
        loginBtn.disabled = true;
      }

      try {
        // Prepare form data
        const formData = new FormData(this);
        const phoneClean = phoneInput.value.replace(/\s/g, "");
        formData.set("so_dien_thoai", phoneClean);

        console.log("📤 Sending request to:", this.action);
        console.log("📋 Data:", {
          phone: phoneClean,
          password:
            "***" +
            passwordInput.value.substring(passwordInput.value.length - 2),
        });

        // Send request
        const response = await fetch(this.action, {
          method: "POST",
          body: formData,
          redirect: "follow", // Important for PHP redirects
        });

        console.log(
          "📥 Response status:",
          response.status,
          response.statusText
        );
        console.log("Redirected?", response.redirected);
        console.log("URL:", response.url);

        // Handle redirect
        if (response.redirected) {
          console.log("🔄 Redirecting to:", response.url);
          window.location.href = response.url;
          return;
        }

        // If no redirect, read response
        const responseText = await response.text();
        console.log(
          "📄 Response (first 500 chars):",
          responseText.substring(0, 500)
        );

        // Check if it's an error page
        if (
          responseText.includes("error") ||
          responseText.includes("Sai") ||
          responseText.includes("Không tồn tại")
        ) {
          showNotification(
            "error",
            "Đăng nhập thất bại",
            "Sai số điện thoại hoặc mật khẩu"
          );
        } else {
          // Reload page to show PHP session messages
          window.location.reload();
        }
      } catch (error) {
        console.error("❌ Fetch error:", error);
        showNotification(
          "error",
          "Lỗi kết nối",
          "Không thể kết nối đến máy chủ. Vui lòng thử lại."
        );
      } finally {
        // Restore button
        if (loginBtn) {
          setTimeout(() => {
            loginBtn.innerHTML = originalBtnText;
            loginBtn.disabled = false;
          }, 1000);
        }
      }
    });

    // 6. Enter key support
    document.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        const active = document.activeElement;
        if (active && (active === phoneInput || active === passwordInput)) {
          loginForm.requestSubmit();
        }
      }
    });

    console.log("🎉 Officer Login JS initialized successfully!");
  });

  // Add CSS animations if not exists
  if (!document.getElementById("login-animations")) {
    const style = document.createElement("style");
    style.id = "login-animations";
    style.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            .fa-spinner {
                animation: spin 1s linear infinite;
            }
            
            button:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            .global-notification {
                animation: slideIn 0.3s ease-out;
            }
        `;
    document.head.appendChild(style);
  }

  // Error handling
  window.addEventListener("error", function (e) {
    console.error("🚨 Global error:", e.message);
    console.error("At:", e.filename, "Line:", e.lineno);
  });
})();
