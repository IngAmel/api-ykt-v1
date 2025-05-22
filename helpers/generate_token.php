<?php
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";

use Firebase\JWT\JWT;

$secret_key = 'YKT123'; // debe ser la misma que usarás para validar
$payload = [
    'client' => 'cliente_ejemplo',
    'iat' => time(),                // Fecha de creación
    'exp' => time() + 3600          // Expira en 1 hora
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

echo "TOKEN JWT:\n" . $jwt;
