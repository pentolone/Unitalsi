<?php
require_once('../php/unitalsi_include_common.php');
require_once('../php/ritorna_numero_ricevuta.php');

$conn = DB_connect();

$conn->query('begin');
$ret = ritorna_numero_ricevuta($conn, 41, 'FT');
$conn->query('commit');

echo "Ricevuta = $ret";
?>