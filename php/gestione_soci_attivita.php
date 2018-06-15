<?php
require_once('../php/unitalsi_include_common.php');
require_once('../php/ricerca_socio.php');

if(!check_key())
   return;
?>
<html>
<head>
  <title>Associazione socio/attivit&agrave;/Viaggi</title>
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
  <script type="text/javascript" src="../js/datefields.js"></script>
  
</head>
<body>

<?php
/****************************************************************************************************
*
*  Associa i soci alle attivita' configurate
*
*  @file gestione_soci_attivita.php
*  @abstract Gestisce l'associazione dei soci alle attivita'
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-24
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-24
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/disegna_tabella_costi_socio.php');
require_once('../php/carica_array.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$debug=false;
$search=false;
$fname=basename(__FILE__);
$kv = array();

$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$date_format=ritorna_data_locale();

$index=0;
$update=false;
$table_name="attivita_detail";
$redirect="../php/gestione_soci_attivita.php";
$print_target="../php/stampa_viaggio.php";
$titolo='Sconosciuto';
$titoloSelect='--- Sconosciuto ---';

$sqlID=0;
$sqlExec='';
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqlanno_selected=$sqlanno;
$desc_sottosezione='';
$sqlid_attpell=0;
$sqlid_socio=0;
$sqltipo_viaggio=0; // 0 = Nessuno, 1 = Andata, 2 = Ritorno, 3 = Andata e Ritorno
$sqltipo='A';
$sqlcosti=array();

$msgAlert=null;

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselectanno_attivita = "SELECT MIN(anno) amin
                                            FROM ";                                                          
// SQL attivita'
$sqlselect_attivita = "SELECT SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                attivita_m.dal dal_order,
                                                attivita_m.id id_prn, attivita.descrizione desa
                                    FROM  attivita,
                                                attivita_m
                                    WHERE attivita_m.id_attivita = attivita.id";

// SQL pellegrinaggi
$sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa
                                    FROM   descrizione_pellegrinaggio,
                                                pellegrinaggi
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";


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

parse_str($_SERVER['QUERY_STRING'], $dest);
$sqltipo = $dest["tipo"];

if(isset($dest["id_sottosezione"]))
    $sqlid_sottosezione = $dest["id_sottosezione"];

if(isset($dest["anno"]))
    $sqlanno = $dest["anno"];

if(isset($dest["id_attpell"]))
    $sqlid_attpell = $dest["id_attpell"];

if(isset($dest["msg"]))
    $msgAlert = $dest["msg"];

$redirect="../php/gestione_soci_attivita.php?tipo=" . $sqltipo;

if($sqltipo == 'A') {
    $titolo = 'Associazione soci attivit&agrave;';
    $tagForm="Seleziona attivit&agrave";
    $titoloSelect ='--- Seleziona attivit&agrave ---';   
    $table_name = 'attivita_m';
    $sqlExec = $sqlselect_attivita;
  }

if($sqltipo == 'V') {
    $titolo = 'Associazione soci viaggi/pellegrinaggi';
    $tagForm="Seleziona viaggio/Pellegrinaggio";
    $titoloSelect ='--- Seleziona il viaggio/pellegrinaggio ---';
    $table_name = 'pellegrinaggi'; 
    $sqlExec = $sqlselect_pellegrinaggio;
  }
$sqlselectanno_attivita .= $table_name;

  if ($_POST) { // se post allora ho modificato i valori di selezione

      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
           if($debug) {
                echo $fname . ": KEY = " . $key . '<br>';
                echo $fname . ": VALUE = " . $value . '<br>';
                echo $fname . ": INDEX = " . $index . '<br><br>';                    	
               }

                     switch($key) {
    		                      case "ricercaSocio": // Ricerca socio
      					                      $search=true;
      					                      $sTxt = $value;
      					                      break;

      		                     case "id_old": // sottosezione precedente
      					                    $sqlid_old = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                      case "anno": // anno
      					                    $sqlanno = $value;
      					                    break;

      		                      case "id_attpell": // attivita/pellegrinaggio
      					                    $sqlid_attpell = $value;
      					                    break;

      		                      case "id-s": // Socio
      					                    $sqlid_socio = $value;
      					                    break;

      		                      case "msg": // Messaggio
      					                    $msgAlert = $value;
      					                    break;
                    }
                  }
//return;
     }
  $sqlselectanno_attivita .= " WHERE anno > 0 AND id_sottosezione = " . $sqlid_sottosezione;     

  if($debug)
      echo "$fname SQL = $sqlselectanno_attivita<br>";

  $result = $conn->query($sqlselectanno_attivita);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlannostart=date('Y');
    }

  $sqlExec .= " AND " . $table_name . ".anno = " . $sqlanno;
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  if($sqlid_sottosezione > 0)  
     $sqlExec .= " AND " . $table_name . ".id_sottosezione = " . $sqlid_sottosezione;
    
  $sqlExec .= "  ORDER BY 5";

  if($debug)
      echo "$fname SQL EXEC = $sqlExec<br>";
     
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     return;
     }

  if($search) {
      echo "<form id='searchTxt' action='" . $redirect . "' method='post'>";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";

      risultati_ricercaS($conn, 'searchTxt', $sTxt, $sqlid_sottosezione);
      echo "</form>";
   }
  
  echo "<table>"; // Tabella principale
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>" . $titolo . "</td>";
  
  // Visualizzo partecipanti se sqlid_attpell > 0
  if($sqlid_attpell > 0)  {
  	   $rSpan=5;
  	   
  	   if($sqlid_socio > 0)
  	      $rSpan=4;
  
      echo "<form id='updateSocio' action=\"../php/gestione_soci_attivita.php?tipo="  . $sqltipo . "\" method=\"post\">";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      echo "<td class='titolo' rowspan=$rSpan>Partecipanti<br>"; 
      echo "<select name='id-s' size='7' class='search'>";
      
      $sql = "SELECT DISTINCT anagrafica.id, CONCAT(cognome,' ', nome) nome
                   FROM   attivita_detail,
                               anagrafica
                   WHERE attivita_detail.id_attpell = $sqlid_attpell
                   AND     anagrafica.id = attivita_detail.id_socio
                   ORDER BY 2";
                   
      $result = $conn->query($sql);

      $index=0;
      while($row = $result->fetch_assoc()) {
      	          echo "<option value=" . $row["id"] ." onClick=\" this.form.submit();\">". $row["nome"] . "</option>";
      	          $index++;
              }
       
      if($index == 0) {
      	   echo "<option disabled>--- Nessun partecipante presente ---</option>";
        }
      echo "</select></td>";
      echo "</form>";
   }
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
  echo "</form>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Anno di riferimento</p></td>";
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

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
  echo "<input type='hidden' name='anno' value=$sqlanno>";
  echo "<tr>";
  echo "<td><p class='required'>" . $tagForm . "</p></td>";
  echo "<td><p><select class='required' name='id_attpell' onChange='this.form.submit();'>" ;
  echo "<option value=0>" . $titoloSelect . "</option>";
  $result = $conn->query($sqlExec);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"];
       	   if($row["id_prn"] == $sqlid_attpell)
       	       echo " selected";
       	   echo ">";
       	   echo htmlentities($row["desa"],$defCharsetFlags, $defCharset) . " &minus;&gt; (". $row["dal"] . " - " . $row["al"] . ")</option>";
       	}
  echo '</select></p></td>'; 
  echo "</tr>";
  echo "</form>";

  if($sqlid_attpell > 0 && $sqlid_socio == 0) { // OK, attivita' selezionata, permetto ricerca socio
      echo "<form id='searchTxt' name='searchTxt' action='" . $redirect . "' method='POST'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sottosezione>";
      echo "<input type='hidden' name='anno' value=" . substr($sqlVdal, 0, 4) . ">";
      echo "<input type='hidden' name='tipo' value='" . $tipo . "'>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' id='id-hidden' name='id-hidden' value=" . $sqlid_socio . ">"; 
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value=$sqlanno>";
      
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      
      if($msgAlert) {
      	   echo "<tr><td colspan='2'><p class='alert'>" . htmlentities($msgAlert, $defCharsetFlags, $defCharset) . "</p></td></tr>";
         }

      echo "<tr>";
      echo "<td><p class='search'>Ricerca socio da associare</p></td>";

      echo "<td>";
      ricerca_socio('searchTxt');
      echo "</td></tr>";
      echo "</form>";
    	}

  echo "<tr>"; 
  if($sqlid_attpell == 0)
     echo "<td colspan='2'><hr></td>";
  else
     echo "<td colspan='3'><hr></td>";
  echo "</tr>";

  if($sqlid_socio > 0) { // OK, socio selezionato, carico i dati del socio
      echo "<tr>";
      echo "<td colspan='3'>";
      disegna_t_costi($conn, $sqlid_socio, $sqlid_attpell, $sqltipo, $sqlanno);
      $sqlcosti = carica_array($conn, $sqlid_attpell, $sqltipo);
      echo "</td>";
      echo "</tr>";
    	}
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
