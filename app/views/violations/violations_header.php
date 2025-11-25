<!-- Đây là file violagtion_header.php -->

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cục Cảnh Sát Giao Thông - Bộ Công An Việt Nam</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        /* Header Styles */
        .csgt-header {
            background: linear-gradient(135deg, #004aad 0%, #00264d 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .csgt-logo {
            height: 60px;
        }
        
        .csgt-navbar {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        
        .csgt-navbar .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .csgt-navbar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        /* Search Form Styles */
        .search-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            margin: 20px;
        }
        
        .vehicle-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vehicle-option {
            text-align: center;
            cursor: pointer;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: #f8f9fa;
        }
        
        .vehicle-option.selected {
            background: #004aad;
            color: white;
            border-color: #004aad;
            transform: translateY(-5px);
        }
        
        .vehicle-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 32px;
            background: #e9ecef;
            transition: all 0.3s ease;
        }
        
        .vehicle-option.selected .vehicle-icon {
            background: rgba(255,255,255,0.2);
        }
        
        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #004aad;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 74, 173, 0.1);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #004aad 0%, #00264d 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            </div>
        </div>
    </header>


    <!-- Main Content -->
    <div class="main-content">