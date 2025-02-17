<?php
namespace ApiYkt\Models;
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";

use PDO;
use App\Models\DataConn;

class Products extends DataConn
{
    private $conn;
    private $table;
    private $base_path;
    private $short_url;
    public function __construct()
    {
        $this->conn = $this->dbConn();
        $this->table = "online_sales.products";
        $this->short_url = "/online_sales/public/uploads/book_covers/";
        $this->base_path = dirname(__FILE__, 3) . $this->short_url;
    }
    public function get_products()
    {
        $sql = "SELECT * FROM $this->table";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute();
        return (object) [
            "response_code" => $response ? 200 : 404,
            "response" => $response,
            "products" => $stmt->fetchAll(PDO::FETCH_OBJ)
        ];
    }
    public function build_query_insert($input)
    {
        if (is_object($input)) {
            $input = get_object_vars($input);
        }
        $columns = implode(",", array_keys($input));
        $placeholders = rtrim(str_repeat('?, ', count($input)), ', ');
        return [
            'columns' => $columns,
            'placeholders' => $placeholders,
            'values' => array_values($input)
        ];
    }
    public function build_query_update($array)
    {
        $set = [];
        foreach ($array as $key => $value) {
            if (strpos($value, "'")) {
                $value = str_replace("'", '"', $value);
            }
            $set[] = "$key = ?";
        }
        return implode(", ", $set);
    }
    public function get_base_path()
    {
        return $this->base_path;
    }
    public function delete_product_img($product_id)
    {
        $pattern = "$this->base_path/{$product_id}_product.*"; // Patrón para buscar archivos
        $existingFiles = glob($pattern); // Buscar archivos que coincidan con el patrón

        foreach ($existingFiles as $existingFile) {
            if (is_file($existingFile)) {
                unlink($existingFile); // Eliminar el archivo
            }
        }
        return $this->get_product_img($product_id) === NULL;
    }
    public function get_product_img($product_id)
    {
        $base_path = $this->base_path;
        $pattern = "$base_path/{$product_id}_product.*"; // Patrón para buscar archivos

        $existingFiles = glob($pattern); // Buscar archivos que coincidan con el patrón

        if (count($existingFiles) > 0) {
            // Devolver la ruta del primer archivo encontrado
            return $existingFiles[0];
        }

        return null; // Si no se encuentra ninguna imagen
    }
    public function get_product($id_product)
    {
        $sql = "SELECT * FROM $this->table WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute([$id_product]);
        $product = $stmt->fetch(PDO::FETCH_OBJ);
        if ($product->img) {
            $imagePath = $this->get_product_img($id_product);
            $product->img = $imagePath ? $this->short_url . basename($imagePath) : null;
        }
        return [
            "response" => $response,
            "response_code" => $response ? 200 : 404,
            "product" => $product,
        ];

    }
    public function post_products($product)
    {
        if (isset($product->product_id)) {
            $old_product = $this->get_product($product->product_id)["product"];
            foreach (get_object_vars($old_product) as $key => $value) {
                // Si la propiedad existe en $product, actualiza su valor en $old_product
                if (isset($product->$key)) {
                    $old_product->$key = $product->$key;
                }
            }
            $upd = $this->build_query_update($old_product);
            $sql = "UPDATE $this->table SET $upd WHERE product_id = ?";
            $stmt = $this->conn->prepare($sql);
            $values = array_values(get_object_vars($old_product));
            $values[] = $product->product_id;

        } else {
            $ins = $this->build_query_insert($product);
            $sql = "INSERT INTO $this->table ($ins[columns]) VALUES ($ins[placeholders])";
            $stmt = $this->conn->prepare($sql);
            $values = array_values($ins['values']);
        }
        return $stmt->execute($values);
    }
    public function setImg($img, $product_id)
    {
        $sql = "UPDATE $this->table SET img = ? WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        $values = [$img, $product_id];
        return $stmt->execute($values);
    }
}