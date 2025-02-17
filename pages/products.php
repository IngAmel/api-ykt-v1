<?php
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";
use ApiYkt\Models\Products;
function get_products()
{
    $products = new Products();
    $data = $products->get_products();
    return $data;
}
function get_product($id)
{
    $products = new Products();
    $data = $products->get_product($id);
    return $data;
}
function post_products()
{
    $data = json_decode(file_get_contents('php://input'));
    if (!isset($data)) {
        return [
            "response_code" => 400,
            "response" => false,
            "icon" => "error",
            "title" => "No se han enviado los datos del formulario"
        ];
    }

    $products = new Products();
    $response = $products->post_products($data);
    return [
        "response_code" => $response ? 200 : 404,
        "response" => $response,
        "icon" => $response ? "success" : "error",
        "text" => $response ? "Guardado" : "Ha ocurrido un error, intente mas tarde"
    ];
}
function update_product($id)
{
    // Obtener los datos sin procesar de la solicitud PUT
    $rawData = file_get_contents("php://input");
    $product = new Products();
    // Procesar los datos manualmente
    $boundary = substr($rawData, 0, strpos($rawData, "\r\n")); // Obtener el límite del formulario
    if (empty($boundary)) {
        return [
            "response_code" => 400,
            "response" => "Formato de solicitud no válido"
        ];
    }

    // Dividir los datos en partes
    $parts = array_slice(explode($boundary, $rawData), 1, -1); // Ignorar el primer y último elemento
    $base_path = $product->get_base_path();

    if (!is_dir($base_path)) {
        mkdir($base_path, 0777, true);
    }
    $product->delete_product_img($id);
    $data = [];
    foreach ($parts as $part) {
        // Si la parte contiene un archivo
        if (strpos($part, 'filename="') !== false) {
            preg_match('/name="([^"]+)"; filename="([^"]+)"/', $part, $matches);
            $fieldName = $matches[1];
            $fileName = $matches[2];

            // Extraer el contenido del archivo
            $fileData = substr($part, strpos($part, "\r\n\r\n") + 4, -2); // Eliminar los últimos 2 caracteres (\r\n)

            // Generar un nombre único para el archivo usando el ID del producto
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION); // Obtener la extensión del archivo
            $newFileName = "{$id}_product.{$fileExtension}"; // Nombre del archivo: {id}_product.{ext}
            $filePath = "$base_path/$newFileName"; // Ruta donde se guardará el archivo

            // Guardar el archivo en el servidor
            file_put_contents($filePath, $fileData);

            $data[$fieldName] = [
                "name" => $newFileName, // Usar el nuevo nombre del archivo
                "type" => mime_content_type($filePath),
                "tmp_name" => $filePath,
                "error" => 0,
                "size" => filesize($filePath),
            ];
        } else {
            // Si la parte contiene datos normales (no archivos)
            preg_match('/name="([^"]+)"\s*\r\n\r\n(.*)\r\n/', $part, $matches);
            if (isset($matches[1])) {
                $data[$matches[1]] = $matches[2];
            }
        }
    }
    // Aquí puedes procesar la imagen y actualizar el producto
    if (isset($data["image"])) {
        // Guardar la ruta de la imagen en la base de datos o realizar otras operaciones

        $response = $product->setImg($data["image"]["name"], $data["product_id"]);
        return [
            "response_code" => $response ? 200 : 400,
            "response" => $response,
            "text" => $response ? "Imagen actualizada correctamente" : "Ha ocurrido un error, intente más tarde",
        ];
    } else {
        return [
            "response_code" => 400,
            "response" => "No se proporcionó una imagen"
        ];
    }
}