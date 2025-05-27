<?php

namespace ApiYkt\Models;


use PDO;
use App\Models\DataConn;

class Families extends DataConn
{
    private $conn;
    private $db;
    private $table;
    public function __construct()
    {
        $this->conn = $this->dbConn();
        $this->db = "families_ykt";
        $this->table = "$this->db.families";
    }
    public function getAllFamiliesActives()
    {
        $sql = "SELECT fam.*
        FROM $this->table AS fam
        WHERE fam.status = 1
        ";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute();



        $famInv = $stmt->fetchAll(PDO::FETCH_OBJ);
        if (!$famInv || count($famInv) === 0) {
            return (object) [
                "response_code" => 404,
                "response" => false,
                "message" => "No se encontraron familias activas"
            ];
        }

        $familiesActives=[];
        $fam_data = $famInv[0];
        foreach ($famInv as $fam_data) {
            $familiesActives[] = [
            "familyId" => $fam_data->id_family,
            "familyCode" => $fam_data->family_code,
            "familyName" => $fam_data->family_name,
            "familyPassword" => $fam_data->password,
            "mainAddress" => $this->getFamilyMainAddress($fam_data->id_family),
            "attorneyInfo" => $this->getAttorneyInfo($fam_data->id_family, $fam_data->attorney),
            "students" => $this->getStudentsByFamily($fam_data->family_code)
        ];
        }
        

        return (object) [
            "response_code" => $response ? 200 : 404,
            "response" => $response,
            "activeFamilies" => $familiesActives
        ];
    }
    public function getStudentsByFamily($family_code)
    {
        $sql = "SELECT stud.id_student, UPPER(student_code) AS student_code,
        UPPER(CONCAT(stud.name, ' ', stud.lastname)) AS student_name,
        anonnymus_equals AS education_level, cmp.campus_name
        FROM families_ykt.families AS fam
        INNER JOIN school_control_ykt.students AS stud ON stud.id_family = fam.id_family
        INNER JOIN school_control_ykt.inscriptions AS insc ON stud.id_student = insc.id_student
        INNER JOIN school_control_ykt.groups AS gps ON gps.id_group = insc.id_group
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON aclg.id_level_grade = gps.id_level_grade
        INNER JOIN school_control_ykt.academic_levels AS al ON al.id_academic_level = aclg.id_academic_level
        INNER JOIN school_control_ykt.campus AS cmp ON cmp.id_campus = gps.id_campus
        INNER JOIN txt_generator.academic_levels_equals AS acle ON acle.id_academic_level = al.id_academic_level
        WHERE fam.family_code = :family_code
        AND gps.group_type_id = 1
        AND insc.active = 1
        AND stud.status = 1
        ";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute([':family_code' => $family_code]);



        $studentsData = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!$studentsData || count($studentsData) === 0) {
            return (object) [
                "response_code" => 404,
                "response" => false,
                "message" => "No se encontraron alumnos activos para la familia"
            ];
        }

        // Estructura deseada
        $famStudents = [];

        foreach ($studentsData as $student) {

            $famStudents[] = [
                "studentCode" => $student->student_code,
                "studentName" => $student->student_name,
                "studentCampus" => $student->campus_name,
                "studenAcademicLevel" => $student->education_level
            ];
        }

        return $famStudents;
    }

    public function getAttorneyInfo($id_family, $attorney)
    {

        if ($attorney) {
            $sql = "SELECT UPPER(CONCAT(name, ' ', lastname)) AS attorney_name, mail, cell_phone
        FROM families_ykt.fathers AS fat
        WHERE fat.id_family = :id_family
        ";
        } else {
            $sql = "SELECT UPPER(CONCAT(name, ' ', lastname)) AS attorney_name, mail, cell_phone
        FROM families_ykt.mothers AS moth
        WHERE moth.id_family = :id_family
        ";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_family' => $id_family]);


        $attorneyData = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!$attorneyData || count($attorneyData) === 0) {
            return (object) [
                "response_code" => 404,
                "response" => false,
                "message" => "No se encontraron datos del apoderado"
            ];
        }

        // Estructura deseada
        $attorneyInfo = [];

        $attorneyInfo = [
            "attorneyName" => $attorneyData[0]->attorney_name,
            "attorneyMail" => $attorneyData[0]->mail,
            "attorneyPhone" => $attorneyData[0]->cell_phone
        ];

        return $attorneyInfo;
    }

    public function getFamilyMainAddress($id_family)
    {

        $sql = "SELECT *
        FROM families_ykt.addresses_families AS adds
        WHERE adds.id_family = :id_family
        AND principal_address = 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_family' => $id_family]);


        $familyAddressData = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!$familyAddressData || count($familyAddressData) === 0) {
            return (object) [
                "response_code" => 404,
                "response" => false,
                "message" => "No se encontraron datos del apoderado"
            ];
        }

        // Estructura deseada
        $addressInfo = [];

        $addressInfo = [
            "street" => $familyAddressData[0]->street,
            "extNumber" => $familyAddressData[0]->ext_number,
            "intNumber" => $familyAddressData[0]->int_number,
            "betweenStreets" => $familyAddressData[0]->between_streets,
            "colony" => $familyAddressData[0]->colony,
            "delegation" => $familyAddressData[0]->delegation,
            "zipCode" => $familyAddressData[0]->postal_code
        ];

        return $addressInfo;
    }

    public function getGeneralPublicInvoice($family_code)
    {

        $sql = "SELECT 
        fam.*,
        CASE 
            WHEN fam.attorney = 0 THEN fath.mail
            WHEN fam.attorney = 1 THEN moth.mail
        END AS family_mail
        FROM families_ykt.families AS fam
        INNER JOIN families_ykt.fathers AS fath ON fath.id_family = fam.id_family
        INNER JOIN families_ykt.mothers AS moth ON moth.id_family = fam.id_family
        WHERE fam.family_code = :family_code
        AND fam.status = 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':family_code' => $family_code]);


        $familyData = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (!$familyData || count($familyData) === 0) {
            return (object) [
                "response_code" => 404,
                "response" => false,
                "message" => "No se encontraron datos para la familia con el código $family_code"
            ];
        }

        $famData = $familyData[0];
        $famInvoiceData = [
            "basicInfo" => [
                "familyId" => $famData->id_family,
                "familyCode" => $famData->family_code,
                "familyName" => $famData->family_name,
                "invoiceType" => "FACTURACIÓN A PÚBLICO GENERAL",
            ],
            "reciverInfo" => [
                "cfdiUse" => "G03",
                "reciverName" => "PUBLICO GENERAL",
                "Rfc" => "XAXX010101000",
                "reciverMail" => $famData->family_mail,
                "TaxResidence" => "MEX",
                "invoiceAddress" => [
                    "street" => "LAFONTAINE",
                    "extNumber" => "",
                    "intNumber" => "",
                    "colony" => "POLANCO",
                    "delegation" => "MIGUEL HIDALGO",
                    "zipCode" => "11550",
                    "betweenStreets" => "",
                ]
            ],
            "students" => $this->getStudentsByFamily($family_code)
        ];

        return $famInvoiceData;
    }

    public function validarCURP($curp)
    {
        // Expresión regular para validar el formato de la CURP
        $regex = '/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[H|M]{1}(AS|BC|BS|CC|CS|CH|CL|CM|CO|DG|DF|GR|GT|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE){1}[BCDFGHJKLMNÑPQRSTVWXYZ]{3}[A-Z0-9]{1}[0-9]{1}$/';

        // Validar si la CURP cumple con el formato
        if (preg_match($regex, $curp)) {
            return true; // CURP válida
        } else {
            return false; // CURP inválida
        }
    }
}
