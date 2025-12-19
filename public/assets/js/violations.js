// JavaScript xử lý tra cứu thực tế
document.addEventListener("DOMContentLoaded", function () {
  const searchForm = document.getElementById("searchForm");
  const searchResults = document.getElementById("searchResults");
  const resultsContent = document.getElementById("resultsContent");

  // Format biển số tự động
  document
    .getElementById("license_plate")
    .addEventListener("input", function (e) {
      let value = e.target.value.toUpperCase().replace(/[^A-Z0-9\.-]/g, "");

      // Tự động format: 29A1-123.45
      if (value.length > 2 && !value.includes("-")) {
        value = value.slice(0, 3) + "-" + value.slice(3);
      }

      e.target.value = value;
    });

  // Xử lý tra cứu
  searchForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const licensePlate = document.getElementById("license_plate").value;
    const vehicleType = document.querySelector(
      'input[name="vehicle_type"]:checked'
    ).value;

    if (!licensePlate) {
      showAlert("Vui lòng nhập biển số xe", "warning");
      return;
    }

    // Hiển thị loading
    showLoading();

    try {
      const response = await fetch("/api/violations/search", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          license_plate: licensePlate,
          vehicle_type: vehicleType,
        }),
      });

      const data = await response.json();

      if (data.success) {
        displayResults(data.data, data.message);
      } else {
        showAlert(data.message, "error");
      }
    } catch (error) {
      console.error("Error:", error);
      showAlert("Lỗi kết nối đến hệ thống", "error");
    }
  });

  function showLoading() {
    searchResults.style.display = "block";
    resultsContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3">Đang kết nối đến hệ thống CSGT...</p>
                <small class="text-muted">Vui lòng chờ trong giây lát</small>
            </div>
        `;
  }

  function displayResults(violations, message) {
    if (!violations || violations.length === 0) {
      resultsContent.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <strong>${message}</strong>
                    <p class="mb-0 mt-2">Biển số xe không có vi phạm phạt nguội trong hệ thống.</p>
                </div>
            `;
      return;
    }

    let html = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;

    violations.forEach((violation, index) => {
      html += `
                <div class="violation-item border rounded p-3 mb-3 ${
                  violation.status === "paid" ? "bg-light" : "border-danger"
                }">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Vi Phạm #${index + 1}
                            ${
                              violation.status === "paid"
                                ? '<span class="badge badge-success ml-2">Đã thanh toán</span>'
                                : ""
                            }
                        </h6>
                        <span class="badge badge-danger font-weight-bold">${formatCurrency(
                          violation.fine_amount
                        )}</span>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Biển số:</strong> ${
                              violation.license_plate
                            }</p>
                            <p class="mb-1"><strong>Loại vi phạm:</strong> ${
                              violation.violation_type
                            }</p>
                            <p class="mb-1"><strong>Địa điểm:</strong> ${
                              violation.location
                            }</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Thời gian:</strong> ${formatDate(
                              violation.violation_date
                            )}</p>
                            <p class="mb-1"><strong>Cán bộ xử lý:</strong> ${
                              violation.officer
                            }</p>
                            <p class="mb-1"><strong>Trạng thái:</strong> 
                                <span class="badge ${
                                  violation.status === "pending"
                                    ? "badge-warning"
                                    : "badge-success"
                                }">
                                    ${
                                      violation.status === "pending"
                                        ? "Chưa thanh toán"
                                        : "Đã thanh toán"
                                    }
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    ${
                      violation.violation_details
                        ? `
                        <div class="mt-2">
                            <strong>Chi tiết:</strong> ${violation.violation_details}
                        </div>
                    `
                        : ""
                    }
                    
                    ${
                      violation.status === "pending"
                        ? `
                        <div class="mt-3 text-right">
                            <button class="btn btn-success btn-sm pay-btn" 
                                    data-violation='${JSON.stringify(
                                      violation
                                    ).replace(/'/g, "\\'")}'>
                                <i class="fas fa-credit-card"></i> Thanh Toán Online
                            </button>
                            <button class="btn btn-outline-primary btn-sm ml-2" onclick="downloadViolationDetails(${
                              violation.id
                            })">
                                <i class="fas fa-download"></i> Tải Biên Bản
                            </button>
                        </div>
                    `
                        : ""
                    }
                </div>
            `;
    });

    resultsContent.innerHTML = html;
    attachPaymentListeners();
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(amount);
  }

  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("vi-VN");
  }

  function showAlert(message, type) {
    const alertClass = type === "error" ? "alert-danger" : "alert-warning";
    resultsContent.innerHTML = `
            <div class="alert ${alertClass}">
                <i class="fas fa-exclamation-circle"></i> ${message}
            </div>
        `;
    searchResults.style.display = "block";
  }
});

function attachPaymentListeners() {
  document.querySelectorAll(".pay-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const violation = JSON.parse(this.dataset.violation);
      processPayment(violation);
    });
  });
}

function processPayment(violation) {
  // Redirect đến trang thanh toán
  window.location.href = `/payment/initiate?violation_id=${violation.id}&amount=${violation.fine_amount}`;
}
