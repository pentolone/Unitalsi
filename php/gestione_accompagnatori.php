<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Associazione Accompagnatori/Accompagnato</title>
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
 <script type="text/javascript">
  // Funzione per abilitare/disabilitare pulsante di inserimento se nessun accompagnato selezionato
      function toggleButton(bttnID, selectList) {
 //         alert('Ecco');
          var btn = document.getElementById(bttnID);
      	    var inputSelect =  document.getElementById(selectList);
      	    var opts = inputSelect.options;
      	    var count = 0;
 //     	    alert('Ecco1'+btn);

      	    for (var i=0; i < opts.length; i++) {
                 if (opts[i].selected) count++;
                }

      	     if(count == 0) { // Nessun accompagnato selezionato
//      	        alert('KO');
      	        btn.disabled=true;
              }
      	     else { // OK to proceed
   //   	        alert('OK');
      	        btn.disabled=false;
             }
       }
  </script>  
</head>
<body>

<?php
/****************************************************************************************************
*
*  Associa Accompagnatori
*
*  @file gestione_accompagnatori.php
*  @abstract Gestisce l'associazione accompagnatori/accompagnati
*  @author Luca Romano
*  @version 1.0
*  @time 2017-08-16
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-08-16
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
//require_once('../php/disegna_tabella_costi_socio.php');
//require_once('../php/carica_array.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$date_format=ritorna_data_locale();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="accompagnatori";
$redirect="../php/gestione_accompagnatori.php";
$print_target="../php/stampa_viaggio.php";
$titolo='Associazione accompagnatori';
//$titoloSelect='--- Sconosciuto ---';

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
$sqlid_accompagnatore=0;
$sqlid_mezzo=0;
$sqltipo='V';
$sqlcosti=array();
$showDoc=false;
$idDelete=array(0, 0);
$idMezzo=array(0, 0);
$sqlNposti=array(0, 0);

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
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                    AND     pellegrinaggi.id IN(SELECT id_attpell FROM attivita_detail
                                                                              WHERE tipo = 'V')";

// SQL accompagnatori
$sqlselect_accompagnatori = "SELECT anagrafica.id,
                                                               CONCAT(cognome,' ',nome) nome,
                                                               SUBSTRING(DATE_FORMAT(dal,'" . $date_format ."'),1,10) dal,
                                                               SUBSTRING(DATE_FORMAT(al,'" . $date_format ."'),1,10) al
                                                  FROM   anagrafica,
                                                              attivita_detail
                                                  WHERE attivita_detail.id_socio = anagrafica.id
                                                  AND     attivita_detail.status_acc = " . ACCOMPAGNATORE .
                                                " AND    (attivita_detail.id_servizio = 0
                                                  OR        attivita_detail.id_servizio IN(SELECT servizio.id
                                                                                                          FROM   servizio
                                                                                                          WHERE accompagna=1))";

// SQL accompagnati
$sqlselect_accompagnati = "SELECT anagrafica.id,
                                                           CONCAT(cognome,' ',nome) nome,
                                                           SUBSTRING(DATE_FORMAT(dal,'" . $date_format ."'),1,10) dal_display,
                                                           SUBSTRING(DATE_FORMAT(al,'" . $date_format ."'),1,10) al_display,
                                                           attivita_detail.dal,
                                                           attivita_detail.al
                                               FROM   anagrafica,
                                                           attivita_detail
                                               WHERE attivita_detail.id_socio = anagrafica.id
                                               AND     attivita_detail.status_acc = " . ACCOMPAGNATO .
                                             " AND    (attivita_detail.id_servizio = 0
                                               OR        attivita_detail.id_servizio IN(SELECT servizio.id
                                                                                                       FROM   servizio
                                                                                                       WHERE accompagna=0))";

// Seleziona se il socio e' gia' associato
$sqlselect_mezzi_detail = "SELECT id
                                            FROM    mezzi_detail";

if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$conn = DB_connect();

  // Check database connection
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

$titoloSelect ='--- Seleziona il viaggio/pellegrinaggio ---';
$table_name = 'pellegrinaggi'; 
$sqlExec = $sqlselect_pellegrinaggio;
$sqlselectanno_attivita .= $table_name;

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

      		                      case "id_attpell": // viaggio/pellegrinaggio
      					                    $sqlid_attpell = $value;
      					                    break;

      		                      case "id_accompagnatore": // Accompagnatore
      					                    $sqlid_accompagnatore = $value;
      					                    break;

      		                      case "msg": // Messaggio
      					                    $msgAlert = $value;
      					                    break;
                    }
                  }
     }
     
  $sqlselectanno_attivita .= " WHERE anno > 0 AND id_sottosezione = " . $sqlid_sottosezione;     

  if($debug) {
      echo "$fname SQL (anno) = $sqlselectanno_attivita<br>";
   }

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
       echo '</select></p></td>'; 
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

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Seleziona viaggio/Pellegrinaggio</p></td>";
  
  // Seleziona viaggio/pellegrinaggio
  echo "<td><select class='required' name='id_attpell' onChange='this.form.submit();' required>" ;
  echo "<option value=>" . $titoloSelect . "</option>";
  $result = $conn->query($sqlExec);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"];
       	   if($row["id_prn"] == $sqlid_attpell)
       	       echo " selected";
       	   echo ">";
       	   echo htmlentities($row["desa"],$defCharsetFlags, $defCharset) . " &minus;&gt; ". $row["dal"] . " - " . $row["al"] . "</option>";
       	}
  echo '</select></p></td>'; 
  echo "</tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  if($sqlid_attpell > 0) { // OK, attivita' selezionata, carico i dati dei possibili accompagnatori
      $sqlselect_accompagnatori .= " AND attivita_detail.id_attpell = $sqlid_attpell
                                                        ORDER BY 2";                                                                      

      if($debug)
          echo "$fname SQL ACCOMPAGNATORI $sqlselect_accompagnatori<br>"; 

      echo "<form action='" . $redirect . "' method='POST'>";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      
      if($msgAlert) {
      	   echo "<tr><td colspan='2'><p class='alert'>" . htmlentities($msgAlert, $defCharsetFlags, $defCharset) . "</p></td></tr>";
         }
      echo "<tr>";
      echo "<td colspan=2>";

      // Apro tabella dei dati di dettaglio
      echo "<table>";
      echo "<tr>";
      echo "<td><p class='search'><select class='search' name='id_accompagnatore' size=20 onChange='this.form.submit();'>";
      $result = $conn->query($sqlselect_accompagnatori);
      echo "<option value=0 disabled>--- Seleziona accompagnatore ---</option>";
      while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                if($row["id"] == $sqlid_accompagnatore)
                   echo " selected";
                   
                echo ">";
                echo htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " &minus;&gt; " . $row["dal"] . " - " . $row["al"];
                echo "</option>";
              } 	
       
      // End select
      echo "</select>";
      echo "</td>";
      echo "</form>";
    	}

  if($sqlid_accompagnatore > 0) { // OK, accompagnatore selezionato, carico i dati dei possibili "accompagnati"
     $sql = "SELECT dal, al
                  FROM   attivita_detail
                  WHERE id_attpell = $sqlid_attpell
                  AND     attivita_detail.id_socio = $sqlid_accompagnatore" ;
                  
      if($debug)
         echo "$fname SQL (DAL/AL Accompagnatore) = $sql<br>";

     $result = $conn->query($sql);
     $row = $result->fetch_assoc();
     $viaggioStart = $row["dal"];
     $viaggioEnd = $row["al"];
     
     $sqlselect_accompagnati .= " AND attivita_detail.id_attpell = $sqlid_attpell " .
                                                   //  AND attivita_detail.dal BETWEEN '" . $viaggioStart . "'
                                                   //  AND DATE_SUB('" . $viaggioEnd . "', INTERVAL 1 DAY)
                                                     " AND attivita_detail.id_socio != $sqlid_accompagnatore
                                                     AND attivita_detail.id_socio NOT IN(SELECT accompagnatori.id_accompagnato
                                                                                                             FROM  accompagnatori
                                                                                                             WHERE accompagnatori.id_attpell = $sqlid_attpell
                                                                                                             AND     accompagnatori.id_accompagnatore  = $sqlid_accompagnatore)
                                                     ORDER BY 2";
      
      if($debug)
         echo "$fname SQL ACCOMPAGNATI = $sqlselect_accompagnati<br>";
         
      echo "<form id='addAccompagnatore' method='POST' action='../php/insert_accompagnatore.php'>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' name='id_accompagnatore' value=$sqlid_accompagnatore>";
      echo "<input type='hidden' name='data_accD' value='" . $viaggioStart . "'>";
      echo "<input type='hidden' name='data_accA' value='" . $viaggioEnd . "'>";

      echo "<td style='vertical-align: top;'>";
      echo "<select style='min-width: 200px;' class='search' id='accomp' name='id_accompagnato[]' size=20 multiple>";
      echo "<option value=0 disabled>--- Seleziona chi accompagna (Seleziona CTRL o CMD per selezioni multiple) ---</option>";

      $result = $conn->query($sqlselect_accompagnati);     
      while($row = $result->fetch_assoc()) { // Carico i dati dei soci NON accompagnati
                echo "<option value=" . $row["id"] . " onClick=\"toggleButton('assBtn', 'accomp');\">";
                echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " &minus;&gt; " . $row["dal_display"] . " - " . $row["al_display"];              
                echo "</option>";
               }
               
      echo "</select></td>";
      
      if($authMask["insert"]) 
         echo "<td style='vertical-align: top;'>&nbsp;&nbsp;&nbsp;<input id='assBtn' class='in_btn' type='submit' value='Associa &minus;&gt;' disabled></td>";
      else 
         echo "<td>&nbsp;</td>";
      echo "</form>";
                     
      // Elenco soci gia' accompagnati
      $sql = "SELECT accompagnatori.id, CONCAT(cognome,' ', nome) nome,
                               SUBSTRING(DATE_FORMAT(accompagnatori.dal,'" . $date_format ."'),1,10) dal_display,
                               SUBSTRING(DATE_FORMAT(accompagnatori.al,'" . $date_format ."'),1,10) al_display,
                               accompagnatori.dal,
                               accompagnatori.al
                   FROM   anagrafica,
                               accompagnatori,
                               attivita_detail
                   WHERE accompagnatori.id_accompagnatore = $sqlid_accompagnatore
                   AND     accompagnatori.id_accompagnato = anagrafica.id
                   AND     attivita_detail.id_attpell = $sqlid_attpell
                   AND     attivita_detail.id_socio = $sqlid_accompagnatore
                   ORDER BY 2";

      if($debug)
         echo "$fname: SQL (accompagnati) = $sql"; 
               
      echo "<form id='removeAccompagnatore' method='POST' action='../php/insert_accompagnatore.php'>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' name='id_accompagnatore' value=$sqlid_accompagnatore>";
      echo "<input type='hidden' name='data_accD' value='" . $viaggioStart . "'>";
      echo "<input type='hidden' name='data_accA' value='" . $viaggioEnd . "'>";

      echo "<td style='vertical-align: top;'>";
      echo "<select style='min-width: 200px;' class='search' name='idDelete' size=5>";
      echo "<option value=0 disabled>--- Seleziona per rimuovere accompagnato ---</option>";

      $result = $conn->query($sql);     
      while($row = $result->fetch_assoc()) { // Carico i dati dei soci accompagnati
      
                if($authMask["delete"]) {
                    echo "<option value=" . $row["id"] . " onClick=\"{if (conferma('". ritorna_js("Rimuovo " . $row["nome"] . " ?") ."')) this.form.submit();}\">";
                    echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " &minus;&gt; " . $row["dal_display"] . " - " . $row["al_display"];
                    echo  "</option>";
                }
                else {
                    echo "<option value=" . $row["id"] . ">";
                    echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset) . " &minus;&gt; " . $row["dal_display"] . " - " . $row["al_display"];
                    echo  "</option>";
                
                }
          }
               
      echo "</select></td>";
      echo "</tr>";
      echo "</table>";
      echo "</td>";
      echo "</tr>";
      echo "</form>";
      
  //    echo "<form id='AddAccompagnato' method='POST' action='../php/delete_sql.php'>";
 // echo "</form>";
     }
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
