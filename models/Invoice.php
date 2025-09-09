<?php

namespace ApiYkt\Models;


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
        $sql = "SELECT fba.business_name, fba.rfc, fba.mail AS mail_billing,
        code_cfdi,
        UPPER (billing_type_description) AS billing_type_description,
        fba.street AS street_billing,
        fba.between_streets AS between_streets_billing,
        fba.ext_number AS ext_number_billing,
        fba.int_number AS int_number_billing,
        fba.colony AS colony_billing,
        fba.delegation AS delegation_billing,
        fba.postal_code AS postal_code_billing,
        fam.*
        FROM $this->table AS fba
        INNER JOIN families_ykt.families AS fam ON fam.id_family = fba.id_family
        INNER JOIN families_billing_data.cfdi_uses AS cfu ON cfu.id_cfdi_uses = fba.id_cfdi_uses
        INNER JOIN families_billing_data.billing_types AS bt ON bt.id_billing_types = fba.id_billing_types
        WHERE fam.family_code = :family_code
        AND fba.current_address = 1
        ";
        $stmt = $this->conn->prepare($sql);
        $response = $stmt->execute([':family_code' => $family_code]);



        $famInv = $stmt->fetchAll(PDO::FETCH_OBJ);
        if (!$famInv || count($famInv) === 0) {



            return $this->getGeneralPublicInvoice($family_code);
        }

        $fam_data = $famInv[0];
        $famInvoiceData = [
            "basicInfo" => [
                "familyId" => $fam_data->id_family,
                "familyCode" => $fam_data->family_code,
                "familyName" => $fam_data->family_name,
                "invoiceType" => $fam_data->billing_type_description,
            ],
            "reciverInfo" => [
                "cfdiUse" => $fam_data->code_cfdi,
                "reciverName" => $fam_data->business_name,
                "Rfc" => $fam_data->rfc,
                "reciverMail" => $fam_data->mail_billing,
                "TaxResidence" => "MEX",
                "invoiceAddress" => [
                    "street" => $fam_data->street_billing,
                    "extNumber" => $fam_data->ext_number_billing,
                    "intNumber" => $fam_data->int_number_billing,
                    "colony" => $fam_data->colony_billing,
                    "delegation" => $fam_data->delegation_billing,
                    "zipCode" => $fam_data->postal_code_billing,
                    "betweenStreets" => $fam_data->between_streets_billing,
                ]
            ],
            "students" => $this->getStudentsByFamily($family_code)
        ];

        return (object) [
            "response_code" => $response ? 200 : 404,
            "response" => $response,
            "invoiceData" => $famInvoiceData
        ];
    }
    public function getStudentsByFamily($family_code)
    {
        $sql = "SELECT stud.id_student, UPPER(student_code) AS student_code,
        UPPER(CONCAT(stud.name, ' ', stud.lastname)) AS student_name,
        stud.curp, psd.precentage_payment, acle.sat_code_payment,
        SUBSTRING(stud.curp, 1, 10) AS rfc_base, degreee_equals AS education_level, revoe, stud.gender
        FROM families_ykt.families AS fam
        INNER JOIN school_control_ykt.students AS stud ON stud.id_family = fam.id_family
        INNER JOIN school_control_ykt.inscriptions AS insc ON stud.id_student = insc.id_student
        INNER JOIN school_control_ykt.groups AS gps ON gps.id_group = insc.id_group
        INNER JOIN school_control_ykt.academic_levels_grade AS aclg ON aclg.id_level_grade = gps.id_level_grade
        INNER JOIN school_control_ykt.academic_levels AS al ON al.id_academic_level = aclg.id_academic_level
        INNER JOIN txt_generator.academic_levels_equals AS acle ON acle.id_academic_level = al.id_academic_level
        INNER JOIN school_control_ykt.assignments AS asgm ON asgm.id_assignment = (SELECT id_assignment FROM school_control_ykt.assignments WHERE id_group = gps.id_group LIMIT 1)
        INNER JOIN school_control_ykt.revoe_levels AS rev ON rev.id_level_grade = aclg.id_level_grade AND asgm.id_level_combination = rev.id_level_combination
        LEFT JOIN families_billing_data.payments_students_detail AS psd 
            ON psd.id_paments_students_detail = (SELECT id_paments_students_detail FROM families_billing_data.payments_students_detail psd WHERE psd.id_student = stud.id_student LIMIT 1)
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
            $curp_student = $student->curp;
            if (!$this->validarCURP($curp_student)) {
                switch ($student->gender) {
                    case '0':
                        $curp_student = 'XEXX010101HNEXXXA4';
                        break;
                    case '1':
                        $curp_student = 'XEXX010101MNEXXXA8';
                        break;

                    default:
                        $curp_student = 'XEXX010101HNEXXXA4';
                        break;
                }
            }

            $rfc_base = substr($curp_student, 0, 10);

            $famStudents[] = [
                "studentId" => $student->id_student,
                "studentCode" => $student->student_code,
                "studentName" => $student->student_name,
                "revoe" => $student->revoe,
                "curp" => $curp_student,
                "educationalLevel" => $student->education_level,
                "rfc" => $rfc_base,
                "studentPaymentDistribution" => $student->precentage_payment,
                "satCodePayment" => $student->sat_code_payment,
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

    public function updateSuppliesFileRoute($id_relationship, $filePath)
    {
        $sql = "UPDATE school_control_ykt.supplies_list_relationship
            SET archive_route = :route
            WHERE id_supplies_list_relationship = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':route' => $filePath,
            ':id' => $id_relationship
        ]);
    }

    public function registerPaymentsBatch(array $payments)
    {
        $sql = "INSERT INTO families_billing_data.payments_received (
            id_payment_concepts,
            id_months_inscription,
            id_payment_methods,
            id_family,
            amount,
            date_recipt,
            reference,
            date_log,
            test_payment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            return [
                "success" => false,
                "message" => "Error al preparar la consulta.",
                "errors" => []
            ];
        }

        $date_log = date("Y-m-d H:i:s");
        $inserted = 0;
        $errors = [];

        foreach ($payments as $index => $p) {
            $required = [
                'id_payment_concepts',
                'id_months_inscription',
                'id_payment_methods',
                'id_family',
                'amount',
                'date_recipt',
                'reference'
            ];

            foreach ($required as $field) {
                if (!isset($p[$field])) {
                    $errors[] = "Pago #$index: falta el campo '$field'.";
                    continue 2;
                }
            }

            $success = $stmt->execute([
                $p['id_payment_concepts'],
                $p['id_months_inscription'],
                $p['id_payment_methods'],
                $p['id_family'],
                $p['amount'],
                $p['date_recipt'],
                $p['reference'],
                $date_log,
                $p['test_payment']
            ]);

            if ($success) {
                $inserted++;
            } else {
                $err = $stmt->errorInfo();
                $errors[] = "Pago #$index: error al insertar: " . $err[2];
            }
        }

        if (!empty($errors)) {
            http_response_code(400); // Bad request si hay errores
            return [
                "success" => false,
                "message" => "Se encontraron errores al registrar algunos pagos.",
                "errors" => $errors
            ];
        }

        return [
            "success" => true,
            "message" => "Pago recibidos.",
        ];
    }


    public function registerSinglePayment(array $payment)
    {
        // Renombrar claves si es necesario
        if (isset($payment['familia'])) {
            $payment['family_code'] = $payment['familia'];
            unset($payment['familia']);
        }

        if (isset($payment['family_code'])) {
            // Si family_code es un valor externo y necesitas convertirlo a id_family,
            // aquí deberías hacer una consulta a la base de datos.
            $stmt = $this->conn->prepare("SELECT id_family FROM families_ykt.families WHERE family_code = ?");
            $stmt->execute([$payment['family_code']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                http_response_code(404);
                return ["error" => "No se encontró una familia con el código proporcionado."];
            }
            $payment['id_family'] = $result['id_family'];
            unset($payment['family_code']);
        }

        $month_number = (int) substr($payment['vencimiento'], 5, 2); // "2025-01-01" → "01" → 1

        $stmt = $this->conn->prepare("SELECT id_months_inscription FROM families_billing_data.months_inscription WHERE month_number = ?");
        $stmt->execute([$month_number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || !isset($result['id_months_inscription'])) {
            http_response_code(404);
            return [
                "success" => false,
                "message" => "No se encontró un mes de inscripción con número '{$month_number}'."
            ];
        }

        $payment['id_months_inscription'] = $result['id_months_inscription'];
        unset($payment['vencimiento']);


        // Adaptar el formato esperado por registerPaymentsBatch
        $payment2 = [
            'id_payment_concepts'     => $payment['concepto'],
            'id_months_inscription'   => $payment['id_months_inscription'], // traducido desde 'vencimiento'
            'id_payment_methods'      => $payment['metodo'],
            'id_family'               => $payment['id_family'],            // traducido desde 'familia'
            'amount'                  => $payment['importe'],                // traducido desde 'importe'
            'date_recipt'             => $payment['fecha'],                  // traducido desde 'fecha'
            'reference'               => $payment['referencia'],              // mismo nombre
            'test_payment'            => 0
        ];

        // Llamar a la función que ya hace la inserción, pero con arreglo de un solo elemento
        $result = $this->registerPaymentsBatch([$payment2]);

        if (!empty($result['errors'])) {
            http_response_code(400);
        }

        return $result;
    }

    public function registerSinglePaymentTest(array $payment)
    {
        // Renombrar claves si es necesario
        if (isset($payment['familia'])) {
            $payment['family_code'] = $payment['familia'];
            unset($payment['familia']);
        }

        if (isset($payment['family_code'])) {
            // Si family_code es un valor externo y necesitas convertirlo a id_family,
            // aquí deberías hacer una consulta a la base de datos.
            $stmt = $this->conn->prepare("SELECT id_family FROM families_ykt.families WHERE family_code = ?");
            $stmt->execute([$payment['family_code']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                http_response_code(404);
                return ["error" => "No se encontró una familia con el código proporcionado."];
            }
            $payment['id_family'] = $result['id_family'];
            unset($payment['family_code']);
        }

        $month_number = (int) substr($payment['vencimiento'], 5, 2); // "2025-01-01" → "01" → 1

        $stmt = $this->conn->prepare("SELECT id_months_inscription FROM families_billing_data.months_inscription WHERE month_number = ?");
        $stmt->execute([$month_number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || !isset($result['id_months_inscription'])) {
            http_response_code(404);
            return [
                "success" => false,
                "message" => "No se encontró un mes de inscripción con número '{$month_number}'."
            ];
        }

        $payment['id_months_inscription'] = $result['id_months_inscription'];
        unset($payment['vencimiento']);


        // Adaptar el formato esperado por registerPaymentsBatch
        $payment2 = [
            'id_payment_concepts'     => $payment['concepto'],
            'id_months_inscription'   => $payment['id_months_inscription'], // traducido desde 'vencimiento'
            'id_payment_methods'      => $payment['metodo'],
            'id_family'               => $payment['id_family'],            // traducido desde 'familia'
            'amount'                  => $payment['importe'],                // traducido desde 'importe'
            'date_recipt'             => $payment['fecha'],                  // traducido desde 'fecha'
            'reference'               => $payment['referencia'],              // mismo nombre
            'test_payment'            => 1
        ];

        // Llamar a la función que ya hace la inserción, pero con arreglo de un solo elemento
        $result = $this->registerPaymentsBatch([$payment2]);

        if (!empty($result['errors'])) {
            http_response_code(400);
        }

        return $result;
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
