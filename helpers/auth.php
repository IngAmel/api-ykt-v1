<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Dotenv\Dotenv;



$path_isc = dirname(__FILE__, 3);
require_once "$path_isc/vendor/autoload.php";


$dotenv = Dotenv::createImmutable($path_isc);
$dotenv->load();
/**
 * Valida un token JWT usando la clave secreta definida.
 * Lanza excepciones si el token es inválido o expirado.
 *
 * @param string $jwt
 * @return object Datos decodificados del token
 * @throws Exception
 */
function validateJWT($jwt)
{
    
    $jwt_secret = $_ENV['JWT_INVOICE_KEY'] ?? null;

    try {
        return JWT::decode($jwt, new Key($jwt_secret, 'HS256'));
    } catch (ExpiredException $e) {
        throw new Exception("Token expirado");
    } catch (Exception $e) {
        throw new Exception("Token inválido");
    }
}
