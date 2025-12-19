// File: public/assets/js/main.js
// Hiệu ứng cuộn mượt cho menu
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  });
});

// Xử lý tìm kiếm
document.querySelectorAll(".search-input").forEach((input) => {
  input.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      const searchTerm = this.value.trim();
      if (searchTerm) {
        showNotification(`Đang tìm kiếm: "${searchTerm}"`, "blue");
      }
    }
  });
});

// Xử lý click vào thông báo
document.querySelectorAll(".notification-item").forEach((item) => {
  item.addEventListener("click", function () {
    const title = this.querySelector("p").textContent;
    showNotification(`Đã mở: ${title}`, "green");
  });
});

// Hiển thị thông báo
function showNotification(message, type = "blue") {
  const colors = {
    blue: "bg-blue-600",
    green: "bg-green-600",
    red: "bg-red-600",
  };

  const notification = document.createElement("div");
  notification.className = `fixed top-20 right-4 ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 3000);
}
