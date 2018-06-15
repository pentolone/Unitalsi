<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Assegnazione camere struttura</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
  <script type="text/javascript" src="../js/setHiddenCosts.js"></script>
  <script type="text/javascript">
  // Funzione per abilitare/disabilitare pulsante di aggiunta voci
      function toggleButton(ref, bttnID) {
      //	alert('Ecco');
      	     var inputField =  document.getElementById(ref);
           var btn = document.getElementById(bttnID);

      	     if(inputField.value.trim() == '') { // Empty string
      	        btn.disabled=true;
              }
      	     else { // OK to proceed
      	        btn.disabled=false;
              }
       }
  </script>

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
</head>
<body>

<?php
/****************************************************************************************************
*
*  Gestione delle prenotazioni delle strutture a disposizione
*
*  @file gestione_prenotazione.php
*  @abstract Gestisce le prenotazioni della struttura
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-18
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-18
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
define('EURO',chr(128));
setlocale(LC_MONETARY, 'it_IT');
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqlanno_selected=$sqlanno;
$sqlid_sottosezione=$sott_app;
$sqlid_old=0;
$sqlid_attpell=0;
$sqlid_struttura=0;
$sqlid_socio=0;
$sqlid_camera=0;
$sqlViaggioStart=null; // Inizio viaggio
$sqlViaggioEnd=null; // Fine viaggio

$sqlUserStart=null; // Inizio viaggio socio
$sqlUserEnd=null; // Fine viaggio socio

$sqlSocioIn=null; // Arrivo socio
$sqlSocioOut=null; // Partenza socio

$index=0;
$indexA=0;

$table_name="AL_occupazione";
$redirect="../php/gestione_prenotazione.php";

$msgAlert=null;
$sqlemail=null;

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }

$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_struttura = "SELECT id, nome
                                       FROM  AL_struttura";

$sqlselectanno_attivita = "SELECT MIN(anno) amin
                                            FROM ";                                                          

// SQL pellegrinaggi
$sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa,
                                                pellegrinaggi.al al_ot
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
$titolo = 'Assegnazione camere';
$tagForm="Seleziona viaggio/Pellegrinaggio";
$titoloSelect ='--- Seleziona il viaggio/pellegrinaggio ---';
$sqlExec = $sqlselect_pellegrinaggio;
$sqlselectanno_attivita .= 'pellegrinaggi';

  if ($_POST) { // se post allora ho modificato i valori di selezione

      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
           if($debug) {
                echo $fname . ": KEY = " . $key . '<br>';
                echo $fname . ": VALUE = " . $value . '<br>';
                echo $fname . ": INDEX = " . $index . '<br><br>';                    	
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

      		                      case "id_attpell": // attivita/pellegrinaggio
      					                    $sqlid_attpell = $value;
      					                    break;

      		                      case "id_struttura": // struttura
      					                    $sqlid_struttura = $value;
      					                    break;

      		                      case "id_camera": // ID camera
      					                    $sqlid_camera = $value;
      					                    break;

      		                      case "id_socio": // Socio
      					                    $sqlid_socio = $value;
      					                    break;

      		                      case "email": // E-mail Socio
      					                    $sqlemail = $value;
      					                    break;

      		                      case "msg": // Messaggio
      					                    $msgAlert = $value;
      					                    break;
                    }
                  }
     }
  $sqlselectanno_attivita .= " WHERE anno > 0 AND id_sottosezione = " . $sqlid_sottosezione;     
  $sqlselect_struttura .= " WHERE id_sottosezione = " . $sqlid_sottosezione . " ORDER BY 2";     

  if($debug)
      echo "$fname SQL = $sqlselectanno_attivita<br>";

  $result = $conn->query($sqlselectanno_attivita);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlannostart=date('Y');
    }

  $sqlExec .= " AND pellegrinaggi.anno = " . $sqlanno;
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  if($sqlid_sottosezione > 0)  
     $sqlExec .= " AND pellegrinaggi.id_sottosezione = " . $sqlid_sottosezione;
    
  $sqlExec .= "  ORDER BY 3 DESC, 4";

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
       
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>" . $titolo . "</td>";
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
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<tr>";
  echo "<td><p class='required'>" . $tagForm . "</p></td>";
  echo "<td><select class='required' name='id_attpell' onChange='this.form.submit();'>" ;
  echo "<option value=0>" . $titoloSelect . "</option>";
  $result = $conn->query($sqlExec);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"];
       	   if($row["id_prn"] == $sqlid_attpell) {
       	       echo " selected";
       	       $sqlViaggioStart = $row["dal_order"];
       	       $sqlViaggioEnd = $row["al_ot"];
       	    }
       	   echo ">";
       	   echo htmlentities($row["desa"],$defCharsetFlags, $defCharset) . " &minus;&gt; (". $row["dal"] . " - " . $row["al"] . ")</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  echo "</form>";

  if($sqlid_attpell > 0) { // OK, attivita' selezionata, carico i dati struttura
      echo "<form id='selectStruttura' action='" . $redirect . "' method='POST'>";
      echo "<input type='hidden' name='id_socio' value=" . $sqlid_socio . ">"; 
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";

      if($debug)
          echo "$fname SQL $sqlselect_struttura<br>";

      $rc = $conn->query($sqlselect_struttura);
// Verifico se esiste SOLO una struttura disponibile      
      if($rc->num_rows == 1) { // Solo una struttura
          $row = $rc->fetch_assoc();
           echo "<input type='hidden' name='id_struttura' value=" . $row["id"] . ">";
           
           if($sqlid_struttura == 0) {
              echo "</form>";
              echo "<script>document.getElementById('selectStruttura').submit();</script>";
             }
         }
      $result = $conn->query($sqlselect_struttura);

      echo "<tr>";
      echo "<td><p class='required'>Seleziona struttura</p></td>";
      echo "<td><select class='required' name='id_struttura' onChange='this.form.submit();' required>";
      echo "<option value=>--- Seleziona la struttura ---</option>";
      while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                if($row["id"] == $sqlid_struttura)
                    echo " selected";
                echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
      // End select
      echo "</select>";
      echo "</td></tr>";
      echo "</form>";
    	}

  if($sqlid_struttura > 0) { // OK, struttura selezionata, carico i dati delle camere associate alla struttura
      $sql = "SELECT AL_camere.id, AL_camere.codice, 
                                AL_camere.n_posti, 
                                AL_piani.descrizione
                   FROM   AL_piani,
                                AL_camere
                   WHERE  id_struttura = $sqlid_struttura
                   AND      AL_camere.id_piano = AL_piani.id
                   AND      AL_camere.n_posti > 0
                   ORDER BY 2";

      echo "<form id='selectRoom'' action='" . $redirect . "' method='POST'>";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      echo "<input type='hidden' name='id_struttura' value=" . $sqlid_struttura . ">";

      echo "<tr>";
      echo "<td><p class='required'>Seleziona camera</p></td>";
      echo "<td>";
      echo "<select class='required' name='id_camera' required onChange='this.form.submit();'>";
      echo "<option value=0>--- Seleziona la camera ---</option>";
      if($debug)
          echo "$fname: SQL $sql<br>";
      $result = $conn->query($sql);
      while($row = $result->fetch_assoc()) {
                 echo "<option value=" . $row["id"];
                 
                 if($row["id"] == $sqlid_camera)
                     echo " selected";
                 echo ">" . htmlentities($row["codice"] . "-" . $row["descrizione"], $defCharsetFlags, $defCharset) . " #" . $row["n_posti"] . "</option>";
                } 	
       
       // End select
       echo "</select>";
       echo "</td></tr>";
       echo "</form>";
      } 	

      
  if($msgAlert) {
      	echo "<tr><td colspan='2'><p class='alert'>" . htmlentities($msgAlert, $defCharsetFlags, $defCharset) . "</p></td></tr>";
     }

  if($sqlid_camera > 0) { // OK, camera selezionata, carico dati soci NON associati e visualizzo quelli in camera

//------------------------------------------------------------------------
// Soci da assegnare      
     $sqlselect_soci = "SELECT anagrafica.id,  CONCAT(cognome,' ',nome) nome, data_exp, email,
                                    IFNULL(CONCAT('<strong>', disabilita.descrizione, '</strong>'),' ') desd,
                                    SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) sDal,
                                    SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) sAl
                                    FROM   anagrafica
                                    LEFT JOIN disabilita
                                    ON anagrafica.id_disabilita = disabilita.id,
                                          attivita_detail
                                    WHERE anagrafica.id = attivita_detail.id_socio
                                    AND     attivita_detail.tipo ='V'
                                    AND     attivita_detail.id_attpell =  $sqlid_attpell
                                    AND     anagrafica.id NOT IN(SELECT id_socio
                                                                                  FROM  AL_occupazione
                                                                                  WHERE id_attpell = $sqlid_attpell)
                                    ORDER BY 2";

     if($debug)
         echo "$fname: SQL (non associati)= $sqlselect_soci<br>";
      echo "<tr>";
      echo "<td colspan='2'><hr></td>";
      echo "</tr>";

      echo "<form name='aggPren' action='../php/insert_prenotazione.php'  method='POST'>";
      echo "<input type='hidden' name='id_camera' value=" . $sqlid_camera . ">"; 
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      echo "<input type='hidden' name='id_struttura' value=" . $sqlid_struttura . ">";

      echo "<tr>";
      echo "<td><p class='required'>Seleziona socio</p></td>";
      echo "<td><select class='required' id='searchTxt' name='id_socio' required>";
      echo "<option value=>--- Seleziona il socio ---</option>";

//      if($debug)
          echo "$fname SQL Soci $sqlselect_soci<br>";
      $result = $conn->query($sqlselect_soci);

      while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) .
                        " &minus;&gt; (" . $row["sDal"] . " - " . $row["sAl"]. ")</option>";
              } 	
       
      // End select
      echo "</select>";
      echo "</td></tr>";

/****** Prendo da attivita socio
      echo "<tr>";
      echo "<td><p class='search'>Seleziona periodo</td></p></td>";
      echo "<td><p class='required'>";
      echo "<input class='required' type='date' name='dal' min='" . $sqlViaggioStart . "' value='" . $sqlViaggioStart . "' max='" . $sqlViaggioEnd . "' required>";
      echo "&nbsp;";
      echo "<input class='required' type='date' name='al' min='" . $sqlViaggioStart . "' max='" . $sqlViaggioEnd . "' value='" . $sqlViaggioEnd . "' required>";
      echo "&nbsp;</p></td>";
      echo "</tr>";
****************/
// Forzatura camera occupata

      echo "<tr>";
      echo "<td><p class='search'>Camera completa</td></p></td>";
      echo "<td><input type='checkbox' name='full' value=1></td>";
      echo "</tr>";

      if($authMask["insert"]) {
         echo "<tr>";
         echo "<td colspan='2' class='button'><input class='in_Btn' type='submit' value='Assegna'></td>";
         echo "</tr>";
        }

      echo "<tr>";
      echo "<td colspan='2'><hr></td>";
      echo "</tr>";
      echo "</form>";

//-------------------------------------------------------------------------
// Soci in camera      
      $sqlselect_soci = "SELECT AL_occupazione.id,  CONCAT(cognome,' ',nome) nome,
                                    IFNULL(CONCAT('<strong>', disabilita.descrizione, '</strong>'),' ') desd,
                                    SUBSTRING(DATE_FORMAT(AL_occupazione.dal,'" . $date_format ."'),1,10) dal,
                                    SUBSTRING(DATE_FORMAT(AL_occupazione.al,'" . $date_format ."'),1,10) al,
                                    AL_occupazione.full
                                    FROM   anagrafica
                                    LEFT JOIN disabilita
                                    ON anagrafica.id_disabilita = disabilita.id,
                                          AL_occupazione
                                    WHERE anagrafica.id = AL_occupazione.id_socio
                                    AND     AL_occupazione.id_camera = $sqlid_camera
                                    AND     AL_occupazione.id_attpell =  $sqlid_attpell
                                    ORDER BY 2";

      echo "<form id='removeSocio' action='../php/delete_sql.php' method='POST'>";
      echo "<input type='hidden' name='redirect' value=" . $redirect . ">"; 
      echo "<input type='hidden' name='table_namee' value='" . $table_name . "'>";

      echo "<tr>";
      echo "<td style='vertical-align: top;'><p class='search'>Elenco soci in camera (seleziona per rimuovere)</p></td>";
      echo "<td><select style='min-width: 200px;' class='search' name='id_socio' size=5>";
     if($debug)
         echo "$fname: SQL (in camera)= $sqlselect_soci<br>";
      $result = $conn->query($sqlselect_soci);

      $index=0;     
      while($row = $result->fetch_assoc()) {
      	          $index++;
      	          
      	          if($authMask["delete"]) {
                    echo "<option value=" . $row["id"] . 
                             " onClick=\"{if (conferma('". ritorna_js("Rimuovo " .$row["nome"] . " dalla camera selezionata?") ."')) document.getElementById('removeSocio').submit();}\">";
                    echo htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " (" . $row["dal"] . " - " . $row["al"] . ")</option>";
                   }
                else {
                    echo "<option value=" . $row["id"] . ">";
                    echo htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " (" . $row["dal"] . " - " . $row["al"] . ")</option>";
                   }
              }
              
      if($index == 0) // Camera vuota
          echo "<option disabled>--- Nessun partecipante presente in camera ---</option>";
      echo "</select></td>";
      echo "</tr>";

    	}

  echo "</table>";
  echo "</div>";

$conn->close();

?>
</body>
</html>