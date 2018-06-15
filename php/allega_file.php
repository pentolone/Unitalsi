<?php
/****************************************************************************************************
*
*  Allega il file alla tabella interessata
*
*  @file allega_file,php
*  @abstract allega il file alla tabella interessata
*  @input id riga tabella, nome tabella, utente,
                   contenuto, 
                   tipologia (default=Generico),
                   header (default=application/pdf)
                   sorgente (default='U'), U=Utente A=Applicativo
*  @author Luca Romano
*  @version 1.0
*  @time 2016-07-22
*  @history first release
*  
*  @first 1.0
*  @since 2016-07-22
*  @CompatibleAppVer > 2.6
*  @where Las Palmas de Gran Canaria
*
*
****************************************************************************************************/
function allega_file($id, $table, $utente, $content, $tipologia='Generico', 
                               $hd='application/pdf', $source='U') {

require_once('../php/chiamate_include_common.php');

$debug=false;
$success=false;
$sqlAdd='';
$sqlSocieta=0;

if(!check_key())
   return;

if(($userid = session_check()) == 0)
    return;

config_timezone();
$sqlutente = ritorna_utente();
$sqlSocieta = ritorna_societa_id();

// Database connect
$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$size = strlen($content);

$sqlAdd = "INSERT INTO attachments(id_societa, foreign_id, foreign_table, source, 
                                                             tipologia, file, size, hdfile, utente)
                   VALUES(" . $sqlSocieta . "," . $id . ", '" .$table . "','" . $source . "','" . 
                   $conn->real_escape_string($tipologia) . "', '" . 
                   $conn->real_escape_string($content) . "', " . $size .  ", '" . 
                   $hd . "', '" .
                   $conn->real_escape_string($utente) . "')";

if($debug)
    echo $sqlAdd;
 
if(!$conn->query($sqlAdd)) {
     	$msg = "allega_file(): dato NON inserito ERR = " . mysqli_error($conn);
     	echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "');</script>"; 
}
else 
  $success=true;
   
$conn->close();
return($success);
}
?>
