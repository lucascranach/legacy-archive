<?php

$attr = (isset($_SESSION['search_attr'])) ? $_SESSION['search_attr'] : [];
$date = (isset($_SESSION['search_date'])) ? $_SESSION['search_date'] : [];
$tech = (isset($_SESSION['search_tech'])) ? $_SESSION['search_tech'] : [];
$collection = (isset($_SESSION['search_collection'])) ? $_SESSION['search_collection'] : [];
$thesaurus = (isset($_SESSION['search_thesau'])) ? $_SESSION['search_thesau'] : [];

$array = [
    'attr' => $attr,
    'date' => $date,
    'tech' => $tech,
    'collection' => $collection,
    'thesaurus' => $thesaurus,
];

echo json_encode($array);
