<!-- Đây là file violations_header.php -->

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cục Cảnh Sát Giao Thông - Bộ Công An Việt Nam</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/traffic/public/assets/css/components/violation_page/violation_search.css">
    
    <style>
        .home-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .home-link:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .home-link i {
            font-size: 18px;
        }
        
        .csgt-header {
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="csgt-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div style="width: 60px; height: 60px; margin-right: 15px;">
                            <svg viewBox="0 0 24 24" width="60" height="60">
                                <!-- Hình tròn nền đỏ -->
                                <circle cx="12" cy="12" r="12" fill="#DA251D" />
                                <!-- Sao vàng 5 cánh căn giữa -->
                                <polygon
                                    fill="#FFF200"
                                    points="
                                        12,6
                                        13.76,10.65
                                        18.76,10.65
                                        14.5,13.6
                                        16.2,18
                                        12,15.2
                                        7.8,18
                                        9.5,13.6
                                        5.24,10.65
                                        10.24,10.65
                                    "
                                />
                            </svg>
                        </div>

                        <div>
                            <h4 class="mb-0" style="font-weight: bold;">Cục Cảnh Sát Giao Thông</h4>
                            <p class="mb-0" style="font-size: 14px; opacity: 0.9;">Bộ Công An Việt Nam</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 text-end">
                    <a href="/traffic/public/" class="home-link">
                        <i class="fas fa-home"></i>
                        <span>Về Trang Chủ</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">