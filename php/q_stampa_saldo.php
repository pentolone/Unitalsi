<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Stampa attivit&agrave;</title>
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
*  Stampa il saldo delle attivita'/viaggio
*
*  @file q_stampa_saldo.php
*  @abstract Stampa il saldo delle attivita'
*  @author Luca Romano
*  @version 1.0
*  @time 2017-0-08
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-08
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
$table_name='';
$redirect="../php/q_stampa_saldo.php";
$print_target="../php/stampa_saldo.php";
$titolo='Sconosciuto';
$titoloSelect='--- Sconosciuto ---';

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqltipo='-';
$sqlanno_selected=$sqlanno;
$desc_sottosezione='';

$sqlselectanno_attivita = "SELECT MIN(anno) amin
                                            FROM ";                                                          

$sqlselect_attivita = "SELECT COUNT(*) ctr, SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                attivita_m.dal dal_order,
                                                attivita_m.id id_prn, CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa
                                    FROM  attivita_detail,
                                                attivita_m,
                                                attivita
                                    WHERE attivita.id =attivita_m.id_attivita
                                    AND     attivita_detail.id_attpell = attivita_m.id";
               
if(!$multisottosezione) {
   $sqlselect_attivita .= " AND attivita_m.id_sottosezione = " . $sott_app; 
 }

$sqlselect_pellegrinaggio = "SELECT COUNT(*) ctr, SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa
                                    FROM   descrizione_pellegrinaggio,
                                                pellegrinaggi,
                                                attivita_detail,
                                                anagrafica
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                    AND     attivita_detail.tipo ='V'
                                    AND     attivita_detail.id_socio = anagrafica.id
                                    AND     attivita_detail.id_attpell = pellegrinaggi.id";

if(!$multisottosezione) {
   $sqlselect_pellegrinaggio .= " AND pellegrinaggi.id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE " . $table_name - ".id_sottosezione = " . $sott_app; 
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
parse_str($_SERVER['QUERY_STRING'], $dest);
$sqltipo = $dest["tipo"];
$redirect="../php/q_stampa_saldo.php?tipo=" . $sqltipo;

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

if($sqltipo == 'A') {
    $titolo = 'Stampa saldo attivit&agrave;';
    $tagForm="Seleziona attivit&agrave";
    $titoloSelect ='--- Seleziona attivit&agrave ---';   
    $table_name = 'attivita_m';
    $sqlExec = $sqlselect_attivita;
  }

if($sqltipo == 'V') {
    $titolo = 'Stampa  saldo viaggio/pellegrinaggio';
    $tagForm="Seleziona viaggio/Pellegrinaggio";
    $titoloSelect ='--- Seleziona il viaggio/pellegrinaggio ---';
    $table_name = 'pellegrinaggi'; 
    $sqlExec = $sqlselect_pellegrinaggio;
  }
$sqlselectanno_attivita .= $table_name;

  $sqlselectanno_attivita .= " WHERE anno > 0 AND id_sottosezione = " . $sqlid_sottosezione;     

  if($debug)
      echo "$fname SQL = $sqlselectanno_attivita<br>";

  $result = $conn->query($sqlselectanno_attivita);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlanno=date('Y');
    }

  $sqlExec .= " AND " . $table_name . ".anno = " . $sqlanno;
  //$sqlselect_pellegrinaggio .= " AND " . $table_name . ".anno = " . $sqlanno;
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  if($sqlid_sottosezione > 0)  
     $sqlExec .= " AND " . $table_name . ".id_sottosezione = " . $sqlid_sottosezione;
    
  $sqlExec .= " GROUP BY 2,3,4,5 ORDER BY 4 DESC, 5";

  if($debug)
     echo "$fname: SQL EXEC = $sqlExec<br>";
     
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>" . $titolo ."</td>";
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
  echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
  echo "<tr>";
  echo "<td><p>" . $tagForm . "</td></p></td>";
  echo "<td><p><select class='search' name='id_prn'>" ;
  echo "<option value=0>" . $titoloSelect . "</option>";
  $result = $conn->query($sqlExec);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"] .">";
       	   echo htmlentities($row["desa"],$defCharsetFlags, $defCharset) . " (#" . $row["ctr"] . ") -&gt; " .  $row["dal"] . "</option>";
       	}
  echo '</select></p></td>'; 
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Totalizza per gruppo</td></p></td>";
  echo "<td><input type='checkbox'  name='totalizza' value='1'></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Formato stampa</td></p></td>";

  if($sqltipo == 'A') {
      echo "<td><input type='radio'  name='prn_format' value='P' checked>A4<br>
                        <input type='radio'   name='prn_format' value='L'>Landscape</p></td>";
     } 	

  if($sqltipo == 'V') {
      echo "<td><input type='radio'  name='prn_format' value='P'>A4<br>
                        <input type='radio'   name='prn_format' value='L' checked>Landscape</p></td>";
     } 	

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
