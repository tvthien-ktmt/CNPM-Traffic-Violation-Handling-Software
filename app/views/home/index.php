<?php 
$headerPath = __DIR__ . '/../layouts/header.php';
$footerPath = __DIR__ . '/../layouts/footer.php';

include $headerPath;
?>

<!-- Banner -->
<section class="home-banner">
    <div class="banner-content">
        <h1>Tin tức & Thông báo</h1>
        <p>Cập nhật những thông tin mới nhất về an toàn giao thông và các quy định pháp luật.</p>
    </div>
</section>

<!-- Layout 2 cột -->
<div class="home-container">
    <div class="main-content">
        <?php
        $newsPath = __DIR__ . '/../components/news_list.php';
        if (file_exists($newsPath)) include $newsPath;
        ?>
    </div>

    <aside class="sidebar">
        <?php
        $notificationsPath = __DIR__ . '/../components/notifications_sidebar.php';
        $quickLinksPath = __DIR__ . '/../components/quick_links.php';
        $appBannerPath = __DIR__ . '/../components/app_banner.php';
        
        if (file_exists($notificationsPath)) include $notificationsPath;
        if (file_exists($quickLinksPath)) include $quickLinksPath;
        if (file_exists($appBannerPath)) include $appBannerPath;
        ?>
    </aside>
</div>

<?php include $footerPath; ?>
