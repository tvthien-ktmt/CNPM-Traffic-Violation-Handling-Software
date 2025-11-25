<!--Đây là file search.php-->

<?php include __DIR__ . '/../violations/violations_header.php'; ?>
<style>
    .search-container {
    margin: 0 auto;
    max-width: 600px;
}

</style>
<div class="container">
    <div class="search-container">
        <h2 class="text-center mb-4" style="color: #004aad; font-weight: bold;">
            <i class="fas fa-search me-2"></i>TRA CỨU PHẠT NGUỘI
        </h2>
        
        <form action="/tra-cuu" method="post" id="tracuu">
            <!-- Vehicle Type Selection -->
            <div class="vehicle-options">
                <div class="vehicle-option selected" data-value="1">
                    <input type="radio" name="vehicle_type" value="1" checked style="display: none;">
                    <div class="vehicle-icon">
                        🚗
                    </div>
                    <div style="font-weight: bold;">Xe ô tô</div>
                </div>
                <div class="vehicle-option" data-value="2">
                    <input type="radio" name="vehicle_type" value="2" style="display: none;">
                    <div class="vehicle-icon">
                        🏍️
                    </div>
                    <div style="font-weight: bold;">Xe máy</div>
                </div>
                <div class="vehicle-option" data-value="3">
                    <input type="radio" name="vehicle_type" value="3" style="display: none;">
                    <div class="vehicle-icon">
                        🚲
                    </div>
                    <div style="font-weight: bold;">Xe điện</div>
                </div>
            </div>

            <!-- License Plate Input -->
            <div class="mb-4">
                <input type="text" name="license_plate" placeholder="NHẬP BIỂN SỐ XE" 
                       onkeyup="inputText(this)" required class="search-input">
                <div class="text-center mt-2">
                    <small class="text-muted">Ví dụ: 29A12345 hoặc 30H1-123.45</small>
                </div>
            </div>
            
            <!-- Search Button -->
            <button type="submit" class="search-btn">
                <i class="fas fa-search me-2"></i>TRA CỨU
            </button>
        </form>
        
        <!-- Results -->
        <div id="ketquatracuu" class="mt-4">
            <?php if (isset($violations) && !empty($violations)): ?>
                <div class="violation-results">
                    <h4 class="text-center mb-3">Kết quả tra cứu cho biển số: <strong><?= htmlspecialchars($licensePlate ?? '') ?></strong></h4>
                    <?php foreach ($violations as $violation): ?>
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Thời gian:</strong> <?= $violation['violation_date'] ?? '' ?></p>
                                    <p><strong>Địa điểm:</strong> <?= $violation['location'] ?? '' ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Lỗi vi phạm:</strong> <?= $violation['violation_type'] ?? '' ?></p>
                                    <p><strong>Mức phạt:</strong> <span class="text-danger"><?= number_format($violation['fine_amount'] ?? 0) ?> VNĐ</span></p>
                                    <p><strong>Trạng thái:</strong> 
                                        <span class="badge bg-<?= ($violation['status'] ?? '') == 'paid' ? 'success' : 'danger' ?>">
                                            <?= ($violation['status'] ?? '') == 'paid' ? 'Đã nộp phạt' : 'Chưa nộp phạt' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (isset($licensePlate)): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle me-2"></i>
                    Không tìm thấy vi phạm nào cho biển số: <strong><?= htmlspecialchars($licensePlate) ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function inputText(element) {
    element.value = element.value.toUpperCase().replace(/[^A-Z0-9\-\.]/g, '');
}

// Xử lý chọn loại xe
document.addEventListener('DOMContentLoaded', function() {
    const vehicleOptions = document.querySelectorAll('.vehicle-option');
    
    vehicleOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            vehicleOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
});

// AJAX form submission
document.getElementById('tracuu')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/tra-cuu', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const resultElement = doc.getElementById('ketquatracuu');
        if (resultElement) {
            document.getElementById('ketquatracuu').innerHTML = resultElement.innerHTML;
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>

<?php include __DIR__ . '/chatbot_ui.php'; ?>
<?php include __DIR__ . '/../violations/violations_footer.php'; ?>