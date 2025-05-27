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
//LOCALHOST LUIS 
//$base_path = "YKT/intraschool/api-ykt-v1";

$route = str_replace($base_path, "", $request_uri);
$route_segments = explode("/", trim($route, "/"));

// Si viene por query string
if (isset($_GET['req_data'])) {
    include 'controllers/read.php';
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
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
    'invoice' => 'pages/invoice.php',
    'familiesActives' => 'pages/families.php',
];

// Verificar si la primera parte de la ruta es una de las páginas definidas
$main_route = $route_segments[0] ?? null;

/* if ($main_route === 'invoice' || $main_route === 'familiesActives') {
    require_once __DIR__ . '/helpers/auth.php';

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["error" => "Token JWT no proporcionado"]);
        exit;
    }

    $jwt = $matches[1];

    try {
        $decoded = validateJWT($jwt);
        // puedes usar $decoded->user_id o lo que venga en el token
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
} */

if (isset($routes[$main_route])) {
    include_once $routes[$main_route];

    $request_method = $_SERVER['REQUEST_METHOD'];
    $param = $route_segments[1] ?? null;

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
        'invoice' => [
            'GET' => function ($family_code = null) {
                return $family_code ? getFamilyInvoiceData($family_code) : badRequest();
            }
        ],
        'familiesActives' => [
            'GET' => function () {
                return getAllActivesFamilies();
            }
        ],
    ];

    // Si el método y la ruta existen, ejecutar la función correspondiente
    if (isset($routes_definitions[$main_route][$request_method])) {
        $handler = $routes_definitions[$main_route][$request_method];

        if ($request_method === 'GET') {
            $result = $handler($param); // Puede ser string como "ABC123"
        } elseif (in_array($request_method, ['PUT', 'DELETE']) && !$param) {
            http_response_code(400);
            echo json_encode(["error" => "Parameter is required for this operation"]);
            exit;
        } else {
            $result = $param ? $handler($param) : $handler();
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
