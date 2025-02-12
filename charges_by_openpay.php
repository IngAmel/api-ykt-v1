<?php

set_time_limit(0);
include 'controllers/read.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = trim($_SERVER["REQUEST_URI"], "/");
$base_path = "intraschool/api-ykt/charges_by_openpay.php";
$route = str_replace($base_path, "", $request_uri);
$route_segments = explode("/", trim($route, "/"));

if (empty($route_segments[0])) {
    http_response_code(404);
    echo json_encode(["data" => "Invalid request."]);
    exit;
}

$main_route = $route_segments[0]; // Primera parte de la ruta

// Definir las rutas y sus manejadores
$routes = [
    'GET' => [
        'books_sale' => [
            'sale' => function () {
                include_once "pages/books_sale.php";
                return create_charge_with_card();
            },
            'charge' => function ($charge_id) {
                include_once "pages/books_sale.php";
                return get_charge($charge_id);
            },
        ],
        'charges' => [
            // Aquí puedes agregar más subrutas para GET /charges
        ],
    ],
    'POST' => [
        'books_sale' => [
            'sale' => function () {
                include_once "pages/books_sale.php";
                return create_charge_with_card();
            },
            'mail_with_envelope' => function () {
                include_once "pages/books_sale.php";
                return mail_with_envelope();
            },
        ],
        'charges' => [
            // Aquí puedes agregar más subrutas para POST /charges
        ],
    ],
];

// Verificar si la ruta y el método HTTP existen
if (isset($routes[$request_method][$main_route])) {
    $route_handler = $routes[$request_method][$main_route];

    if (isset($route_segments[1])) {
        $sub_route = $route_segments[1];

        if (isset($route_handler[$sub_route])) {
            $handler = $route_handler[$sub_route];

            // Si la subruta requiere un parámetro (como "charge")
            if ($sub_route === "charge" && isset($route_segments[2])) {
                $charge_id = $route_segments[2];
                $result = $handler($charge_id);
            } else {
                $result = $handler();
            }

            http_response_code($result["response_code"]);
            echo json_encode($result["response"]);
        } else {
            http_response_code(400);
            echo json_encode(["data" => "Invalid sub-route for $main_route."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["data" => "Missing action for $main_route."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["data" => "The requested route is invalid."]);
}