// File: D:\xampp\htdocs\traffic\public\assets\js\violation_search.js

console.log("✅ violation_search.js loaded successfully!");

// Format biển số tự động
function formatLicensePlate(input) {
  let value = input.value.toUpperCase().replace(/[^A-Z0-9\.-]/g, "");

  console.log("Formatting license plate:", value);

  // Tự động thêm dấu gạch ngang
  if (value.length > 2 && !value.includes("-")) {
    value = value.slice(0, 3) + "-" + value.slice(3);
  }

  input.value = value;
}

// Xử lý form submission
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM loaded - initializing violation search");

  const tracuuForm = document.getElementById("tracuuForm");

  if (tracuuForm) {
    console.log("✅ Form found, attaching event listener");

    tracuuForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("🚀 Form submitted");

      const licensePlate = document.querySelector(
        'input[name="license_plate"]'
      ).value;
      console.log("License plate:", licensePlate);

      const resultsDiv = document.getElementById("ketquatracuu");

      // Hiển thị loading
      showLoading(resultsDiv);

      try {
        console.log("📡 Sending request to server...");

        // Tạm thời hiển thị kết quả mẫu
        setTimeout(() => {
          showMockResults(resultsDiv, licensePlate);
        }, 2000);
      } catch (error) {
        console.error("❌ Error:", error);
        showError("Lỗi kết nối đến máy chủ");
      }
    });
  } else {
    console.error("❌ Form not found!");
  }
});

function showLoading(resultsDiv) {
  resultsDiv.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p style="margin-top: 15px; color: white; font-size: 16px;">
                <i class="fas fa-satellite-dish"></i> 
                Đang kết nối đến hệ thống CSGT...
            </p>
            <small style="color: #ccc;">Vui lòng chờ trong giây lát</small>
        </div>
    `;
}

// Hiển thị kết quả mẫu (tạm thời)
function showMockResults(resultsDiv, licensePlate) {
  console.log("🎯 Showing mock results for:", licensePlate);

  const mockViolations = [
    {
      id: 1,
      license_plate: licensePlate,
      violation_type: "Vượt đèn đỏ",
      location: "Ngã tư Láng Hạ - Đội Cấn, Hà Nội",
      violation_date: "2024-01-15 14:30:00",
      fine_amount: 800000,
      source: "Hệ thống",
    },
    {
      id: 2,
      license_plate: licensePlate,
      violation_type: "Chạy quá tốc độ",
      location: "Đường Trần Duy Hưng, Hà Nội",
      violation_date: "2024-01-10 09:15:00",
      fine_amount: 1200000,
      source: "Camera",
    },
  ];

  let html = `
        <div class="violation-result">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Tìm thấy ${mockViolations.length} vi phạm cho biển số <strong>${licensePlate}</strong>
            </div>
    `;

  mockViolations.forEach((violation, index) => {
    html += `
            <div class="violation-item">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Vi Phạm #${index + 1}
                        <span class="badge bg-primary ms-2">${
                          violation.source
                        }</span>
                    </h6>
                    <span class="badge bg-danger fw-bold">${formatCurrency(
                      violation.fine_amount
                    )}</span>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p class="mb-2"><strong><i class="fas fa-car me-2"></i>Biển số:</strong> ${
                          violation.license_plate
                        }</p>
                        <p class="mb-2"><strong><i class="fas fa-traffic-light me-2"></i>Loại vi phạm:</strong> ${
                          violation.violation_type
                        }</p>
                        <p class="mb-2"><strong><i class="fas fa-map-marker-alt me-2"></i>Địa điểm:</strong> ${
                          violation.location
                        }</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong><i class="fas fa-clock me-2"></i>Thời gian:</strong> ${formatDate(
                          violation.violation_date
                        )}</p>
                        <p class="mb-2"><strong><i class="fas fa-info-circle me-2"></i>Trạng thái:</strong> 
                            <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 text-end">
                    <button class="btn btn-success btn-lg me-2" onclick="processPayment(${
                      violation.id
                    })">
                        <i class="fas fa-credit-card me-2"></i> Thanh Toán Online
                    </button>
                    <button class="btn btn-outline-primary btn-lg" onclick="downloadViolation(${
                      violation.id
                    })">
                        <i class="fas fa-download me-2"></i> Tải Biên Bản
                    </button>
                </div>
            </div>
        `;
  });

  html += `</div>`;
  resultsDiv.innerHTML = html;

  console.log("✅ Results displayed successfully");
}

function showError(message) {
  const resultsDiv = document.getElementById("ketquatracuu");
  resultsDiv.innerHTML = `
        <div class="violation-result">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Lỗi:</strong> ${message}
            </div>
        </div>
    `;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function processPayment(violationId) {
  console.log("💳 Processing payment for violation:", violationId);
  alert(
    `🚦 Chuyển hướng đến trang thanh toán cho vi phạm #${violationId}\n\nTính năng đang được phát triển...`
  );
  // window.location.href = `/payment?violation_id=${violationId}`;
}

function downloadViolation(violationId) {
  console.log("📄 Downloading violation:", violationId);
  alert(
    `📋 Tải biên bản vi phạm #${violationId}\n\nTính năng đang được phát triển...`
  );
  // window.location.href = `/download/receipt/${violationId}`;
}

// Test function - có thể xóa sau
function testJavaScript() {
  console.log("🧪 Testing JavaScript functionality...");
  return "JavaScript is working!";
}

// Gọi test function khi load
testJavaScript();
