<?php

// =====================
// DANH SÁCH ROUTES
// =====================
$routes = [
    'GET /' => 'HomeController@index',

    // Public pages
    'GET /tin-tuc' => 'NewsController@index',
    'GET /tin-tuc/{id}' => 'NewsController@detail',
    'GET /lien-he' => 'ContactController@index',
    'POST /lien-he' => 'ContactController@send',

    // =====================
    // Tra cứu vi phạm (gộp phần bạn)
    // =====================
    'GET /violations/search' => 'ViolationController@search',
    'POST /violations/search-handler' => 'ViolationController@handleSearch',

    // =====================
    // Thanh toán (gộp phần bạn)
    // =====================
    'GET /payment' => 'PaymentController@index',
    'POST /payment/process' => 'PaymentController@process',
    'GET /payment/callback/vnpay' => 'PaymentController@vnpayCallback',
    'GET /payment/callback/momo' => 'PaymentController@momoCallback',

    // =====================
    // Tra cứu vi phạm (phần team)
    // =====================
    'GET /tra-cuu' => 'ViolationController@showSearch',
    'POST /tra-cuu' => 'ViolationController@search',
    'GET /tra-cuu/lich-su' => 'ViolationController@history',
    'GET /tra-cuu/{license_plate}' => 'ViolationController@detail',

    // Chatbot
    'POST /api/chatbot' => 'ViolationController@apiChatbot',
    'GET /chatbot' => 'ChatbotController@index',

    // Thanh toán (team version)
    'GET /thanh-toan/{violation_id}' => 'PaymentController@create',
    'POST /thanh-toan/process' => 'PaymentController@process',
    'GET /thanh-toan/thanh-cong' => 'PaymentController@success',
    'GET /thanh-toan/that-bai' => 'PaymentController@failure',

    // Payment callback (team version)
    'GET /payment/vnpay/callback' => 'PaymentController@vnpayCallback',
    'GET /payment/momo/callback' => 'PaymentController@momoCallback',
    'POST /payment/momo/callback' => 'PaymentController@momoCallback',

    // Biên lai
    'GET /bien-lai/{receipt_id}' => 'ReceiptController@generate',
    'GET /bien-lai/{receipt_id}/download' => 'ReceiptController@download',

    // Cán bộ
    'GET /can-bo/dang-nhap' => 'AuthController@showOfficerLogin',
    'POST /can-bo/dang-nhap' => 'AuthController@officerLogin',
    'POST /can-bo/dang-xuat' => 'AuthController@officerLogout',

    // Dashboard cán bộ
    'GET /can-bo' => 'OfficerController@dashboard',
    'GET /can-bo/vi-pham' => 'OfficerController@violationsList',
    'GET /can-bo/vi-pham/them' => 'OfficerController@showAddViolation',
    'POST /can-bo/vi-pham/them' => 'OfficerController@addViolation',
    'GET /can-bo/vi-pham/{id}/sua' => 'OfficerController@showEditViolation',
    'POST /can-bo/vi-pham/{id}/sua' => 'OfficerController@editViolation',
    'POST /can-bo/vi-pham/{id}/xoa' => 'OfficerController@deleteViolation',

    // API
    'POST /api/camera/detect' => 'OfficerController@cameraDetect',
    'POST /api/ocr/verify' => 'OfficerController@ocrVerify',
    'GET /api/violations/recent' => 'OfficerController@getRecentViolations',

    // AI services
    'POST /api/ai/detect-plate' => 'ApiController@detectLicensePlate',
    'POST /api/ai/query-laws' => 'ApiController@queryTrafficLaws',
];
$router->post('/api/chatbot', 'ApiController@chatbot');


// =====================
// HÀM TÌM ROUTE
// =====================
function route($method, $path) {
    global $routes;
    $key = "$method $path";

    // Exact match
    if (isset($routes[$key])) {
        return $routes[$key];
    }

    // Dynamic route
    foreach ($routes as $routeKey => $handler) {
        if (strpos($routeKey, '{') !== false) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routeKey);
            $pattern = str_replace('/', '\/', $pattern);

            if (preg_match("/^$pattern$/", $key, $matches)) {
                array_shift($matches);
                return [
                    'handler' => $handler,
                    'params' => $matches
                ];
            }
        }
    }

    return null;
}


// =====================
// DISPATCH
// =====================
function dispatch($method, $uri) {
    $path = parse_url($uri, PHP_URL_PATH);
    $route = route($method, $path);

    if ($route) {
        if (is_array($route)) {
            call_controller_method($route['handler'], $route['params']);
        } else {
            call_controller_method($route);
        }
    } else {
        http_response_code(404);
        echo "Trang không tồn tại";
    }
}


// =====================
// GỌI CONTROLLER
// =====================
function call_controller_method($handler, $params = []) {
    list($controller, $method) = explode('@', $handler);

    $controllerFile = "app/controllers/$controller.php";

    if (!file_exists($controllerFile)) {
        throw new Exception("Controller $controller không tồn tại");
    }

    require_once $controllerFile;
    $instance = new $controller();
    call_user_func_array([$instance, $method], $params);
}

?>
