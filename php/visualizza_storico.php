<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Storico partecipazioni</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <link rel="apple-touch-icon" sizes="57x57" href="/images/fava/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/images/fava/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/images/fava/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/images/fava/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/images/fava/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/images/fava/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/images/fava/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/images/fava/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/images/fava/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/images/fava/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/images/fava/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/images/fava/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/images/fava/favicon-16x16.png">
  <link rel="manifest" href="/images/fava/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/images/fava/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">  
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
  
</head>
<body>
<?php
/****************************************************************************************************
*
*  Visualizzazione lo storico delle partecipazione per il socio selezionato
*
*  @file visualizza_storico.php
*  @abstract Visualizza lo storico attivita'/viaggio del socio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-10-10
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-10-10
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
$debug=false;
$update=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$sqlid_socio=0;
$sql_anno=0;
$sql_descrizione=null;
$sqlnome=null;
$sql_socio = "SELECT CONCAT(cognome, ' ', nome) nome
                       FROM   anagrafica
                       WHERE id =?";

$sql_storico = "SELECT attivita_m.anno,
                                      attivita.descrizione
                          FROM   attivita,
                                      attivita_m,
                                      attivita_detail
                          WHERE attivita_detail.id_socio = ?
                          AND     attivita_detail.tipo = 'A'
                          AND     attivita_detail.id_attpell = attivita_m.id
                          AND     attivita_m.id_attivita = attivita.id
                          UNION
                          SELECT pellegrinaggi.anno,
                                       descrizione_pellegrinaggio.descrizione
                          FROM   descrizione_pellegrinaggio,
                                      pellegrinaggi,
                                      attivita_detail
                          WHERE attivita_detail.id_socio = ?
                          AND     attivita_detail.tipo = 'V'
                          AND     attivita_detail.id_attpell = pellegrinaggi.id
                          AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                          ORDER BY 1 DESC, 2";
$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
parse_str($_SERVER['QUERY_STRING'], $dest);
$sqlid_socio = $dest["id_socio"];

if(!$sqlid_socio)
   return;
   
$stmt = $conn->prepare($sql_socio);
$stmt->bind_param("i", $sqlid_socio);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($sqlnome);

$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare($sql_storico);
$stmt->bind_param("ii", $sqlid_socio, $sqlid_socio);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($sqlanno, $sql_descrizione);

echo "<table style='width:100%;'>";
echo "<tr>";
echo "<td style='text-align: center;'><p class='required'>" . htmlentities($sqlnome, $defCharsetFlags, $defCharset) . "</p></td>";
echo "</tr>";

echo "<tr>";

echo "<td style='text-align: center;'><select class='search' size=10>";

$index=0;
$old_anno=0;
while($stmt->fetch()) {
	       if($sqlanno != $old_anno) {
	       	 if($index > 0)
	       	    echo "</optgroup>";
	          echo "<optgroup label=$sqlanno>";
	          $old_anno = $sqlanno;
           }
	       echo "<option disabled>" . htmlentities($sql_descrizione, $defCharsetFlags, $defCharset) . "</option>";
	       $index++;
      }
         
if($index == 0)
   echo "<option disabled>--- Nessun dato storico presente ---</option>";
else 
   echo "</optgroup>";
echo "</select></td>";
echo "</tr>";

echo "<tr>";
echo "<td style='text-align: center;'><input type='button' class='in_btn' value='Chiudi'
          onClick='window.close();'></td>";
echo "</tr>";
echo "</table>";

?>
