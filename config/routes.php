<?php
// Định nghĩa routes
$routes = [
    '/' => 'HomeController@index',
    '/officers/login' => 'AuthController@showOfficerLogin',
    '/officers/dashboard' => 'OfficerController@dashboard',
    '/tra-cuu' => 'ViolationController@search',
    '/tin-tuc' => 'NewsController@index',
    '/lien-he' => 'ContactController@index'
];

// Hàm xử lý routing
function route($path) {
    global $routes;
    return $routes[$path] ?? null;
}
?>