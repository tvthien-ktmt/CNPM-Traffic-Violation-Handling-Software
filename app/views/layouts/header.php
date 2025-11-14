<!-- File: app/views/layouts/header.php -->
<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $pageTitle ?? 'Hệ Thống Xử Phạt Vi Phạm Giao Thông'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/traffic/public/assets/css/components/home.css">
    <link rel="stylesheet" href="/traffic/public/assets/css/components/header.css">
    <link href="/traffic/public/assets/css/style.css" rel="stylesheet" />
</head>
<body>
    <!-- Header -->
<header class="site-header">
  <div class="header-container">
    <div class="header-inner">
      <!-- Logo và tên cơ quan -->
      <div class="header-left">
        <div class="logo-circle">
          <svg class="logo-icon" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z"
            />
            <circle cx="12" cy="12" r="3" fill="currentColor" />
          </svg>
        </div>
        <div class="logo-text">
          <h1 id="site-title">Cục Cảnh Sát Giao Thông</h1>
          <p class="sub-title">Bộ Công An Việt Nam</p>
        </div>
      </div>

      <!-- Menu điều hướng -->
      <nav class="nav-menu">
        <a href="/" class="menu-item">Trang chủ</a>
        <a href="/tra-cuu" class="menu-item">Tra cứu vi phạm</a>
        <a href="/officers/login" class="menu-item">Trang cán bộ</a>
        <a href="/tin-tuc" class="menu-item">Tin tức</a>
        <a href="/lien-he" class="menu-item">Liên hệ</a>
      </nav>

      <!-- Nút đăng nhập + Menu mobile -->
      <div class="header-right">
        <!-- THAY ĐỔI: Thêm href cho nút đăng nhập -->
        <a href="/traffic/app/views/officers/login.php" class="btn-login">Đăng nhập cán bộ</a>
        <button class="btn-menu">
          <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</header>
<script src="/traffic/public/assets/js/header.js"></script>
<main class="main-content">