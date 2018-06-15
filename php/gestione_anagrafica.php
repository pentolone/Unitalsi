<?php
require_once('../php/unitalsi_include_common.php');
require_once('../php/ricerca_socio.php');
require_once('../php/ricerca_comune.php');
require_once('../php/ritorna_tessera_rinnovata.php');

if(!check_key())
   return;
?>
<html>
<head>
  <title>Anagrafica socio</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="UTF-8">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/calcola_codice_fiscale.js"></script>
  
  <script type="text/javascript" >

// Seleziona la visualizzazione dei dati selezionati
  function setActiveDiv(i) {
   //alert("HERE");
    var arrayDiv = document.getElementsByName("divana");
 //alert(arrayMese.length);
 //alert(i);
  // alert(getElementById("divana00").style.display);
  arrayDiv[i].style.display = "block";
 // alert(arrayDiv[i].style.display);
  
  for(ix=0; ix < arrayDiv.length; ix++) {
// alert("Ciclo");
       if(ix != i) {
       	//alert(ix);
          arrayDiv[ix].style.display = "none";
         }
         //alert(arrayMese[ix].style.display);
  
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
*  Gestione dell'anagrafica principale
*
*  @file gestione_anagrafica.php
*  @abstract Gestisce l'anagrafica degi soci
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-26
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-01-26
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__); 
$ctrAuth=0;
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$update = false;
$searchS=false;
$searchC=false;
$table_name="anagrafica";
$redirect="../php/gestione_anagrafica.php";
$sqlID=0;

$kv = array(); // array per campi in input da POST

/*---------------
	Variabili DB
-----------------*/
$sqlcognome = "";
$sqlnome="";
$sqlsesso="";
$sqlindirizzo="";
$sqlid_comune; // Codice comune di residenza
$sqlcitta=null; 
$sqlcitta_display=''; // Città presa da tabella comuni
$sqlcap=null;
$sqlid_provincia=0;	// Link tabella province
$sqlluogo_nascita="";
$sqlcodice_catastale=''; // Codice catastale comune di nascita
$sqldata_nascita="";
$sqlcf="";
$sqlts="";
$sqltelefono="";
$sqlcellulare="";
$sqltelefono_rif="";
$sqlemail="";
$sqlid_tipo_doc=0;		// Link tabella tipo doc
$sqln_doc="";
$sqldata_ril="";
$sqldata_exp="";
$sqlid_luogo_rilascio=0;
$sqlluogo_rilascio_display=null; // Display comune rilascio documento
$sqlid_stato_civile=0;	// Link tabella stato civile
$sqlid_professione=0; // Link tabella professione
$sqlid_titolo_studio=0; // Link tabella titolo_studio
$sqln_biancheria="";
$sqlpensionato=false;
$sqldeceduto=false;
$sqldata_decesso="";
$sqlsospeso=false;
$sqlsocio_effettivo=false;
$sqleffettivo_dal="";
$sqln_tessera="";
$sqln_socio="";
$sqln_tessera_unitalsi="";
$sqlid_disabilita=0;
$sqlid_cittadinanza=1;
$sqlid_stato_nascita=1;
$sqlid_sezione=0;
$sqlid_sottosezione=$sott_app;
$sqlid_gruppo_par=0;
$sqlid_categoria=0;
$sqlid_tipo_personale=0;
$sqlid_classificazione=0;
$sqlid_classificazione1=0;
$sqltimestamp="";
$sqlutente="";

$rinnovata=null; // Testo per rinnovo tessera

/*---------------
	Fine variabili DB
-----------------*/

$idSoc = ritorna_societa_id();

/*------------------------------
	Selezione dati da tabelle correlate
--------------------------------*/
  $sqlselect_socio = "SELECT 0, id,  CONCAT(cognome,' ',nome) nome
                                  FROM   anagrafica
                                  WHERE id_sottosezione = " . $sott_app;

  if($multisottosezione) {
     $sqlselect_socio .= " UNION SELECT 1, anagrafica.id,
                                       CONCAT(anagrafica.cognome,' ',anagrafica.nome,' (Sottosezione di ' , sottosezione.nome,')') nome
                                       FROM anagrafica,
                                                 sottosezione
                                       WHERE anagrafica.id_sottosezione = sottosezione.id
                                       AND id_sottosezione != " . $sott_app; 
   }
  $sqlselect_socio .= " ORDER BY 1,3";

  $sqlselect_sesso = "SELECT '\"\"' id, '--- Seleziona sesso ---' d
                                 FROM   DUAL
                                 UNION
                                 SELECT 'M' id, 'Maschio' d
                                 UNION
                                 SELECT 'F' id, 'Femmina' d
                                 ORDER BY 1";

$sqlselect_comune = "SELECT comuni.id, CONCAT(comuni.nome, ' (', province.sigla,')') nome,
                                                  comuni.cap, comuni.codice_catastale, comuni.id_provincia
                                     FROM   comuni,
                                                 province
                                     WHERE comuni.id_provincia = province.id 
                                     ORDER BY 2";

$sqlselect_provincia = "SELECT 0, '\"\"' id, '--- Seleziona provincia ---' nome
                                       FROM   DUAL
                                       UNION
                                       SELECT 1, id, nome
                                       FROM    province
                                       ORDER BY 1,3";

$sqlselect_professione = "SELECT 0, '\"\"' id, '--- Seleziona professione ---' descrizione
                                           FROM   DUAL
                                           UNION
                                           SELECT 1, id, descrizione
                                           FROM    professione
                                           ORDER BY 1,3";

$sqlselect_titolo_studio = "SELECT 0, '\"\"' id, '--- Seleziona titolo studio ---' descrizione
                                            FROM   DUAL
                                            UNION
                                            SELECT 1, id, descrizione
                                            FROM    titolo_studio
                                            ORDER BY 1,3";

$sqlselecttipo_doc = "SELECT 0, '\"\"' id, '--- Seleziona tipo documento ---' descrizione
                                    FROM   DUAL
                                    UNION
                                    SELECT 1, id, descrizione
                                    FROM    tipo_documento
                                    ORDER BY 1,3";

$sqlselectstato_civile = "SELECT 0, 0 id, '--- Seleziona stato civile ---' descrizione
                                    FROM   DUAL
                                    UNION
                                    SELECT 1, id, descrizione
                                    FROM    stato_civile
                                    ORDER BY 1,3";

$sqlselectsezione = "SELECT 0, '\"\"' id, '--- Seleziona la sezione ---' nome
                                    FROM   DUAL
                                    UNION
                                    SELECT 1, id, nome
                                    FROM    societa
                                    ORDER BY 1,3";

$sqlselectsottosezione = "SELECT 0, '\"\"' id, '--- Seleziona la sottosezione ---' nome
                                           FROM   DUAL
                                           UNION
                                           SELECT 1, sottosezione.id, sottosezione.nome nome
                                           FROM    sottosezione, societa
                                           WHERE  sottosezione.id_sezione = " . $idSoc .
                                          " AND societa.id = sottosezione.id_sezione
                                           ORDER BY 1,3";

$sqlselectgruppo_par = "SELECT 0, '\"\"' id, '--- Seleziona il gruppo ---' descrizione,0
                                         FROM   DUAL
                                         UNION
                                         SELECT 1, id, descrizione, id_ori
                                         FROM    gruppo_parrocchiale
                                         WHERE  id_sottosezione = " . $sott_app; 

  if($multisottosezione) {
      $sqlselectgruppo_par .= " UNION
                                                 SELECT 2, gruppo_parrocchiale.id,
                                                              CONCAT(descrizione,' (Sottosezione di ' , sottosezione.nome,')') descrizione,
                                                              gruppo_parrocchiale.id_ori
                                                 FROM    gruppo_parrocchiale,
                                                              sottosezione
                                                 WHERE  gruppo_parrocchiale.id_sottosezione = sottosezione.id
                                                 AND      id_sottosezione != " . $sott_app; 
   }

  $sqlselectgruppo_par .= " ORDER BY 1,3";

$sqlselectcategoria = "SELECT 0, '\"\"' id, '--- Seleziona la categoria ---' descrizione
                                     FROM   DUAL
                                     UNION
                                     SELECT 1, id, descrizione
                                     FROM    categoria
                                     ORDER BY 1,3";

$sqlselecttipo_personale = "SELECT 0, '\"\"' id, '--- Seleziona la tipologia personale ---' descrizione
                                     FROM   DUAL
                                     UNION
                                     SELECT 1, id, descrizione
                                     FROM    tipo_personale
                                     ORDER BY 1,3";

$sqlselectclassificazione = "SELECT 0, 0 id, '--- Seleziona la classificazione ---' descrizione
                                     FROM   DUAL
                                     UNION
                                     SELECT 1, id, descrizione
                                     FROM    classificazione
                                     ORDER BY 1,3";

$sqlselectdisabilita = "SELECT 0, 0 id, '--- Seleziona eventuale disabilita ---' descrizione
                                     FROM   DUAL
                                     UNION
                                     SELECT 1, id, descrizione
                                     FROM    disabilita
                                     ORDER BY 1,3";

$sqlselectnazioni = "SELECT 0, id, nazione_PS
                                  FROM   PS_nazioni
                                  WHERE id = 1
                                  UNION
                                  SELECT 1, id, nazione_PS
                                  FROM    PS_nazioni
                                  WHERE  id > 1
                                  ORDER BY 1,3";
/*------------------------------
	Fine selezione dati da tabelle correlate
--------------------------------*/

/*------------------------------
	Selezione dati storici
--------------------------------*/

$sql_storico = "SELECT attivita_m.anno,
                                      attivita.descrizione
                          FROM   attivita,
                                      attivita_m,
                                      attivita_detail
                          WHERE attivita_detail.id_socio = ?
                          AND     attivita_detail.tipo = 'A'
                          AND     attivita_detail.id_attpell = attivita_m.id
                          AND     attivita_m.id_attivita = attivita.id
                          UNION
                          SELECT pellegrinaggi.anno,
                                       descrizione_pellegrinaggio.descrizione
                          FROM   descrizione_pellegrinaggio,
                                      pellegrinaggi,
                                      attivita_detail
                          WHERE attivita_detail.id_socio = ?
                          AND     attivita_detail.tipo = 'V'
                          AND     attivita_detail.id_attpell = pellegrinaggi.id
                          AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                          ORDER BY 1 DESC, 2";
/*=======================
		MAIN
========================*/
if(($userid = session_check()) == 0)
    return;
config_timezone();
$current_user = ritorna_utente();

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

  if ($_POST) { // se post sono in fase di aggiornamento o ricerca del socio
       foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                    if($debug) {
                        echo $fname . ": KEY = " . $key . '<br>';
                        echo $fname . ": VALUE = " . $value . '<br>';
                        echo $fname . ": INDEX = " . $index . '<br><br>';                    	
                      }

                    switch($key) {
      		                      case "ricercaSocio": // Ricerca socio
      					                      $searchS=true;
      					                      break;

      		                      case "id-s": // Table ID
      					                      $sqlID = $value;
      					                      if($sqlID > 0) {
                                             $update = true;
                                             // Valorizzo testo tessera rinnovata
                                             if(ritorna_rinnovo($conn, $sqlID))
                                                $rinnovata = "&nbsp;<font style='background-color: green;'>&nbsp;T&nbsp;</font>";
                                             else
                                                $rinnovata = "&nbsp;<font style='background-color: red;'>&nbsp;T&nbsp;</font>";
                                          }
      					                      break;
                            }
                   $index++;
                  }
//return;
        if($update) {  // Carico i dati dal DB
            $sql_select = "SELECT cognome, nome, sesso, indirizzo, citta,
                                                cap, luogo_nascita, data_nascita, cf, ts, 
                                                telefono, cellulare, telefono_rif, email,
                                                id_tipo_doc, n_doc, data_ril, data_exp, id_stato_civile,
                                                id_professione, id_titolo_studio,id_cittadinanza, n_biancheria,pensionato,
                                                deceduto, data_decesso, sospeso, socio_effettivo, effettivo_dal,
                                                n_tessera, n_socio, n_tessera_unitalsi, id_sezione, id_stato_nascita,
                                                id_sottosezione, id_gruppo_par, id_categoria, id_tipo_personale,
                                                id_provincia, codice_catastale,id_luogo_rilascio,
                                                id_classificazione, id_classificazione1, id_disabilita,
                                                DATE_FORMAT((data), '" .$date_format . "') data, utente
                                  FROM    anagrafica
                                  WHERE  anagrafica.id = " . $sqlID;
            if($debug)
               echo "$fname: SQL = $sql_select";
		     $result = $conn->query($sql_select);
           $row = $result->fetch_assoc();

           $sqlcognome = $row["cognome"];
           $sqlnome = $row["nome"];
           $sqlsesso = $row["sesso"];
           $sqlindirizzo = $row["indirizzo"];

           $sqlcitta = $row["citta"];

           $sqlid_provincia=$row["id_provincia"];

           $sqlcap = $row["cap"];

           $sqlluogo_nascita = $row["luogo_nascita"];
           $sqlcodice_catastale = $row["codice_catastale"];
		     $sqldata_nascita = $row["data_nascita"]; 

           $sqlcf = $row["cf"];
           $sqlts = $row["ts"];
           $sqltelefono = $row["telefono"];
           $sqlcellulare = $row["cellulare"];

           $sqltelefono_rif = $row["telefono_rif"];
           $sqlemail = $row["email"];

           $sqlid_tipo_doc = $row["id_tipo_doc"];       
           $sqln_doc = $row["n_doc"];
           $sqldata_ril = $row["data_ril"];
           $sqldata_exp = $row["data_exp"];
           $sqlid_luogo_rilascio = $row["id_luogo_rilascio"];

           $sqlid_cittadinanza = $row["id_cittadinanza"];
           $sqlid_stato_civile = $row["id_stato_civile"];
           $sqlid_stato_nascita = $row["id_stato_nascita"];
           $sqlid_professione = $row["id_professione"];

           $sqlid_titolo_studio = $row["id_titolo_studio"];
           $sqln_biancheria = $row["n_biancheria"];
           $sqlpensionato = $row["pensionato"];       
           $sqldeceduto = $row["deceduto"];

           $sqldata_decesso= $row["data_decesso"];
           $sqlsospeso = $row["sospeso"];
           $sqlsocio_effettivo = $row["socio_effettivo"];
           $sqleffettivo_dal = $row["effettivo_dal"];

           $sqln_tessera= $row["n_tessera"];
           $sqln_socio = $row["n_socio"]; 
           $sqln_tessera_unitalsi = $row["n_tessera_unitalsi"];
           $sqlid_sezione = $row["id_sezione"];

           $sqlid_sottosezione= $row["id_sottosezione"];
           $sqlid_gruppo_par = $row["id_gruppo_par"];
           $sqlid_categoria = $row["id_categoria"];

           $sqlid_tipo_personale = $row["id_tipo_personale"];
           $sqlid_classificazione = $row["id_classificazione"];
           $sqlid_classificazione1 = $row["id_classificazione1"];
           $sqlid_disabilita= $row["id_disabilita"];
           $sqltimestamp= $row["data"];
           $sqlutente= $row["utente"];
           
           // Luogo rilascio documento
           if($sqlid_luogo_rilascio > 0) {
           	  $rs = $conn->query("SELECT CONCAT(comuni.nome,' (', province.sigla,')') n
           	                                     FROM   comuni, province
           	                                     WHERE comuni.id_provincia = province.id
           	                                     AND     comuni.id = $sqlid_luogo_rilascio");
           	  
           	  $rss = $rs->fetch_assoc();
           	  $sqlluogo_rilascio_display = $rss["n"];
           	  $rs->close();
              }
      }
  }
 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body></html>";
     return;
     }
      
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione); 

  if($searchS) { // Ricerca dati socio
      echo "<form id='searchTxt' action='" . $redirect . "' method='post'>";
      risultati_ricercaS($conn, 'searchTxt', $value);
      echo "</form>";
   }

 // Dati sempre visibili (in coppa)
  echo "<div style='width: 800px;'>"; // Pagina titolo e campo di ricerca
 
  echo "<table style='width: 100%;'>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione anagrafica socio</td>";
  echo "</tr>";

// Campo per la ricerca del socio
  echo "<form id='searchTxt' name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Ricerca socio</p></td>";

  echo "<td>";
  ricerca_socio('searchTxt');
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";
  echo "</div>";
  echo "<br>";
// Fine dati sempre visibili
 
   // Preparo i tab del menu
 echo "<div class='anagrafica' id='divana00' name='divana' style='width: 800px;'>"; // Pagina principale
 echo "<ul>";
 echo "<li class='activelink'><a href='#'>Dati principali</a></li>";
 echo "<li onClick='setActiveDiv(1);'><a href='#'>Contatti</a></li>";
 echo "<li onClick='setActiveDiv(2);'><a href='#'>Tessere</a></li>";
 echo "<li onClick='setActiveDiv(3);'><a href='#'>Altre informazioni</a></li>";
 
 if($update)
     echo "<li onClick='setActiveDiv(4);'><a href='#'>Storico partecipazioni</a></li>";
 echo "</ul>";

  echo "<table style='width: 100%;'>";

 // Form principale
  if($update) {
      echo "<form action='../php/update_sql.php' method='POST'>";
      echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
     }
  else {
      echo "<form action='../php/insert_sql.php' method='POST'>";
  	
     }
  echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
  echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";
  echo "<input type='hidden' name='codice_catastale' id='codice_catastale' value='" . $sqlcodice_catastale . "'>";
  echo "<input type='hidden' name='id_provincia' id='id_provincia' value='" . $sqlid_provincia . "'>";

  echo "<tr><td class='titolo' colspan=2>Dati principali</td></tr>";
  echo "<tr>";
  echo "<td><p class='required'>Cognome</p></td>";
  echo "<td><input class='required' maxlength='50' size='60' type='value' name='cognome' value='" .  htmlentities($sqlcognome, $defCharsetFlags, $defCharset) ."' required/>";
  
  if($rinnovata)
      echo $rinnovata;
  else  { // Sono in inserimento, propongo la stampa dopo inserimento
      echo "<input type='checkbox' name='printRow' id='cbPr' checked><label for='cbPr'>Stampa dato</label>";
     }
  echo "</td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Nome</p></td>";
  echo "<td><input class='required' maxlength='50' size='60' type='value' name='nome' value='" .  htmlentities($sqlnome, $defCharsetFlags, $defCharset) ."' required/></td>";
  echo "</tr>";
   
  echo "<tr>";
  echo "<td><p class='required'>Sesso</p></td>";
  echo "<td><select name='sesso' class='required' required>";
  $result = $conn->query($sqlselect_sesso);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlsesso)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["d"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Indirizzo</p></td>";
  echo "<td><p class='required'><input class='required' maxlength='50' size='60' type='value' name='indirizzo' value='" .  htmlentities($sqlindirizzo, $defCharsetFlags, $defCharset) ."' required/>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Citt&agrave; e CAP</p></td>";
  echo "<td><p class='required'>";
  ricerca_comune($conn, 'citta', $sqlcitta, 'div1');
  echo "<nobr><input class='required' onkeypress='return event.charCode >= 48 && event.charCode <= 57' size='5' maxlength='5' type='value' id='cap' name='cap' value='". $sqlcap . "' required/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Luogo e data di nascita</p></td>";
  echo "<td><p class='required'>";
  ricerca_comune($conn, 'luogo_nascita', $sqlluogo_nascita,'div2', 'required', true);
  echo "<input class='required' maxlength='10' size='10' type='date' name='data_nascita' value='" .  $sqldata_nascita ."' required/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Stato di nascita</p></td>";
  echo "<td><select name='id_stato_nascita' class='required' required>";
  
  $result = $conn->query($sqlselectnazioni);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_stato_nascita)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nazione_PS"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>';
  echo "</tr>"; 
  
  echo "<tr>";
  echo "<td><p class='required'>Cittadinanza</p></td>";
  echo "<td><select name='id_cittadinanza' class='required' required>";
  
  $result = $conn->query($sqlselectnazioni);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_cittadinanza)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nazione_PS"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>';
  echo "</tr>"; 
 
  echo "<tr>";
  echo "<td><p class='required'>Codice fiscale&nbsp</p></td>";
  
  echo "<td><p class='required'><input class='required' style='text-transform:uppercase' maxlength='16' size='21' type='value' name='cf' value='" .  htmlentities($sqlcf,$defCharsetFlags, $defCharset) ."' required/>";

  echo "&nbsp;&nbsp;&nbsp;<input type='button' class='in_btn' value='Calcola' onClick='calcola_codice_fiscale(this.form);'>";
  echo '</tr>';
  
  echo "<tr>";
  echo "<td><p class='required'>Documento (tipo e numero)</td>";
  echo "<td><p class='required'><select name='id_tipo_doc' class='required' required>";
  $result = $conn->query($sqlselecttipo_doc);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_tipo_doc)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select>'; 
  echo "&nbsp;";
  echo "<input class='required' maxlength='20' size='30' type='value' name='n_doc' value='" .  htmlentities($sqln_doc, $defCharsetFlags, $defCharset) ."' required/></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Data di rilascio / Scadenza</p></td>";
  echo "<td><p class='required'><input class='required' maxlength='10' size='10' type='date' name='data_ril' value='" . $sqldata_ril ."' required/><br>";
  echo "<input class='required' maxlength='10' size='10' type='date' name='data_exp' value='" .  $sqldata_exp ."' required/></p></td>";
  echo "</tr>";

// Luogo di rilascio documento
  echo "<input type='hidden' name='id_luogo_rilascio' id='id_luogo_rilascio' value=$sqlid_luogo_rilascio>";
  echo "<tr>";
  echo "<td><p class='required'>Luogo rilascio documento</p></td>";
  echo "<td>";
  ricerca_comune($conn, 'o', $sqlluogo_rilascio_display, 'div3', 'required', false, true);
  echo "</td>";
  echo "</tr>";

// Fine luogo rilascio documento
  echo "<tr>";
  echo "<td><p>Stato civile</p></td>";
  echo "<td><select name='id_stato_civile' class='search'>";
  $result = $conn->query($sqlselectstato_civile);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_stato_civile)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  
  echo "<tr>";
  echo "<input type='hidden' name='id_sezione' value=" . $idSoc . ">";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 

     echo "<td><select name='id_sottosezione' class='required' required>";
     $result = $conn->query($sqlselectsottosezione);
     while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select>';
       }
  echo '</td>'; 
  echo "</tr>";
 
  echo "<tr>";
  echo "<td><p class='required'>Gruppo</p></td>";
  echo "<td><select name='id_gruppo_par' class='required' required>";
  $result = $conn->query($sqlselectgruppo_par);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_gruppo_par)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Categoria</p></td>";
  echo "<td><select name='id_categoria' class='required' required>";
  $result = $conn->query($sqlselectcategoria);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_categoria)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>'; 
  echo '</tr>';

  echo "<tr>";
  echo "<td><p class='required'>Tipo personale</p></td>";
  echo "<td><select name='id_tipo_personale' class='required' required>";
  $result = $conn->query($sqlselecttipo_personale);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_tipo_personale)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Classificazione</p></td>";
  echo "<td><select name='id_classificazione' class='search'>";
  $result = $conn->query($sqlselectclassificazione);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_classificazione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>';
  echo "</tr>";

// Classificazione 1  
  echo "<tr>";
  echo "<td><p>Classificazione 1</p></td>";
  echo "<td><select name='id_classificazione1' class='search'>";
  $result = $conn->query($sqlselectclassificazione);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_classificazione1)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></td>';
  echo "</tr>";
  echo "</table>";
  echo "</div>"; // Fine div principale

  echo "<div class='anagrafica' id='divana01' name='divana' style='width: 800px; display: none;'>"; // Pagina contatti
  echo "<ul>";
  echo "<li onClick='setActiveDiv(0);'><a href='#'>Dati principali</a></li>";
  echo "<li class='activelink'><a href='#'>Contatti</a></li>";
  echo "<li onClick='setActiveDiv(2);'><a href='#'>Tessere</a></li>";
  echo "<li onClick='setActiveDiv(3);'><a href='#'>Altre informazioni</a></li>";
  if($update)
      echo "<li onClick='setActiveDiv(4);'><a href='#'>Storico partecipazioni</a></li>";
  echo "</ul>";
  echo "<table style='width: 100%;'>";

  echo "<tr><td class='titolo' colspan=2>Contatti</td></tr>";
  
  echo "<tr>";
  echo "<td><p>Telefono</p></td>";
  echo "<td><p><input maxlength='20' size='30' type='value' name='telefono' value='" .  htmlentities($sqltelefono, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Cellulare</p></td>";
  echo "<td><p><input maxlength='20' size='30' type='value' name='cellulare' value='" .  htmlentities($sqlcellulare, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Telefono di riferimento</p></td>";
  echo "<td><p><input maxlength='20' size='30' type='value' name='telefono_rif' value='" .  htmlentities($sqltelefono_rif, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>E-mail</p></td>";
  echo "<td><p><input maxlength='50' size='55' type='value' name='email' value='" .  htmlentities($sqlemail, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";

  echo "<div class='anagrafica' id='divana02' name='divana' style='display: none; width: 800px;'>"; // Pagina tessere
  echo "<ul>";
  echo "<li onClick='setActiveDiv(0);'><a href='#'>Dati principali</a></li>";
  echo "<li onClick='setActiveDiv(1);'><a href='#'>Contatti</a></li>";
  echo "<li class='activelink'><a href='#'>Tessere</a></li>";
  echo "<li onClick='setActiveDiv(3);'><a href='#'>Altre informazioni</a></li>";

  if($update) // Visualizzo storico partecipazioni
      echo "<li onClick='setActiveDiv(4);'><a href='#'>Storico partecipazioni</a></li>";
  echo "</ul>";
  
  echo "<table style='width: 100%;'>";

  echo "<tr><td class='titolo' colspan=2>Tessere</td></tr>";

  echo "<tr>";
  echo "<td><p>Tessera sanitaria</p></td>";
  echo "<td><input maxlength='20' size='25' type='value' name='ts' value='" .  htmlentities($sqlts, $defCharsetFlags, $defCharset) ."'/></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>N. tessera</p></td>";
  echo "<td><p><input maxlength='20' size='25' type='value' name='n_tessera' value='" .  htmlentities($sqln_tessera, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>N. socio</p></td>";
  echo "<td><p><input maxlength='20' size='25' type='value' name='n_socio' value='" .  htmlentities($sqln_socio, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>N. tessera UNITALSI</p></td>";
  echo "<td><p><input maxlength='20' size='25' type='value' name='n_tessera_unitalsi' value='" .  htmlentities($sqln_tessera_unitalsi, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";
  echo "</table>";
  echo "</div>"; // Fine pagina tessere

  echo "<div class='anagrafica' id='divana03' name='divana' style='width: 800px; display: none;'>"; // Pagina altre informazioni
  echo "<ul>";
  echo "<li onClick='setActiveDiv(0);'><a href='#'>Dati principali</a></li>";
  echo "<li onClick='setActiveDiv(1);'><a href='#'>Contatti</a></li>";
  echo "<li onClick='setActiveDiv(2);'><a href='#'>Tessere</a></li>";
  echo "<li class='activelink'><a href='#'>Altre informazioni</a></li>";
  if($update)
      echo "<li onClick='setActiveDiv(4);'><a href='#'>Storico partecipazioni</a></li>";
  echo "</ul>";
    
  echo "<table style='width: 100%;'>";

  echo "<tr><td class='titolo' colspan=2>Altre informazioni</td></tr>";

  echo "<tr>";
  echo "<td><p>Pensionato</p></td>";
  echo "<input type='hidden' name='pensionato' value='0'>";
  echo "<td><p class='required'><input type='checkbox' class='required'  name='pensionato' value='1'";
  if($sqlpensionato)
      echo " checked='true'>";
  else 
      echo '>';
  echo "</p></td></tr>";
    
  echo "<tr>";
  echo "<td><p>Deceduto (data)</p></td>";
  echo "<input type='hidden' name='deceduto' value='0'>";
  echo "<td><input type='checkbox' class='required'  name='deceduto' value='1'";
  if($sqldeceduto)
      echo " checked='true'>";
  else 
      echo '>';
  echo "&nbsp;<input maxlength='10' size='10' type='date' name='data_decesso' value='" .  $sqldata_decesso ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Professione</p></td>";
  echo "<td><p><select class='search' name='id_professione'>";
  $result = $conn->query($sqlselect_professione);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_professione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></p></td>';
  echo "</tr>";  
   
  echo "<tr>";
  echo "<td><p>Titolo di studio</p></td>";
  echo "<td><p><select class='search' name='id_titolo_studio'>";
  $result = $conn->query($sqlselect_titolo_studio);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_titolo_studio)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo "</tr>";  
   
  echo "<tr>";
  echo "<td><p>N. biancheria</p></td>";
  echo "<td><p><input maxlength='3' size='4' type='value' name='n_biancheria' value='" . $sqln_biancheria . "'/></p></td>";
  echo "</tr>";
  
  echo "<input type='hidden' name='sospeso' value=0>"; 
  echo "<tr>";
  echo "<td><p>Sospeso</p></td>";

  echo "<td><p><input type='checkbox' name='sospeso' value=1";
  if($sqlsospeso)
      echo ' checked="true">';
  else 
      echo '>';
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Socio effettivo (data)</p></td>";
  echo "<input type='hidden' name='socio_effettivo' value=0>";
  echo "<td><p><input type='checkbox' name='socio_effettivo' value=1";
  if($sqlsocio_effettivo)
      echo ' checked="true">';
  else 
      echo '>';
  echo "&nbsp;<input maxlength='10' size='10' type='date' name='effettivo_dal' value='" .  $sqleffettivo_dal ."'/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Disabilit&agrave;</p></td>";
  echo "<td><p class='required'><select class='search' name='id_disabilita'>";
  $result = $conn->query($sqlselectdisabilita);
   while($row = $result->fetch_assoc()) {
       	echo "<option value=" . $row["id"];
          if($row["id"] == $sqlid_disabilita)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></p></td>';
  echo '</tr>';
  echo '</table></div>';
 
  echo "<div class='anagrafica' id='divana04' name='divana' style='display: none; width: 800px;'>"; // Pagina storico
  echo "<ul>";
  echo "<li onClick='setActiveDiv(0);'><a href='#'>Dati principali</a></li>";
  echo "<li onClick='setActiveDiv(1);'><a href='#'>Contatti</a></li>";
  echo "<li onClick='setActiveDiv(2);'><a href='#'>Tessere</a></li>";
  echo "<li onClick='setActiveDiv(3);'><a href='#'>Altre informazioni</a></li>";
  echo "<li  class='activelink'><a href='#'>Storico partecipazioni</a></li>";
  echo "</ul>";
  $stmt = $conn->prepare($sql_storico);
  $stmt->bind_param("ii", $sqlID, $sqlID);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlannoS, $sql_descrizioneS);  
  echo "<table style='width: 100%;'>";

  echo "<tr><td class='titolo' colspan=2>Storico partecipazioni</td></tr>";

  echo "<tr>";
  echo "<td style='text-align: center;'><select class='search' size=10>";

  $index=0;
  $old_anno=0;
  while($stmt->fetch()) {
	         if($sqlannoS != $old_anno) {
	       	   if($index > 0)
	       	       echo "</optgroup>";
	            echo "<optgroup label=$sqlannoS>";
	            $old_anno = $sqlannoS;
              }
	         echo "<option disabled>" . htmlentities($sql_descrizioneS, $defCharsetFlags, $defCharset) . "</option>";
	         $index++;
          }
         
  if($index == 0)
      echo "<option disabled>--- Nessun dato storico presente ---</option>";
  else 
      echo "</optgroup>";
  echo "</select></td>";
  echo "</tr>";

  echo "</table>";
  echo "</div>"; // Fine storico
 
  // Div con pulsanti di azione

  echo "<div style='width: 800px;'>";
  
  echo "<table style='width: 100%';>";
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  if($update) {
     echo "<input type='hidden' name='utente' value='" . $current_user ."'>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td colspan='2'><table style='width: 100%;'><tr>";

     if($authMask["update"]) { // Su update rimuovo attributo "name" al campo Luogo rilascio documento
         echo  "<td class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'
         onmouseover='document.getElementById(\"o\").removeAttribute(\"name\");'></td>";
         echo "</form>";
        }

     if($authMask["delete"]) {
         echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
         echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
         echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
         echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqlcognome . ' ' . $sqlnome. " ?") ."');}\"></td>";
   	      }  
   	   echo "</tr></table></td></tr>";
   	    }  
   else {
     if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
         echo  "<td colspan='2' class='button'><input class='in_btn' id='btn' type='submit' value='Inserisci'
          onmouseover='document.getElementById(\"o\").removeAttribute(\"name\");'></td>";
         echo "</form>";
        }
     }
 echo "</div>"; // Fine div pulsanti di azione


 echo '</div></body></html>';

 
