<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$path_isc = dirname(__FILE__, 3);

include_once "$path_isc/vendor/autoload.php";
include_once 'class/Families.php';

function getAllFamiliesActives(){
    $familes = new Families;

    return $familes->getAllFamiliesActives();
}

function getAllDadsActives(){
    $familes = new Families;

    return $familes->getAllDadsActives();
}

function getAllMomsActives(){
    $familes = new Families;

    return $familes->getAllMomsActives();
}

function getAllStudentsActives(){
    $familes = new Families;

    return $familes->getAllStudentsActives();
}

function getRouteSupervisors(){
    $familes = new Families;

    return $familes->getRouteSupervisors();
}

function getDebtorFamilies(){
    $familes = new Families;

    return $familes->getDebtorFamilies();
}