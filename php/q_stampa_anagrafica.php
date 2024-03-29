<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Stampa anagrafica soci</title>
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
*  Stampa l'anagrafica del socio
*
*  @file q_stampa_anagrafica.php
*  @abstract Stampa l'anagrafica del socio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-17
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-17
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
$redirect="../php/q_stampa_anagrafica.php";
$print_target="../php/stampa_anagrafica.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlid_gruppo_par=0;

$desc_sottosezione='';

$sqlselect_gruppo_par = "SELECT id, descrizione
                                          FROM   gruppo_parrocchiale
                                          WHERE 1";

if(!$multisottosezione) {
   $sqlselect_gruppo_par .= " AND id_sottosezione = " . $sott_app; 
 }

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

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

// Verifica abilitazioni utente
$authMask = ritorna_abilitazioni($conn);

$ctrAuth=0;
foreach ($authMask as $key => $value) {
               if($debug) { // Visualizzo autorizzazioni
                   echo "$fname Auth -> $key = $value<br>";
                  } // end foreach
               $ctrAuth += $value; // Controllo autorizzazioni
   }
// Fine verifica abilitazioni

  if ($_POST) { // se post allora ho modificato i valori di selezione

      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";

                     switch($key) {
      		                     case "id_old": // sottosezione precedente
      					                    $sqlid_old = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                      case "id_gruppo_par": // gruppo parrocchiale
      					                    $sqlid_gruppo_par = $value;
      					                    break;
                    }
                  }
     }

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlid_gruppo_par=0;
    }
    
  if($sqlid_sottosezione > 0)  
     $sqlselect_gruppo_par .= " AND id_sottosezione = " . $sqlid_sottosezione;
    

  $sqlselect_gruppo_par .= " ORDER BY 2";

  if($debug)
     echo "$fname: SQL = $sqlselect_gruppo_par<br>";
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body></html>";
     return;
     }
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Stampa anagrafica soci</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_gruppo_par' value='" . $sqlid_gruppo_par . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' onChange='this.form.submit();'>" ;
      echo "<option value=0>--- Tutte ---</option>";
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
  echo "<form action='" . $print_target . "' target='_blank' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";

  echo "<tr>";
  echo "<td><p>Gruppo</p></td>";
  echo "<td><select class='search' name='id_gruppo_par'>" ;
  echo "<option value=0>--- Tutti ---</option>";

  $result = $conn->query($sqlselect_gruppo_par);
   while($row = $result->fetch_assoc()) {
       	    echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_gruppo_par)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       	
  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Suddividi per gruppo</p></td>";
  echo "<td><p><input type='checkbox' name='suddivisione'></p></td>";
  echo "</tr>";

  echo "<tr>";  
  echo "<td><table>";
  echo "<tr><td><input id='e' name='effettivo' type='radio' value=1><label for='e'>Effettivo</label></td></tr>";
  echo "<tr><td><input id='a' name='effettivo' type='radio' value=2><label for='a'>Ausiliario</label></td></tr>";
  echo "<tr><td><input id='t' name='effettivo' type='radio' value=0 checked><label for='t'>Tutti</label></td></tr>";
  echo "</table></td>";
    
  echo "<td><select name='id_categoria' class='search'>";
  $rCat = $conn->query("SELECT id, descrizione FROM categoria ORDER BY 2");
  echo "<option value=0>--- Seleziona eventuale categoria ---</option>";
  
  while($rC = $rCat->fetch_assoc()) {
  	         echo "<option value=" . $rC["id"] . ">" . htmlentities($rC["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";
 
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
  
  if($authMask["query"]) {
      echo "<tr>";
      echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Stampa'></p></form></td>";
      echo "</tr>";
  }
  echo "</form>";
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
