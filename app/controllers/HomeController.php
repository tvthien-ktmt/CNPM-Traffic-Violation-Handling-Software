<!-- File: app/controllers/HomeController.php -->
<?php
class HomeController {
    public function index() {
        $pageTitle = "Trang Tin Tức - Hệ Thống Xử Phạt Vi Phạm Giao Thông";
        include '../app/views/home/index.php';
    } 
}
?> 