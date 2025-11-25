<?php
// Định nghĩa routes
$routes = [
    // === TRANG CÔNG KHAI ===
    'GET /' => 'HomeController@index',
    'GET /tin-tuc' => 'NewsController@index',
    'GET /tin-tuc/{id}' => 'NewsController@detail',
    'GET /lien-he' => 'ContactController@index',
    'POST /lien-he' => 'ContactController@send',
    
    // === TRA CỨU VI PHẠM ===
    'GET /tra-cuu' => 'ViolationController@showSearch',
    'POST /tra-cuu' => 'ViolationController@search',
    'GET /tra-cuu/lich-su' => 'ViolationController@history',
    'GET /tra-cuu/{license_plate}' => 'ViolationController@detail',
    
    // === CHATBOT ===
    'POST /api/chatbot' => 'ViolationController@apiChatbot',
    'GET /chatbot' => 'ChatbotController@index',
    
    // === THANH TOÁN ===
    'GET /thanh-toan/{violation_id}' => 'PaymentController@create',
    'POST /thanh-toan/process' => 'PaymentController@process',
    'GET /thanh-toan/thanh-cong' => 'PaymentController@success',
    'GET /thanh-toan/that-bai' => 'PaymentController@failure',
    
    // === CALLBACK TỪ CỔNG THANH TOÁN ===
    'GET /payment/vnpay/callback' => 'PaymentController@vnpayCallback',
    'GET /payment/momo/callback' => 'PaymentController@momoCallback',
    'POST /payment/momo/callback' => 'PaymentController@momoCallback',
    
    // === XUẤT BIÊN LAI ===
    'GET /bien-lai/{receipt_id}' => 'ReceiptController@generate',
    'GET /bien-lai/{receipt_id}/download' => 'ReceiptController@download',
    
    // === TRANG CÁN BỘ ===
    'GET /can-bo/dang-nhap' => 'AuthController@showOfficerLogin',
    'POST /can-bo/dang-nhap' => 'AuthController@officerLogin',
    'POST /can-bo/dang-xuat' => 'AuthController@officerLogout',
    
    // === DASHBOARD CÁN BỘ (yêu cầu auth) ===
    'GET /can-bo' => 'OfficerController@dashboard',
    'GET /can-bo/vi-pham' => 'OfficerController@violationsList',
    'GET /can-bo/vi-pham/them' => 'OfficerController@showAddViolation',
    'POST /can-bo/vi-pham/them' => 'OfficerController@addViolation',
    'GET /can-bo/vi-pham/{id}/sua' => 'OfficerController@showEditViolation',
    'POST /can-bo/vi-pham/{id}/sua' => 'OfficerController@editViolation',
    'POST /can-bo/vi-pham/{id}/xoa' => 'OfficerController@deleteViolation',
    'GET /can-bo/bien-lai' => 'OfficerController@receiptsList',
    'GET /can-bo/bien-lai/{id}' => 'OfficerController@viewReceipt',
    'POST /can-bo/bien-lai/{id}/in' => 'OfficerController@printReceipt',
    'GET /can-bo/thong-ke' => 'OfficerController@statistics',
    
    // === API CHO CÁN BỘ ===
    'POST /api/camera/detect' => 'OfficerController@cameraDetect',
    'POST /api/ocr/verify' => 'OfficerController@ocrVerify',
    'GET /api/violations/recent' => 'OfficerController@getRecentViolations',
    
    // === AI SERVICE ROUTES ===
    'POST /api/ai/detect-plate' => 'ApiController@detectLicensePlate',
    'POST /api/ai/query-laws' => 'ApiController@queryTrafficLaws',
];

// Hàm xử lý routing
function route($method, $path) {
    global $routes;
    $key = "$method $path";
    
    // Check exact match first
    if (isset($routes[$key])) {
        return $routes[$key];
    }
    
    // Check dynamic routes (có tham số)
    foreach ($routes as $routeKey => $handler) {
        if (strpos($routeKey, '{') !== false) {
            // Convert route pattern to regex
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routeKey);
            $pattern = str_replace('/', '\/', $pattern);
            
            if (preg_match("/^$pattern$/", $key, $matches)) {
                array_shift($matches); // Remove full match
                return [
                    'handler' => $handler,
                    'params' => $matches
                ];
            }
        }
    }
    
    return null;
}

// Hàm dispatch request
function dispatch($method, $uri) {
    // Remove query string
    $path = parse_url($uri, PHP_URL_PATH);
    
    $route = route($method, $path);
    
    if ($route) {
        if (is_array($route) && isset($route['handler'])) {
            // Dynamic route với parameters
            call_controller_method($route['handler'], $route['params']);
        } else {
            // Static route
            call_controller_method($route);
        }
    } else {
        // 404 Not Found
        http_response_code(404);
        echo "Trang không tồn tại";
    }
}

// Hàm gọi controller method
function call_controller_method($handler, $params = []) {
    list($controller, $method) = explode('@', $handler);
    
    // Include controller file
    $controllerFile = "app/controllers/$controller.php";
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        
        // Create controller instance
        $controllerInstance = new $controller();
        
        // Call method với parameters
        call_user_func_array([$controllerInstance, $method], $params);
    } else {
        throw new Exception("Controller $controller không tồn tại");
    }
}
?><?php
// Định nghĩa routes
$routes = [
    // === TRANG CÔNG KHAI ===
    'GET /' => 'HomeController@index',
    'GET /tin-tuc' => 'NewsController@index',
    'GET /tin-tuc/{id}' => 'NewsController@detail',
    'GET /lien-he' => 'ContactController@index',
    'POST /lien-he' => 'ContactController@send',
    
    // === TRA CỨU VI PHẠM ===
    'GET /tra-cuu' => 'ViolationController@showSearch',
    'POST /tra-cuu' => 'ViolationController@search',
    'GET /tra-cuu/lich-su' => 'ViolationController@history',
    'GET /tra-cuu/{license_plate}' => 'ViolationController@detail',
    
    // === CHATBOT ===
    'POST /api/chatbot' => 'ViolationController@apiChatbot',
    'GET /chatbot' => 'ChatbotController@index',
    
    // === THANH TOÁN ===
    'GET /thanh-toan/{violation_id}' => 'PaymentController@create',
    'POST /thanh-toan/process' => 'PaymentController@process',
    'GET /thanh-toan/thanh-cong' => 'PaymentController@success',
    'GET /thanh-toan/that-bai' => 'PaymentController@failure',
    
    // === CALLBACK TỪ CỔNG THANH TOÁN ===
    'GET /payment/vnpay/callback' => 'PaymentController@vnpayCallback',
    'GET /payment/momo/callback' => 'PaymentController@momoCallback',
    'POST /payment/momo/callback' => 'PaymentController@momoCallback',
    
    // === XUẤT BIÊN LAI ===
    'GET /bien-lai/{receipt_id}' => 'ReceiptController@generate',
    'GET /bien-lai/{receipt_id}/download' => 'ReceiptController@download',
    
    // === TRANG CÁN BỘ ===
    'GET /can-bo/dang-nhap' => 'AuthController@showOfficerLogin',
    'POST /can-bo/dang-nhap' => 'AuthController@officerLogin',
    'POST /can-bo/dang-xuat' => 'AuthController@officerLogout',
    
    // === DASHBOARD CÁN BỘ (yêu cầu auth) ===
    'GET /can-bo' => 'OfficerController@dashboard',
    'GET /can-bo/vi-pham' => 'OfficerController@violationsList',
    'GET /can-bo/vi-pham/them' => 'OfficerController@showAddViolation',
    'POST /can-bo/vi-pham/them' => 'OfficerController@addViolation',
    'GET /can-bo/vi-pham/{id}/sua' => 'OfficerController@showEditViolation',
    'POST /can-bo/vi-pham/{id}/sua' => 'OfficerController@editViolation',
    'POST /can-bo/vi-pham/{id}/xoa' => 'OfficerController@deleteViolation',
    'GET /can-bo/bien-lai' => 'OfficerController@receiptsList',
    'GET /can-bo/bien-lai/{id}' => 'OfficerController@viewReceipt',
    'POST /can-bo/bien-lai/{id}/in' => 'OfficerController@printReceipt',
    'GET /can-bo/thong-ke' => 'OfficerController@statistics',
    
    // === API CHO CÁN BỘ ===
    'POST /api/camera/detect' => 'OfficerController@cameraDetect',
    'POST /api/ocr/verify' => 'OfficerController@ocrVerify',
    'GET /api/violations/recent' => 'OfficerController@getRecentViolations',
    
    // === AI SERVICE ROUTES ===
    'POST /api/ai/detect-plate' => 'ApiController@detectLicensePlate',
    'POST /api/ai/query-laws' => 'ApiController@queryTrafficLaws',
];

// Hàm xử lý routing
function route($method, $path) {
    global $routes;
    $key = "$method $path";
    
    // Check exact match first
    if (isset($routes[$key])) {
        return $routes[$key];
    }
    
    // Check dynamic routes (có tham số)
    foreach ($routes as $routeKey => $handler) {
        if (strpos($routeKey, '{') !== false) {
            // Convert route pattern to regex
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routeKey);
            $pattern = str_replace('/', '\/', $pattern);
            
            if (preg_match("/^$pattern$/", $key, $matches)) {
                array_shift($matches); // Remove full match
                return [
                    'handler' => $handler,
                    'params' => $matches
                ];
            }
        }
    }
    
    return null;
}

// Hàm dispatch request
function dispatch($method, $uri) {
    // Remove query string
    $path = parse_url($uri, PHP_URL_PATH);
    
    $route = route($method, $path);
    
    if ($route) {
        if (is_array($route) && isset($route['handler'])) {
            // Dynamic route với parameters
            call_controller_method($route['handler'], $route['params']);
        } else {
            // Static route
            call_controller_method($route);
        }
    } else {
        // 404 Not Found
        http_response_code(404);
        echo "Trang không tồn tại";
    }
}

// Hàm gọi controller method
function call_controller_method($handler, $params = []) {
    list($controller, $method) = explode('@', $handler);
    
    // Include controller file
    $controllerFile = "app/controllers/$controller.php";
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        
        // Create controller instance
        $controllerInstance = new $controller();
        
        // Call method với parameters
        call_user_func_array([$controllerInstance, $method], $params);
    } else {
        throw new Exception("Controller $controller không tồn tại");
    }
}
?>