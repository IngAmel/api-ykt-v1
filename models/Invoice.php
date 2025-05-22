<?php
namespace ApiYkt\Models;
$path_isc = dirname(__FILE__, 3);
include "$path_isc/vendor/autoload.php";

use PDO;
use App\Models\DataConn;

class Invoice extends DataConn
{
    private $conn;
    private $db;
    private $table;
    public function __construct()
    {
        $this->conn = $this->dbConn();
        $this->db = "families_billing_data";
        $this->table = "$this->db.families_billing_addresses";
    }
    public function getFamilyInvoiceData($family_code)
    {
        $sql = "SELECT fba.business_name, fba.rfc, fba.mail, fam.*
        FROM $this->table AS fba
        INNER JOIN families_ykt.families AS fam ON fam.id_family = fba.id_family
        WHERE fam.family_code = :family_code
        ";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute([':family_code' => $family_code]);

        return (object) [
            "response_code" => $response ? 200 : 404,
            "response" => $response,
            "invoiceData" => $stmt->fetchAll(PDO::FETCH_OBJ)
        ];
    }
}