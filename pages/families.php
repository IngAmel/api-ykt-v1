<?php

$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";
use ApiYkt\Models\Families;

function getAllActivesFamilies()
{
    $products = new Families();
    $data = $products->getAllFamiliesActives();
    return $data;
}

function validateLogin($familyCredentials)
{
    $products = new Families();
    $data = $products->validateLogin($familyCredentials);
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
