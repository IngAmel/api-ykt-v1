<?php
use App\Models\DataConn;

class Families extends DataConn {   

    private $itemsTable = "families_ykt";      
    public $id;
    public $name;
    public $description;
    public $price;
    public $category_id;
    public $created; 
    public $modified; 

    private $conn;
    
    public function __construct(){
        $this->conn = $this->dbConn();
    }

    function getAllFamiliesActives(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t1.id_family, t1.family_code, t1.family_name, t1.password
                                      FROM families_ykt.families AS t1
                                      WHERE t1.status = 1");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $id_family = $row->id_family;

            $principal_address = null;

            //--- URLs ---//
            $row->ykt_linea = '';
            $row->update_family_information = '';
            //--- --- ---//
            
            //--- Dirección principal ---//
            $stmt0 = $this->conn->prepare("SELECT 
                                             CASE
                                                WHEN t1.int_number != '' THEN CONCAT(t1.street, ' no. ext.', t1.ext_number, ', no. int.', t1.int_number, ', ', t1.colony, ', ', t1.delegation, ', CP. ', t1.postal_code)
                                                ELSE CONCAT(t1.street, ' no. ext.', t1.ext_number, ', ', t1.colony, ', ', t1.delegation, ', CP. ', t1.postal_code)
                                             END AS principal_address
                                           FROM families_ykt.addresses_families AS t1
                                           WHERE t1.id_family = :id_family AND (principal_address = 1 OR belongs_to = 0)
                                           LIMIT 1");
            $stmt0->bindValue(':id_family', $id_family, PDO::PARAM_INT);
            $stmt0->execute();

            while ($row0 = $stmt0->fetch(PDO::FETCH_OBJ)) {
                $principal_address = $row0->principal_address;
            }

            $row->principal_address = $principal_address;

            $secondary_contacts = array();
            
            //--- Contactos de confianza ---//
            $stmt1 = $this->conn->prepare("SELECT t1.trusted_contact_id, t1.contact_full_name AS contact_name, t1.relationship, t1.cell_phone, 
                                                CASE
                                                    WHEN t1.internal_number != '' THEN CONCAT(t1.street, ' no. ext.', t1.external_number, ', no. int.', t1.internal_number, ', ', t1.colony, ', ', t1.delegation, ', CP. ', t1.postal_code)
                                                    ELSE CONCAT(t1.street, ' no. ext.', t1.external_number, ', ', t1.colony, ', ', t1.delegation, ', CP. ', t1.postal_code)
                                                END AS contact_address
                                           FROM families_ykt.trusted_contacts AS t1
                                           WHERE t1.id_family = :id_family");
            $stmt1->bindValue(':id_family', $id_family, PDO::PARAM_INT);
            $stmt1->execute();

            while ($row1 = $stmt1->fetch(PDO::FETCH_OBJ)) {
                $secondary_contacts[] = $row1;
            }

            $row->secondary_contacts = $secondary_contacts;
            $result[] = $row;
        }

        return $result;
    }

    function getAllDadsActives(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t1.id_family, t1.family_code, CONCAT(t2.lastname, ' ', t2.name) AS father_name, t2.mail, t2.cell_phone, t2.landline,
                                             CONCAT(t3.street, ' ', t3.ext_number, ' ', t3.int_number, ', ', t3.colony, ', ', t3.delegation, ', CP: ', t3.postal_code) AS father_address
                                      FROM families_ykt.families AS t1
                                      INNER JOIN families_ykt.fathers AS t2 ON t1.id_family = t2.id_family
                                      INNER JOIN families_ykt.addresses_families AS t3 ON t2.id_family_address = t3.id_family_address
                                      WHERE t1.status = 1");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[] = $row;
        }

        return $result;
    }

    function getAllMomsActives(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t1.id_family, t1.family_code, CONCAT(t2.lastname, ' ', t2.name) AS mother_name, t2.mail, t2.cell_phone, t2.landline,
                                             CONCAT(t3.street, ' ', t3.ext_number, ' ', t3.int_number, ', ', t3.colony, ', ', t3.delegation, ', CP: ', t3.postal_code) AS mother_address
                                      FROM families_ykt.families AS t1
                                      INNER JOIN families_ykt.mothers AS t2 ON t1.id_family = t2.id_family
                                      INNER JOIN families_ykt.addresses_families AS t3 ON t2.id_family_address = t3.id_family_address
                                      WHERE t1.status = 1");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[] = $row;
        }

        return $result;
    }

    function getAllStudentsActives(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t5.id_family, t5.family_code, t1.id_student, t1.student_code, CONCAT(t1.name,' ', t1.lastname) AS nombre, t1.status, 
                                             IF(t1.gender = 0, 'Hombre', 'Mujer') AS sexo, t2.group_code, t3.campus_name, t4.degree,
                                             CASE
                                                WHEN t2.id_section = 1 THEN 'hombres'
                                                WHEN t2.id_section = 2 THEN 'mujeres'
                                                ELSE 'mixto'
                                             END AS academic_section
                                      FROM school_control_ykt.students AS t1
                                      LEFT JOIN school_control_ykt.groups AS t2 ON t1.group_id = t2.id_group
                                      LEFT JOIN school_control_ykt.campus AS t3 ON t2.id_campus = t3.id_campus
                                      LEFT JOIN school_control_ykt.academic_levels_grade AS t4 ON t2.id_level_grade = t4.id_level_grade
                                      INNER JOIN families_ykt.families AS t5 ON t5.id_family = t1.id_family
                                      WHERE t1.status = 1 AND t5.status = 1");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[] = $row;
        }

        return $result;
    }

    function getRouteSupervisors(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t1.no_colaborador AS collaborator_number, CONCAT(t1.apellido_paterno_colaborador, ' ', t1.apellido_materno_colaborador, ' ', t1.nombres_colaborador) AS collaborator_name,
                                             t1.correo_institucional AS institutional_mail, t2.access_password AS password
                                    FROM colaboradores_ykt.colaboradores AS t1
                                    INNER JOIN colaboradores_ykt.collaborator_access t2 ON t1.no_colaborador = t2.no_colaborador
                                    WHERE t1.status = 1 AND t1.id_area_colaborador = 5");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[] = $row;
        }

        return $result;
    }

    function getDebtorFamilies(){
        $result = null;

        $stmt = $this->conn->prepare("SELECT t1.id_family, t1.family_code, t1.family_name
                                      FROM families_ykt.families AS t1
                                      INNER JOIN families_ykt.debtor_families AS t2 ON t1.id_family = t2.id_family 
                                      WHERE t1.status = 1 AND t2.transport_access = 0");

        $stmt->execute();
                
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $result[] = $row;
        }

        return $result;
    }
}
?>