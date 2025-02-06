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
if ($request_method === 'GET') {
    switch ($main_route) {
        case "books_sale":
            if (isset($route_segments[1])) {
                $sub_route = $route_segments[1];

                if ($sub_route === "sale") {
                    include_once "pages/books_sale.php";
                    $result = create_charge_with_card();
                } elseif ($sub_route === "charge" && isset($route_segments[2])) {
                    $charge_id = $route_segments[2]; // tr_234sdf4q234
                    include_once "pages/books_sale.php";
                    $result = get_charge($charge_id);
                } else {
                    http_response_code(400);
                    echo json_encode(["data" => "Invalid books_sale request."]);
                    exit;
                }

                http_response_code($result["response_code"]);
                echo json_encode($result["response"]);
            } else {
                http_response_code(400);
                echo json_encode(["data" => "Missing action for books_sale."]);
            }
            break;

        case "charges":
            // Implementar lógica para /charges si es necesario
            break;

        default:
            http_response_code(404);
            echo json_encode(["data" => "The requested route is invalid."]);
            break;
    }
} elseif ($request_method === 'POST') {
    switch ($main_route) {
        case "books_sale":
            if (isset($route_segments[1])) {
                $sub_route = $route_segments[1];

                if ($sub_route === "sale") {
                    include_once "pages/books_sale.php";
                    $result = create_charge_with_card();
                } else {
                    http_response_code(400);
                    echo json_encode(["data" => "Invalid books_sale request."]);
                    exit;
                }

                http_response_code($result["response_code"]);
                echo json_encode($result["response"]);
            } else {
                http_response_code(400);
                echo json_encode(["data" => "Missing action for books_sale."]);
            }
            break;

        case "charges":
            // Implementar lógica para /charges si es necesario
            break;

        default:
            http_response_code(404);
            echo json_encode(["data" => "The requested route is invalid."]);
            break;
    }
}
