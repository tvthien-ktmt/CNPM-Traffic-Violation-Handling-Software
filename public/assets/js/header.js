// File: public/assets/js/header.js
document.addEventListener("DOMContentLoaded", function () {
  // Xử lý nút đăng nhập cán bộ
  const loginBtn = document.querySelector(".btn-login");
  if (loginBtn) {
    loginBtn.addEventListener("click", function (e) {
      // Có thể thêm xử lý trước khi chuyển hướng
      console.log("Chuyển đến trang đăng nhập cán bộ...");
      // Chuyển hướng đã được xử lý bởi href, nhưng có thể thêm hiệu ứng loading
    });
  }

  // Xử lý menu mobile
  const menuBtn = document.querySelector(".btn-menu");
  const navMenu = document.querySelector(".nav-menu");

  if (menuBtn && navMenu) {
    menuBtn.addEventListener("click", function () {
      navMenu.classList.toggle("mobile-active");
    });
  }
});
