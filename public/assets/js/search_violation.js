function inputText(element) {
  // Chuyển thành chữ hoa và giữ lại dấu gạch ngang, dấu chấm
  element.value = element.value.toUpperCase().replace(/[^A-Z0-9\-\.]/g, "");
}

// Xử lý chọn loại xe
document.addEventListener("DOMContentLoaded", function () {
  const vehicleOptions = document.querySelectorAll(".vehicle-option");

  vehicleOptions.forEach((option) => {
    option.addEventListener("click", function () {
      // Remove selected class from all options
      vehicleOptions.forEach((opt) => opt.classList.remove("selected"));

      // Add selected class to clicked option
      this.classList.add("selected");

      // Update radio button
      const radio = this.querySelector('input[type="radio"]');
      radio.checked = true;
    });
  });
});
