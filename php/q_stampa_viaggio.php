<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Stampa viaggio/pellegrinaggio</title>
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
*  Stampa il viaggio
*
*  @file q_stampa_viaggio.php
*  @abstract Stampa il viaggio/pellegrinaggio selezionato
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-20
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-20
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$date_format=ritorna_data_locale();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="ricevute";
$redirect="../php/q_stampa_viaggio.php";
$print_target="../php/stampa_viaggio.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqlanno_selected=$sqlanno;
$desc_sottosezione='';

$sqlselectanno_viaggio = "SELECT MIN(anno) amin
                                            FROM   viaggi";                                                          

$sqlselect_viaggio = "SELECT COUNT(*) ctr, SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desp
                                    FROM   pellegrinaggi,
                                                 descrizione_pellegrinaggio
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";

if(!$multisottosezione) {
   $sqlselect_viaggio .= " AND pellegrinaggi.id_sottosezione = " . $sott_app; 
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

      		                      case "anno": // anno
      					                    $sqlanno = $value;
      					                    break;
                    }
                  }
     }

  $sqlselectanno_viaggio .= " WHERE id_sottosezione = " . $sqlid_sottosezione;     

  if($debug)
      echo "$fname SQL = $sqlselectanno_viaggio<br>";

  $result = $conn->query($sqlselectanno_viaggio);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlanno=date('Y');
    }

  $sqlselect_viaggio .= " AND viaggi.anno = " . $sqlanno;
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  if($sqlid_sottosezione > 0)  
     $sqlselect_viaggio .= " AND viaggi.id_sottosezione = " . $sqlid_sottosezione;
    
  $sqlselect_viaggio .= " GROUP BY 2,3,4,5 ORDER BY 3 DESC, 5";

  if($debug)
     echo "$fname: SQL = $sqlselect_viaggio<br>";
     
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Stampa viaggi/pellegrinaggi</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p>Sottosezione</td></p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='search'><input disabled class='required' id='descrizione' maxlength='100' size='110' type='value' value='" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."' required/></p></td>";
     }
  else { 
      echo "<td><p class='search'><select class='search' name='id_sottosezione' onChange='this.form.submit();'>" ;
      echo "<option value=0>--- Tutte ---</option>";
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	   echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></p></td>'; 
     }
  echo "</tr>";
  echo "</form>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Anno di riferimento</td></p></td>";
  echo "<td><p class='required'><select class='required' name='anno' required onChange='this.form.submit();'>" ;
  $ctr=$sqlannostart;
  while($ctr >= $sqlanno_min) {
  	         echo "<option value=" . $ctr;
  	         if($ctr == $sqlanno)
  	             echo " selected";
  	          echo ">" . $ctr . "</option>";
  	         $ctr--;
             } 	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  echo "<form action='" . $print_target . "' target='_blank' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<tr>";
  echo "<td><p>Seleziona viaggio/pellegrinaggio</td></p></td>";
  echo "<td><p><select class='search' name='id_prn'>" ;
  echo "<option value=0>--- Tutti ---</option>";
  $result = $conn->query($sqlselect_viaggio);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"] .">";
       	   echo $row["dal"] . " -&gt; " . htmlentities($row["desp"],$defCharsetFlags, $defCharset) . " (#" . $row["ctr"] .")</option>";
       	}
  echo '</select></p></td>'; 
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Stampa'></p></form></td>";
  echo "</tr>";
  echo "</form>";
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
