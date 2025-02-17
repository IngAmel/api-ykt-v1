<?php
exit;

set_time_limit(0);
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Content-Type: application/json; charset=UTF-8");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 3600"); // Cachear la respuesta preflight por 1 hora
    exit(0); // No procesar más código para solicitudes OPTIONS
}

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = trim($_SERVER["REQUEST_URI"], "/");
$base_path = "intraschool/api-ykt/products.php";
$route = str_replace($base_path, "", $request_uri);
$route_segments = explode("/", trim($route, "/"));

if (empty($route_segments[0])) {
    http_response_code(404);
    echo json_encode(["data" => "Invalid request."]);
    exit;
}

$main_route = $route_segments[0]; // Primera parte de la ruta
var_dump($route_segments);
// Definir las rutas y sus manejadores
$routes = [
    'GET' => [
        'products' => [
            'products' => function () {
                include_once "pages/products.php";
                return get_products();
            },
        ],
    ],
    'POST' => [
        'products' => [
            'products' => function () {
                include_once "pages/products.php";
                return post_products();
            },
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