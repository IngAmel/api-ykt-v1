<?php
set_time_limit(0);
header("Content-Type: application/json; charset=UTF-8");

// Configuración de CORS (para desarrollo)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar solicitudes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Max-Age: 3600");
    exit(0);
}
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";


use Rigel\Colaboradores2\Collaborator as Rigel2;

// Obtener información de la solicitud
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

//Agregado para tests locales
//$request_uri .= "reporteNomina/10/?year=2024";

$query_params = $_GET;
//$query_params["year"] = "2024";

// Base path de tu API
$base_path = '/intraschool/api-ykt/rigel';
$route = str_replace($base_path, '', $request_uri);

// Dividir la ruta en segmentos
$segments = array_values(array_filter(explode('/', $route)));

// Determinar el recurso y el ID
$resource = $segments[0] ?? null;
$id = $segments[1] ?? null;

try {
    // Manejo de rutas
    switch ("$request_method:$resource") {
        // GET /nominas/
        case 'GET:reporteNomina':
            if ($id) {
                // GET /nominas/$id
                try {
                    $id = (int) $id;

                    if ($id > 25 || $id < 0) {
                        $response = "Nomina invalida";
                    } else {

                        $dictionary_nom = [
                            1 => "01",
                            2 => "01",
                            3 => "02",
                            4 => "02",
                            5 => "03",
                            6 => "03",
                            7 => "04",
                            8 => "04",
                            9 => "05",
                            10 => "05",
                            11 => "06",
                            12 => "06",
                            13 => "07",
                            14 => "07",
                            15 => "08",
                            16 => "08",
                            17 => "09",
                            18 => "09",
                            19 => "10",
                            20 => "10",
                            21 => "11",
                            22 => "11",
                            23 => "12",
                            24 => "12",
                            25 => "12",
                        ];

                        $query_params["first"] = $id % 2 !== 0 ? 1 : 0;
                        $query_params["special"] = 0;

                        if ($id === 25) {
                            $query_params["first"] = 0;
                            $query_params["special"] = 1;
                        }

                        $colab = new Rigel2;
                        $response = $colab->reporteNomina($dictionary_nom[$id], $query_params);
                    }
                } catch (\Throwable $th) {
                    if ((int) $_SESSION["colab"] === 11115) {
                        $errorDetails = [
                            'archivo' => $th->getFile(),
                            'linea' => $th->getLine(),
                            'mensaje' => $th->getMessage(),
                            'trace' => $th->getTrace(),
                            'trace_string' => $th->getTraceAsString(),
                            'codigo' => $th->getCode(),
                            'clase' => get_class($th)
                        ];
                        echo "<pre>";
                        var_dump($errorDetails);
                        echo "</pre>";
                    }
                    throw new Exception("Ha ocurrido un error", 500);
                }
            } else {
                // GET /nominas/?foo=bar (con filtros)
                $response = array_values($db['nominas']);
                if ($query_params) {
                    // Aquí puedes implementar filtrado basado en query params
                    $response = array_filter($response, function ($item) use ($query_params) {
                        // Ejemplo de filtrado simple
                        if (isset($query_params['foo']) && $item['nombre'] !== $query_params['foo']) {
                            return false;
                        }
                        return true;
                    });
                }
            }
            break;

        // GET /colaboradores/ o /colaborador/2163
        case 'GET:colaboradores':
        case 'GET:colaborador':
            if ($id) {
                // GET /colaborador/2163
                $response = $db['colaboradores'][$id] ?? null;
                if (!$response) {
                    throw new Exception("Colaborador no encontrado", 404);
                }
            } else {
                // GET /colaboradores/
                $response = array_values($db['colaboradores']);
            }
            break;

        // Agrega más casos para otros métodos (POST, PUT, DELETE) y recursos
        default:
            throw new Exception("Ruta no encontrada", 404);
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $response,
        //'params' => $query_params // Opcional: mostrar parámetros recibidos
    ]);

} catch (Exception $e) {
    // Manejo de errores
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}