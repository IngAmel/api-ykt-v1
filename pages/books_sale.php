<?php
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";
use Cobranza\PHP\Models\Payments;
function create_charge_with_card()
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['formData']) || !isset($data['customer'])) {
        return [
            "response_code" => 400,
            "response" => [
                "response" => false,
                "icon" => "error",
                "title" => "No se han enviado los datos del formulario"
            ]
        ];
    }
    $formData = $data['formData'];
    $customer = $data['customer'];
    $payments = new Payments();
    return $payments->create_charge_with_card($customer, $formData);
}
function get_charge($id_charge)
{
    $payments = new Payments();
    $data = $payments->get_charge($id_charge);
    return [
        "response_code" => $data->response ? 200 : 404,
        "response" => $data
    ];
}