<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Occupazione viaggio/pellegrinaggio</title>
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
*  Visualizzazione dell'occupazione struttura per mese
*
*  @file visualizza_occupazione.php
*  @abstract Visualizza l'occupazione della struttura selezionata
*  @author Luca Romano
*  @version 1.0
*  @time 2017-05-08
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-03-08
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
require_once("../php/f_occupazione.php");
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$index=0;
$debug=false;
$update=false;
$fname=basename(__FILE__);
$mesi=array("Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                     "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre");

$id_pell=0;

$sqlanno=date('Y');
$sqlanno_min=date('Y');
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;


// SQL sottosezione
$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

// SQL minimo anno
$sqlselectanno_attivita = "SELECT MIN(anno) amin
                                            FROM  pellegrinaggi";                                                          

// SQL pellegrinaggi
$sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa
                                    FROM   descrizione_pellegrinaggio,
                                                pellegrinaggi
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";

// SQL occupazione


  $conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  if(($userid = session_check()) == 0)
    return;
 $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 

  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

  if ($_POST) { // se post allora ho modificato i valori di selezione

      foreach ($_POST as $key => $value) {
      	              if(is_array($value)) {
    	            	     if($debug)
    	            	         print_r($value);
    	            	     }
    	            	 else {

                        $kv[] = "$key=$value";
                        if($debug) {
                            echo $fname . ": KEY = " . $key . '<br>';
                            echo $fname . ": VALUE = " . $value . '<br>';
                          }
                     }

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

      		                      case "msg": // Messaggio
      					                    $msgAlert = $value;
      					                    break;

      		                      case "id-p": // viaggio/pellegrinaggio
      					                    $id_pell = $value;
            					              break;
                    }
                  }
                  if($id_pell > 0) {
                     f_occupazione($conn, $id_pell);
                     return;
                    }
//return;
     }

  $sqlselect_pellegrinaggio .= "  AND pellegrinaggi.id_sottosezione = $sqlid_sottosezione
                                                    AND pellegrinaggi.anno = $sqlanno
                                                   ORDER BY 3 DESC, 4";
  $sqlselectanno_attivita .= "  WHERE     pellegrinaggi.id_sottosezione = $sqlid_sottosezione";
  $result = $conn->query($sqlselectanno_attivita);
  $row = $result->fetch_assoc();
  $sqlanno_min = $row["amin"];

  if(!$sqlanno_min)
     $sqlanno_min = date('Y');   
  
  echo "<form action='../php/visualizza_occupazione.php' method='post'>";  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Seleziona i viaggi da visualizzare</td>";
  echo "</tr>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td class='required'><p class='required'>Sottosezione</td></p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='search'><input disabled class='required' id='descrizione' maxlength='100' size='110' type='value' value='" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."' required/></p></td>";
     }
  else { 
      echo "<td><p class='required'><select class='required' name='id_sottosezione' onChange='this.form.submit();'>" ;
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

  echo "<tr>";
  echo "<td><p class='required'>Seleziona anno</p></td>";
  echo "<td><select class='required' name='anno' onChange='this.form.submit();'>";
  $index = $sqlanno;
  while($index >= $sqlanno_min) {
  	         echo "<option value=$index>$index</option>";
  	         $index--;
  }
  echo "</select></td>";
  echo "</tr>";
  
  $result = $conn->query($sqlselect_pellegrinaggio);
  $index = 0;
  while($row = $result->fetch_assoc()) {
       	   if($index == 0) {
                echo "<tr><td colspan='2'><hr></td></tr>";
                echo "<tr>";
                echo "<td><p class='required'>Seleziona viaggio/pellegrinaggio</p></td>";
                echo "<td><select class='required' name='id-p'>"; 
                echo "<option value=0>--- Seleziona dalla lista ---</option>";
             }
       	   echo "<option value=" . $row["id_prn"] . ">";
       	   echo htmlentities($row["desa"] ,$defCharsetFlags, $defCharset) . "</option>";
       	   $index++;
       	 }
       if($index > 0) {
           echo '</select></td>'; 
           echo '</tr>'; 

           echo '<tr>';
           echo '<td colspan="2" align="center"><input class="in_btn" type="submit" value="Go"></td>';
           echo '</tr>';
        }
        else {
        	  echo "<tr><td colspan='2'><h2>Nessun dato trovato</h2></td></tr>";
        }
       echo '</table>'; 
       echo '</form>';
 ?>
 </body>
 </html> 


