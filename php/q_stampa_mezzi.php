<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Stampa composizione mezzi viaggio/pellegrinaggio</title>
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
*  Stampa la composizione del mezzo di trasporto
*
*  @file q_stampa_mezzi.php
*  @abstract Stampa la composizione dei mezi per il viaggio selezionato
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-06
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-06
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
$redirect="../php/q_stampa_mezzi.php";
$print_target="../php/stampa_mezzi.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlid_attpell=0;
$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqlanno_selected=$sqlanno;
$sqldata_viaggio=null;
$desc_sottosezione='';
$id_prn=array();

$sqlselectanno_viaggio = "SELECT MIN(anno) amin
                                            FROM   pellegrinaggi";                                                          

$sqlselect_viaggio = "SELECT COUNT(*) ctr, SUBSTRING(DATE_FORMAT(mezzi_detail.data_viaggio,'" . $date_format ."'),1,10) dal, 
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                mezzi_detail.data_viaggio dal_order,
                                                pellegrinaggi.id id_attpell,
                                                descrizione_pellegrinaggio.descrizione desp
                                    FROM   pellegrinaggi,
                                                 descrizione_pellegrinaggio,
                                                 mezzi_detail
                                    WHERE  pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                    AND      mezzi_detail.id_attpell = pellegrinaggi.id
                                    AND      mezzi_detail.id_sottosezione = pellegrinaggi.id_sottosezione
                                    AND      (mezzi_detail.data_viaggio = pellegrinaggi.dal OR mezzi_detail.data_viaggio = pellegrinaggi.al)";
                                   // AND      pellegrinaggi.al != NULL";
if(!$multisottosezione) {
   $sqlselect_viaggio .= " AND pellegrinaggi.id_sottosezione = " . $sott_app; 
 }

$sqlselect_mezzi = "SELECT mezzi_disponibili.descrizione desdis,
                                              SUM(mezzi_detail.n_posti),
                                              mezzi_disponibili.capienza,
                                              mezzi_trasporto.descrizione desmez,
                                              mezzi_detail.id_mezzo
                                  FROM   mezzi_disponibili,
                                              mezzi_detail,
                                              mezzi_trasporto
                                  WHERE mezzi_detail.id_mezzo = mezzi_disponibili.id
                                  AND     mezzi_disponibili.id_mezzo = mezzi_trasporto.id";


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

      		                      case "anno": // anno
      					                    $sqlanno = $value;
      					                    break;

      		                      case "id_attpell": // id viaggio/pellegrinaggio+data (separato da ",")
      					                    $sqlid_attpell = explode(",", $value)[0];
      					                    $sqldata_viaggio = explode(",", $value)[1];
      					                    break;

                    }
                  }
     }

  $sqlselectanno_viaggio .= " WHERE id_sottosezione = " . $sqlid_sottosezione;     
  $sqlselect_viaggio .= " AND pellegrinaggi.anno = " . $sqlanno .
                                      " AND pellegrinaggi.id_sottosezione = " . $sqlid_sottosezione;
                                     // " AND pellegrinaggi.id = " . $sqlid_attpell;

  $sqlselect_mezzi .= " AND mezzi_disponibili.id_sottosezione = " . $sqlid_sottosezione .
                                   " AND mezzi_detail.id_attpell = " . $sqlid_attpell;   
  if($debug) {
      echo "$fname SQL = $sqlselectanno_viaggio<br>";
      echo "$fname SQL = $sqlselect_mezzi<br>";
   }

  $result = $conn->query($sqlselectanno_viaggio);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlanno=date('Y');
    }
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  $sqlselect_viaggio .= " GROUP BY 2,3,4,5,6 ORDER BY 4 DESC, 6";

  if($debug)
     echo "$fname: SQL = $sqlselect_viaggio<br>";
     
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body>";
     echo "</html>";
     return;
     }
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Stampa composizione mezzi</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' onChange='this.form.submit();'>" ;
      //echo "<option value=0>--- Tutte ---</option>";
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
  //echo "<form action='" . $print_target . "' target='_blank' method='POST'>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Seleziona viaggio/pellegrinaggio</p></td>";
  echo "<td><select class='required' required name='id_attpell'  onChange='this.form.submit();'>" ;
 
  echo "<option value=>--- Seleziona viaggio/pellegrinaggio ---</option>";
  $result = $conn->query($sqlselect_viaggio);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value='" . $row["id_attpell"] . "," . $row["dal_order"] . "'";
       	   if($row["id_attpell"] == $sqlid_attpell && $row["dal_order"] == $sqldata_viaggio)
       	      echo " selected";
       	   echo ">";
       	   echo $row["dal"] . " -&gt; " . htmlentities($row["desp"],$defCharsetFlags, $defCharset) . " (#" . $row["ctr"] .")</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  echo "</form>";

  if($sqlid_attpell > 0) {  // Elenco mezzi associati al viaggio/pellegrinaggio
     $sqlselect_mezzi .= " AND mezzi_detail.id_attpell = " . $sqlid_attpell .
                                      " GROUP BY 1,3,4,5";

     echo "<form action='" . $print_target . "' method='post' target='_blank'>";
     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
     echo "<input type='hidden' name='id_attpell' value='" . $sqlid_attpell . "," . $sqldata_viaggio . "'>";
     //echo "<input type='hidden' name='dal' value='" . $viaggioStart . "'>";
     //echo "<input type='hidden' name='al' value='" . $viaggioEnd . "'>";
     if($debug)
         echo "$fname SQL = $sqlselect_mezzi";

     echo "<tr>";
     echo "<td><p>Formato stampa</p></td>";
     echo "<td><input type='radio'  name='prn_format' value='P' checked>A4<br>
                       <input type='radio'   name='prn_format' value='L'>Landscape</p></td>";
     echo "</tr>";

     $result = $conn->query($sqlselect_mezzi);
     $index=0;
     while($row = $result->fetch_assoc()) {
     	         //$id_prn[$index] = $row["id_mezzo"];
               echo "<tr>";
     	         echo "<td colspan='2'><p>&nbsp;&nbsp;<input type='checkbox' name='id_prn[]' value=" . $row["id_mezzo"] . " checked>" . $row["desdis"] . " (" . $row["desmez"] . ")</p></td>";
               echo "</tr>";
               $index++;
              }
     echo "<tr>";
     echo "<td colspan='2'><hr></td>";
     echo "</tr>";
  
     if($authMask["query"]) {
         echo "<tr>";
         echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Stampa'></p></form></td>";
         echo "</tr>";
        }
     echo "</form>";
     }
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
