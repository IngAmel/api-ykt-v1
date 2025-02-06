<?php
set_time_limit(0);
include 'controllers/read.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['req_data'])) {
        $requested_data = $_GET['req_data'];
        $status_code = 400;
        $data = null;

        switch ($requested_data) {
            case 'get-all-families-actives':
                $status_code = 200;
                $data = getAllFamiliesActives();
                break;
            case 'get-all-dads-actives':
                $status_code = 200;
                $data = getAllDadsActives();
                break;
            case 'get-all-moms-actives':
                $status_code = 200;
                $data = getAllMomsActives();
                break;
            case 'get-all-students-actives':
                $status_code = 200;
                $data = getAllStudentsActives();
                break;
            case 'get-route-supervisors':
                $status_code = 200;
                $data = getRouteSupervisors();
                break;
            case 'get-families-without-access-school-transport':
                $status_code = 200;
                $data = getDebtorFamilies();
                break;
            default:
                $status_code = 404;
                $data = "The requested data parameter is unknown.";
                break;
        }

        http_response_code($status_code);
        echo json_encode(
            array(
                "data" => $data
            )
        );

    } else {
        http_response_code(404);
        echo json_encode(
            array(
                "data" => "The requested data parameter was not sent."
            )
        );
    }
}