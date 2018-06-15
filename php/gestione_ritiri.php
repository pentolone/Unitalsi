<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione ritiri</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
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
*  Gestione dei ritiri (visualizza/elimina)
*
*  @file gestione_ritiri.php
*  @abstract Gestisce la visualizzazione/cancellazione dei ritiri
*  @author Luca Romano
*  @version 1.0
*  @time 2017-10-10
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-10-10
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$fname=basename(__FILE__);
$date_format=ritorna_data_locale();

$index=0;
$update=false;
$msgAlert=null;
$nrec=20; // numero di record da visualizzare
$filterRit='%'; // filtro per nome del ritirato
$table_name="ritiri_detail";
$redirect="../php/gestione_ritiri.php";

// Variabili da tabella
$sqlID=0;
$sqlanno=date('Y');
$sqlanno_min=null;
$sqlanno_selected=$sqlanno;

$sqln_ricevuta=0;
$sqlcodice;
$sqlnome;
$sqldescrizione=null;
$sqldal=null;
$sqlal=null;
$sqln_lettera=null;
$sqlmittente=null;
$sqldestinatario=null;
$sqloggetto=null;
$sqlconsegnato=null;
$sqlreferente=null;
$sqlnote=null;
$sqldata;
$sqlutente;

$sqlid_sottosezione=$sott_app;

$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselectanno_ritiri      = "SELECT MIN(anno) amin
                                             FROM   ritiri_detail WHERE id_sottosezione=?";                                                          

$sqlselect_ritiri = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                            ritiri_detail.id,
                                            attivita.descrizione,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.dal,'" . $date_format . "'),1,10) dal,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.al,'" . $date_format . "'),1,10) al,
                                            ritiri_detail.utente,
                                            DATE_FORMAT(ritiri_detail.data, '" . $date_format . "') data
                               FROM    anagrafica,
                                             ritiri_detail,
                                             attivita_m,
                                             attivita
                               WHERE  anagrafica.id = ritiri_detail.id_socio
                               AND      ritiri_detail.id_sottosezione =?
                               AND      ritiri_detail.anno = ?
                               AND      ritiri_detail.tipo = 'A'
                               AND      ritiri_detail.id_attpell = attivita_m.id
                               AND      attivita_m.id_attivita = attivita.id
                               AND      lower(cognome) like lower(?)
                               UNION
                               SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                            ritiri_detail.id,
                                            descrizione_pellegrinaggio.descrizione,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.dal,'" . $date_format . "'),1,10) dal,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.al,'" . $date_format . "'),1,10) al,
                                            ritiri_detail.utente,
                                            DATE_FORMAT(ritiri_detail.data, '" . $date_format . "') data
                               FROM    anagrafica,
                                             ritiri_detail,
                                             pellegrinaggi,
                                             descrizione_pellegrinaggio
                               WHERE  anagrafica.id = ritiri_detail.id_socio
                               AND      ritiri_detail.id_sottosezione =?
                               AND      ritiri_detail.anno = ?
                               AND      ritiri_detail.tipo = 'V'
                               AND      ritiri_detail.id_attpell = pellegrinaggi.id
                               AND      pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                               AND      lower(cognome) like lower(?)
                               ORDER BY 1 LIMIT 0,?";

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

  if ($_POST) { // se post allora modificato parametri di input
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                     switch($key) {
      		                     case "id": // ID riga
      					                    $sqlID = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                     case "anno": // anno di competenza
      					                    $sqlanno_selected = $value;
      					                    break;

      		                     case "nrec": // numero righe da visualizzare
      					                    $nrec = $value;
      					                    break;

      		                     case "filterRit": // Filtro per nome utente
      					                    $filterRit = $value . '%';
      					                    break;
                    }
             $index++;
           } // End foreach
           
           if($sqlID > 0) { // Aggiornamento riga, carico i dati
               $update=true;
               $sql  =  "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                            ritiri_detail.id,
                                            attivita.descrizione,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.dal,'" . $date_format . "'),1,10) dal,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.al,'" . $date_format . "'),1,10) al,
                                            ritiri_detail.utente,
                                            DATE_FORMAT(ritiri_detail.data, '" . $date_format . "') data
                               FROM    anagrafica,
                                             ritiri_detail,
                                             attivita_m,
                                             attivita
                               WHERE  anagrafica.id = ritiri_detail.id_socio
                               AND      ritiri_detail.id_sottosezione = $sqlid_sottosezione
                               AND      ritiri_detail.anno = $sqlanno_selected
                               AND      ritiri_detail.tipo = 'A'
                               AND      ritiri_detail.id_attpell = attivita_m.id
                               AND      attivita_m.id_attivita = attivita.id
                               AND      ritiri_detail.id = $sqlID
                               UNION
                               SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                            ritiri_detail.id,
                                            descrizione_pellegrinaggio.descrizione,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.dal,'" . $date_format . "'),1,10) dal,
                                            SUBSTR(DATE_FORMAT(ritiri_detail.al,'" . $date_format . "'),1,10) al,
                                            ritiri_detail.utente,
                                            DATE_FORMAT(ritiri_detail.data, '" . $date_format . "') data
                               FROM    anagrafica,
                                             ritiri_detail,
                                             pellegrinaggi,
                                             descrizione_pellegrinaggio
                               WHERE  anagrafica.id = ritiri_detail.id_socio
                               AND      ritiri_detail.id_sottosezione = $sqlid_sottosezione
                               AND      ritiri_detail.anno = $sqlanno_selected
                               AND      ritiri_detail.tipo = 'V'
                               AND      ritiri_detail.id_attpell = pellegrinaggi.id
                               AND      pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                               AND      ritiri_detail.id = $sqlID";
               if($debug)
                  echo "$fname: SQL = $sql<br>";

               $result = $conn->query($sql);

               $row = $result->fetch_assoc();
               $sqlnome=$row["nome"];
               $sqldescrizione=$row["descrizione"];
               $sqldal=$row["dal"];
               $sqlal=$row["al"];
               $sqldata=$row["data"];
               $sqlutente=$row["utente"];
              }
     }
 
 $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione);
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px; overflow-x:auto;'>";

// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body>";
     echo "</html>";
     return;
     }
     
   if($sqlID == -1) { // Richiesta cancellazione di tutte le righe
       $sql = "DELETE FROM ritiri_detail
                    WHERE  id_sottosezione = $sqlid_sottosezione
                    AND      anno = $sqlanno_selected";
                    
       if($debug)
           echo "$fname: SQL DELETE ALL $sql<br>";
       $conn->query($sql);
       $msgAlert = "Eliminate " . $conn->affected_rows . " righe";
       $sqlID = 0;
      }

  $stmt = $conn->prepare($sqlselectanno_ritiri); // Minimo anno dei ritiri
  
  $stmt->bind_param("i", $sqlid_sottosezione);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlanno_min);
  $stmt->fetch();
  $stmt->close();
 
   if(!$sqlanno_min)
      $sqlanno_min = date('Y');
 
  $conn->prepare($sqlselect_ritiri);
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='nrec' value=$nrec>";
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Ritiri</td>";
  echo "</tr>";
  
  if($msgAlert) {
      echo "<tr>";
      echo "<td colspan='2'><p class='alert'>" . $msgAlert . "</p></td>";
      echo "</tr>";
     }

// Sottosezione
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
 
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' required onChange='this.form.submit();'>";
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

// Anno di riferimento
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

// Se aggiorno visualizzo i dati per la cancellazione

if($update) {

    echo "<tr>";
    echo "<td><p class='required'>Nominativo</p></td>";
    echo "<td><p class='required'>" . htmlentities($sqlnome,$defCharsetFlags, $defCharset) . "</p></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><p class='required'>Descrizione</p></td>";
    echo "<td><p class='required'>"  . htmlentities($sqldescrizione,$defCharsetFlags, $defCharset) . "</p></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><p class='required'>Dal &minus; Al</p></td>";
    echo "<td><p class='required'>"  . $sqldal . "&nbsp;&minus;&nbsp;" . $sqlal . "</p></td>";
  
    echo "<tr>";
    echo "<td colspan='2'><hr></td>";
    echo "</tr>";
  
    echo "<tr>";
    echo "<td class='tb_upd' colspan='2'>(Ultimo aggiornamento " . $sqldata . " Utente " . $sqlutente.")</td>";
    echo "</tr>";


     if($authMask["delete"]) {
         echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
         echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
         echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
         echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
         echo "<tr>";
         echo "<td colspan=2 style='text-align: center;'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello il ritiro di " . $sqlnome . "?") ."');}\"></td>";
   	      echo "</tr>";
         echo "</form>";
   	     } 
   
	  }
  else { // Se abilitato propongo la cancellazione di tutti i ritiri (se abilitato alla cancellazione)
     if($authMask["delete"]) {
         echo "<form action='" . $redirect . "' method=\"post\">";
         echo "<input type=\"hidden\" name=\"id\" value=-1>";
         echo "<input type=\"hidden\" name=\"anno\" value=$sqlanno_selected>";
         echo "<input type=\"hidden\" name=\"id_sottosezione\" value=\"" . $sqlid_sottosezione . "\">";
         echo "<tr>";
         echo "<td colspan=2 style='text-align: center;'><input class='md_btn' type='submit' value='Elimina TUTTI' onclick=\"{return conferma('". ritorna_js("Cancello TUTTI i ritirati nell'anno selezionato ?") ."');}\"></td>";
   	      echo "</tr>";
         echo "</form>";
        }
     }

  echo "</table>";
  
  // Elenco ultimi 'n' ritiri
  
  if($debug) {
  	  echo "$fname SQL (prepare) $sqlselect_ritiri<br>";
  	  echo "$fname bind param Sottosezione = $sqlid_sottosezione<br>";
  	  echo "$fname bind param Anno = $sqlanno_selected<br>";
  	  echo "$fname bind param Filter = $filterRit<br>";
  	  echo "$fname bind param N. Rec = $nrec<br>";
  	  }
  $index=0;
  $stmt = $conn->prepare($sqlselect_ritiri);
  $stmt->bind_param("iisiisi", $sqlid_sottosezione, $sqlanno_selected, $filterRit,
                                    $sqlid_sottosezione, $sqlanno_selected, $filterRit, $nrec);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlnome,
                                  $sqlid_ritiro,
                                  $sqldescrizione,
                                  $sqldal,
                                  $sqlal,
                                  $sqlutente,
                                  $datainsert);

  while($stmt->fetch()) {
  	         if($index==0) { // Intestazione
  	             echo "<br>";
  	             echo "<form action='" . $redirect . "' method='POST'>";
  	             echo "<input type='hidden' name='id' value=$sqlID>";
  	             echo "<table style='background-color: white;opacity: 0.8;'>";
  	             echo "<tr>";
  	             echo "<th colspan='12' style='font: bold 14px Arial, Helvetica, sans-serif;'>Visualizzo ultime ";
  	             echo "<select class='search' name='nrec' onChange='this.form.submit();'>";
  	             echo "<option value=20";
  	             
  	             if(20 == $nrec)
  	                echo " selected";
  	             echo ">20</option>";

  	             echo "<option value=40";
  	             
  	             if(40 == $nrec)
  	                echo " selected";
  	             echo ">40</option>";

  	             echo "<option value=60";
  	             
  	             if(60 == $nrec)
  	                echo " selected";
  	             echo ">60</option>";

  	             echo "<option value=80";
  	             
  	             if(80 == $nrec)
  	                echo " selected";
  	             echo ">80</option>";

  	             echo "<option value=100";
  	             
  	             if(100 == $nrec)
  	                echo " selected";
  	             echo ">100</option>";

  	             echo "<option value=120";
  	             
  	             if(120 == $nrec)
  	                echo " selected";
  	             echo ">120</option>";

  	             echo "<option value=140";
  	             
  	             if(140 == $nrec)
  	                echo " selected";
  	             echo ">140</option>";

  	             echo "<option value=200";
  	             
  	             if(200 == $nrec)
  	                echo " selected";
  	             echo ">200</option>";

  	             echo "</select> righe";
  	             echo "; oppure filtra per nominativo del ritirato";
  	             echo "&nbsp;<input class='search' name='filterRit' maxlength=60 size=50>";
  	             echo "<input type='submit' value='Cerca'>";
  	             echo "</th>";
  	             echo "</form>";
  	             echo "</tr>";
  	             
  	             echo "<tr>";	             
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Intestatario</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Descrizione</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Dal</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Al</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Aggiornato da</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data/ora</th>";
  	             echo "</tr>";
  	         }
  	         $index++;
  	         
  	         echo "<form id='updRitiro_$index' action='" . $redirect . "' method='POST'>";
  	         echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  	         echo "<input type='hidden' name='anno' value=$sqlanno_selected>";
  	         echo "<input type='hidden' name='id' value=" . $sqlid_ritiro. ">";
            echo "<input type='hidden' name='nrec' value=$nrec>";
  	         echo "<tr>";
  	         echo "<td style='font: bold italic 11px Arial, Helvetica, sans-serif;vertical-align: top; white-space: nowrap;'>";
  	         echo "<a href='#' onClick=\"document.getElementById('updRitiro_$index').submit();\">";
  	         echo htmlentities($sqlnome, $defCharsetFlags, $defCharset);
  	         echo "</a></td>";
  	         echo "</form>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif;text-align: left;vertical-align: top;'>";
  	         echo htmlentities($sqldescrizione, $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: center;vertical-align: top;'>";
  	         echo $sqldal;

  	         echo "</td>";
  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: center;vertical-align: top;'>";
  	         echo $sqlal;
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($sqlutente, $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: center;vertical-align: top;'>";
  	         echo $datainsert;
  	         echo "</td>";
  	         echo "</tr>";

       	}
       	
  if($index > 0)
     echo "</table>";
  else 
     echo "<h1>Nessun dato trovato in tabella</h1>";
  echo "</div>";

$conn->close();

?>
</body>
</html>
