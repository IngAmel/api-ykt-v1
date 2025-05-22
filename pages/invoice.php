<?php
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";

use ApiYkt\Models\Invoice;

function getFamilyInvoiceData($family_code)
{
    $products = new Invoice();
    $data = $products->getFamilyInvoiceData($family_code);
    return $data;
}

function badRequest()
{
    return [
        'status' => 400,
        'error' => 'Bad Request',
        'message' => 'The request could not be understood or was missing required parameters.'
    ];
}
