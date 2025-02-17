<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

set_time_limit(0);

// Obtener la URL de la solicitud
$request_uri = trim($_SERVER["REQUEST_URI"], "/");
$base_path = "intraschool/api-ykt";
$route = str_replace($base_path, "", $request_uri);
$route_segments = explode("/", trim($route, "/"));

// Si no hay segmentos en la URL, ejecutar la lógica principal de index.php
if (empty($route_segments[0])) {
    include 'controllers/read.php';
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['req_data'])) {
        $requested_data = $_GET['req_data'];
        $status_code = 400;
        $data = null;

        switch ($requested_data) {
            case 'get-all-families-actives':
                $status_code = 200;
                $data = getAllFamiliesActives();
                break;
            case 'get-all-dads-actives':
                $status_code = 200;
                $data = getAllDadsActives();
                break;
            case 'get-all-moms-actives':
                $status_code = 200;
                $data = getAllMomsActives();
                break;
            case 'get-all-students-actives':
                $status_code = 200;
                $data = getAllStudentsActives();
                break;
            case 'get-route-supervisors':
                $status_code = 200;
                $data = getRouteSupervisors();
                break;
            case 'get-families-without-access-school-transport':
                $status_code = 200;
                $data = getDebtorFamilies();
                break;
            default:
                $status_code = 404;
                $data = "The requested data parameter is unknown.";
                break;
        }

        http_response_code($status_code);
        echo json_encode(["data" => $data]);
    } else {
        http_response_code(404);
        echo json_encode(["data" => "The requested data parameter was not sent."]);
    }
    exit;
}

// Definir las rutas disponibles
$routes = [
    'products' => 'pages/products.php',
    'books' => 'pages/books.php',
];

// Verificar si la primera parte de la ruta es una de las páginas definidas
$main_route = $route_segments[0];

if (isset($routes[$main_route])) {
    include_once $routes[$main_route];

    $request_method = $_SERVER['REQUEST_METHOD'];
    $id = isset($route_segments[1]) ? intval($route_segments[1]) : null; // Si existe un ID, convertirlo en número

    // Definir las acciones permitidas
    $routes_definitions = [
        'products' => [
            'GET' => function ($id = null) {
                return $id ? get_product($id) : get_products();
            },
            'POST' => function () {
                return post_products();
            },
            'PUT' => function ($id) {
                return update_product($id);
            },
            'DELETE' => function ($id) {
                return delete_product($id);
            },
        ],
        'books' => [
            'GET' => function ($id = null) {
                return $id ? get_book_by_id($id) : get_books();
            },
            'POST' => function () {
                return post_books();
            },
        ],
    ];

    // Si el método y la ruta existen, ejecutar la función correspondiente
    if (isset($routes_definitions[$main_route][$request_method])) {
        $handler = $routes_definitions[$main_route][$request_method];

        if ($request_method === 'GET' && $id) {
            $result = $handler($id); // Obtener un solo producto/libro
        } elseif (in_array($request_method, ['PUT', 'DELETE']) && !$id) {
            http_response_code(400);
            echo json_encode(["error" => "ID is required for this operation"]);
            exit;
        } else {
            $result = $id ? $handler($id) : $handler(); // Ejecutar la función con o sin ID
        }

        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Invalid route"]);
}
