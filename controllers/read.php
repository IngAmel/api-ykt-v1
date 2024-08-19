<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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