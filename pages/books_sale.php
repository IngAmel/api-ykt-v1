<?php
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";
use Cobranza\PHP\Models\Payments;
use Cobranza\PHP\Models\BillingMail;
use Rigel\Colaboradores\MailerSingleton;
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
function mail_with_envelope()
{
    $billingMail = new BillingMail;
    $mailerSingleton = MailerSingleton::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);
    $mails = $data["mails"];
    $secretMails = $data["secretMails"] ?? [];
    $named_params = [
        'name_from' => $data['name_from'],
        'mails' => $mails,
        'secretMails' => $secretMails,
        'subject' => $data['subject'],
        'isHTML' => true
    ];
    $mailerSingleton->setParams($named_params);

    $base64File = $data["file"];

    // Eliminar el prefijo "data:image/jpeg;base64,"
    if (strpos($base64File, 'base64,') !== false) {
        $base64File = explode('base64,', $base64File)[1];
    }
    $fileName = $data["file_name"] ?? "Comprobante de pago.pdf";

    $fileContent = base64_decode($base64File);
    if ($fileContent === false) {
        return [
            "response_code" => 400,
            "response" => [
                "response" => false,
                "msg" => "El archivo base64 es inválido."
            ]
        ];
    }

    $tempFilePath = sys_get_temp_dir() . "/" . $fileName;
    file_put_contents($tempFilePath, $fileContent);
    $mailerSingleton->addAttachment($tempFilePath, $fileName);

    $mailerSingleton->Body = $data["text"];
    //$mailerSingleton->Body = $billingMail->get_billing_mail_with_envelope($data["text"]);

    $status = $mailerSingleton->send();
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    $response = [
        "response" => $status,
        "msg" => $status ? "Correo enviado" : "Ha ocurrido un error",
    ];
    return [
        "response_code" => $status ? 200 : 404,
        "response" => $response
    ];





}