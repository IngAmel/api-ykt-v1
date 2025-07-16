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
    'invoiceMassive' => 'pages/invoice.php',
    'familiesActives' => 'pages/families.php',
    'validateLogin' => 'pages/families.php',
    'uploadSuppliesListFile' => 'pages/invoice.php',
    'payment-recived' => 'pages/invoice.php',
];

// Verificar si la primera parte de la ruta es una de las páginas definidas
$main_route = $route_segments[0] ?? null;

if (in_array($main_route, ['invoice', 'familiesActives', 'invoiceMassive', 'validateLogin', 'payment-recived'])) {
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
}

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
        'invoiceMassive' => [
            'POST' => function () {
                // Leer JSON del cuerpo
                $input = json_decode(file_get_contents("php://input"), true);

                if (!isset($input['family_codes']) || !is_array($input['family_codes'])) {
                    http_response_code(400);
                    return ["error" => "Debe enviar un arreglo llamado 'family_codes'."];
                }

                // Llamar a tu función que procese el arreglo
                return getMassiveInvoiceData($input['family_codes']);
            }
        ],
        'familiesActives' => [
            'GET' => function () {
                return getAllActivesFamilies();
            }
        ],
        'validateLogin' => [
            'POST' => function () {
                // Leer JSON del cuerpo
                $input = json_decode(file_get_contents("php://input"), true);

                if (!isset($input['user']) || !isset($input['password'])) {
                    http_response_code(400);
                    return ["error" => "Debe enviar los campos 'user' y 'password'."];
                }

                // Reestructurar como espera la función actual
                $familyCredentials = [
                    'familyCode' => $input['user'],
                    'hashedPass' => $input['password']
                ];

                return validateLogin($familyCredentials);
            }
        ],

        'payments-recived-massive' => [
            'POST' => function () {
                require_once 'models/Invoice.php';
                $model = new \ApiYkt\Models\Invoice();

                $input = json_decode(file_get_contents("php://input"), true);

                if (!isset($input['payments']) || !is_array($input['payments'])) {
                    http_response_code(400);
                    return ['error' => "Debes enviar un arreglo 'payments' con los pagos."];
                }

                return $model->registerPaymentsBatch($input['payments']);
            }
        ],
        'payment-recived' => [
            'POST' => function () {
                require_once 'models/Invoice.php';
                $model = new \ApiYkt\Models\Invoice();

                $input = json_decode(file_get_contents("php://input"), true);

                if (!is_array($input)) {
                    http_response_code(400);
                    return ['error' => "Debes enviar un arreglo de objetos JSON con los datos de los pagos."];
                }

                $results = [];
                foreach ($input as $idx => $payment) {
                    if (!is_array($payment)) {
                        $results[] = [
                            'index' => $idx,
                            'success' => false,
                            'error' => 'El elemento no es un objeto válido'
                        ];
                        continue;
                    }

                    $res = $model->registerSinglePayment($payment);
                    $results[] = [
                        'index' => $idx,
                        'result' => $res
                    ];
                }

                return $results;
            }
        ],


        'uploadSuppliesListFile' => [
            'POST' => function () {
                if (!isset($_FILES['pdf_file']) || !isset($_POST['id_relationship'])) {
                    http_response_code(400);
                    return ['success' => false, 'message' => 'Faltan datos'];
                }

                $file = $_FILES['pdf_file'];
                $id = $_POST['id_relationship'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    return ['success' => false, 'message' => 'Error al subir el archivo'];
                }

                $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($file['name']));
                $uploadDir = dirname(__DIR__) . '/school_control/public/uploads/supplies_list/';

                $uploadPath = $uploadDir . $filename;

                // Asegúrate de que el directorio exista
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        http_response_code(500);
                        return ['success' => false, 'message' => 'No se pudo crear el directorio de carga'];
                    }
                }

                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    http_response_code(500);
                    return ['success' => false, 'message' => 'Error al guardar el archivo'];
                }

                // Ruta que se guardará en la BD
                $publicPath = 'school_control/public/uploads/supplies_list/' . $filename;

                // Llama a tu modelo para actualizar el registro
                require_once 'models/Invoice.php';
                $model = new \ApiYkt\Models\Invoice();
                $success = $model->updateSuppliesFileRoute($id, $publicPath); // Debes crear esta función

                return ['success' => $success, 'message' => $success ? 'Archivo subido correctamente' : 'Error al guardar en BD'];
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
