<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Ristampa ricevute emesse</title>
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
*  Ristampa le ricevute emesse
*
*  @file q_ristampa_ricevute.php
*  @abstract Ristampa le ricevute emesse
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-15
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-15
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="ricevute";
$redirect="../php/q_ristampa_ricevute.php";
$print_target="../php/stampa_ricevuta.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlnote='';
$sqlanno=date('Y');
$sqlanno_min=null;
$sqlanno_selected=$sqlanno;

$desc_sottosezione='';

$sqlselectanno_ricevuta = "SELECT MIN(YEAR(data_ricevuta)) amin
                                             FROM   ricevute";                                                          

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_ricevute = "SELECT LPAD(n_ricevuta, 5, '0') n_ricevuta, CONCAT(anagrafica.cognome,' ', anagrafica.nome) nome,
                                                  ricevute.importo
                                     FROM   ricevute, anagrafica
                                     WHERE anagrafica.id = ricevute.id_socio";


if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  if ($_POST) { // se post allora ho modificato i valori di selezione
      $update = true;
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";

                     switch($key) {
      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                      case "anno": // anno di riferimento
      					                    $sqlanno_selected = $value;
      					                    break;
                    }
                  // $index++;
                  }
     }

  $sqlselectanno_ricevuta .= " WHERE id_sottosezione = " . $sqlid_sottosezione;     

  $result = $conn->query($sqlselectanno_ricevuta);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];
  
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  if(!$sqlanno_min)
      $sqlanno_min = date('Y');

  if($debug)
     echo "ANNO  DI PARTENZA = " . $sqlanno_min;
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Ristampa ricevute emesse</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno_selected . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' required onChange='this.form.submit();'>" ;
      echo "<option value=''>--- Seleziona la sottosezione ---</option>";
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></td>'; 
     }
  echo "</tr>";
  echo "</form>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Anno di riferimento</p></td>";
  echo "<td><select class='required' name='anno' required onChange='this.form.submit();'>" ;
  $ctr=$sqlanno;
  while($ctr >= $sqlanno_min) {
  	         echo "<option value=" . $ctr;
  	         if($ctr == $sqlanno_selected)
  	             echo " selected";
  	          echo ">" . $ctr . "</option>";
  	         $ctr--;
             } 	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

// Campo per la ricerca della ricevuta
  $sqlselect_ricevute .= " AND ricevute.id_sottosezione = " . $sqlid_sottosezione .
                                        " AND     YEAR(data_ricevuta) = " . $sqlanno_selected .
                                        " ORDER BY 1 DESC";
  if($debug)  
      echo $sqlselect_ricevute;

  echo "<form name='searchTxt' action='" . $print_target . "' method='POST' target='_blank'>";
  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">"; 
  echo "<input type='hidden' name='anno' value=" . $sqlanno_selected . ">"; 
  echo "<tr>";
  echo "<td><p class='search'>Seleziona ricevuta</td></p></td>";
  echo "<td><select class='search' name='id-hidden' onChange='this.form.submit();'>";
  $result = $conn->query($sqlselect_ricevute);
  echo "<option value=>--- Seleziona la ricevuta da stampare ---</option>";
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["n_ricevuta"] . "\">" . "Ricevuta # " . $row["n_ricevuta"] . " " . htmlentities($row["nome"], $defCharsetFlags, $defCharset) .
            " &minus;&gt; " . money_format('%(!n',$row["importo"]) . "&nbsp;&euro;</option>";
           } 	
       
  // End select
  echo "</select>";
  echo "</td></tr>";
  echo "</form>";

  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
