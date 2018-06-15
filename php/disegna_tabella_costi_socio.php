<?php
require_once('../php/unitalsi_include_common.php');
require_once("../php/ritorna_tessera_rinnovata.php");
require_once('../php/ricerca_comune.php');

if(!check_key())
   return; 
/****************************************************************************************************
*
*
*  Funzione che disegna la tabella dei costi del socio
*
*  @file disegna_tabella_costi_socio.php
*  @abstract Disegna tabella HTML coi costi del socio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-25
*  @history first release
*  
*  @first 1.0
*  @since 2017-02-25
*  @CompatibleAppVer All
*  @where Monza
*
*  input : connessione aperta al db
*             id socio
*            id attività dell'anno (tabella attivita_m) /viaggio o pellegrinaggio
*            tipo ('A' = attivita', 'V' = viaggio/pellegrinaggio)
*            data di fine pellegrinaggio (in caso di 'V' controllo data scadenza documento)
*            modifica dati (true/false, default false)
*
*
****************************************************************************************************/
//==== Variabili globali ====
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset();
$defCharsetFlags = ritorna_default_flags(); 
$date_format=ritorna_data_locale();
$sottosezione=ritorna_sottosezione_pertinenza();

$redirect=null;
$tipo='N';
$tesseraOK=false;
$msgAction=null;
$sqlid_attpell=0;
$sqlid_socio=0;
$sqlnome_socio=null;
$sqlnome_socio_js=null;
$sqltipo_viaggio=0; // 0 = Nessuno, 1 = Andata, 2 = Ritorno, 3 = Andata e Ritorno
$sqlVdal=null; // Partenza viaggio/Pellegrinaggio
$sqlVal=null; // Termine viaggio/Pellegrinaggio
$showTessera=false; // True se devo visualizzare costo tessera
$totCosti=0.00; // Totale costi soci

$sqlluogo_rilascio_display=null; // Display comune rilascio documento


$sqlCostiArray=array(); // Array dei costi
$sqlQtaArray=array(); // Array delle quantita'
$sqlRiduzione=array(); // Array delle riduzioni
$sqlnote;


define('EURO',chr(128));
setlocale(LC_MONETARY, 'it_IT');

//================== Parte SQL ======================

// Tipo pagamento
$sql_tipopag = "SELECT id, descrizione
                           FROM   tipo_pagamento
                           ORDER BY 2";
/*------------------------------------------------------------

       Tabella categoria/servizio (Viaggi/Pellegrinaggi)
       
------------------------------------------------------------*/
$sql_categoria = "SELECT id, descrizione
                              FROM   categoria
                              ORDER BY 2";

$sql_servizio = "SELECT id, descrizione
                            FROM   servizio
                            ORDER BY 2";

/*------------------------------------------------------------

       Tabella elenco documenti di identita'
       
------------------------------------------------------------*/
$sqlselect_tipo_doc = "SELECT id, descrizione
                                      FROM   tipo_documento
                                      ORDER BY 2";

/*------------------------------------------------------------

       Tabella elenco nazioni (Viaggi/Pellegrinaggi)
       
------------------------------------------------------------*/
$sqlselect_nazioni = "SELECT 0, id, nazione_PS
                                    FROM   PS_nazioni
                                    WHERE id = 1
                                    UNION
                                    SELECT 1, id, nazione_PS
                                    FROM    PS_nazioni
                                    WHERE id > 1
                                    ORDER BY 1, 3";

/*------------------------------------------------------------

       Tabella elenco riduzioni
       
------------------------------------------------------------*/
$sql_riduzioni = "SELECT id, CONCAT(descrizione,' (', costo, ')') ridu, costo
                             FROM   riduzione
                             WHERE id_sottosezione = ?
                             ORDER BY 2";
                                     
/*------------------------------------------------------------

       Attivita' di tesseramento
       
------------------------------------------------------------*/
$sql_tessera = "SELECT attivita_m.id, 
                                       attivita.descrizione,
                                       costi.costo,
                                       costi.id id_c
                          FROM   attivita,
                                      attivita_m,
                                      costi
                          WHERE  attivita.id = attivita_m.id_attivita
                          AND      costi.id_attpell = attivita.id
                          AND      costi.tessera = 1
                          AND      attivita_m.anno = ?
                          AND     attivita_m.id_sottosezione = ?";

//=========== Costi ==============
/*--------------------- Costi principali -------------------------
       Tabella costi da anagrafica costi
 ------------------------------------------------------------*/
                           
// Costi secondari
$sql_costi_s = "SELECT costi.id,
                                       costi.descrizione,
                                       costi.costo,
                                       0 qt
                          FROM    costi
                          WHERE  costi.id_parent = ?
                          ORDER BY 1";
// Totale costi
$sql_totcosti= "SELECT SUM(costi.costo) tot
                          FROM   costi
                          WHERE id_parent = 0
                          AND     tipo =?
                          AND     id_attpell IN(SELECT id FROM  ?
                                                                      WHERE id = ?";
//=========== Fine Costi ==============
//================== FINE Parte SQL ======================

function disegna_t_costi($conn, $l_id_socio, $id_attpell, $l_tipo, $anno = null, $edit = false) {
// Variabili globali
   global $debug;
   global $fname;
   global $defCharset;
   global $defCharsetFlags; 
   global $date_format;

   global $msgAction;
	global $redirect;
   global $sottosezione;
   global $tipo;
   global $sqlid_attpell;

	global $sqlselect_tipopag;
	global $sql_idsocio;
	global $sqlnome_socio;
	global $sqlselect_nazioni;
	global $sqlnome_socio_js;
	global $sql_categoria;
	global $sql_servizio;
	global $sqlselect_tipo_doc;
	global $sql_riduzioni;
	global $sqlluogo_rilascio_display;
	global $sql_costi_p;
	global $sqlanno;
	global $sqlnote;
	
	$sqlanno = date('Y');
	
	if(isset($anno))
	   $sqlanno = $anno;
	
	global $sqlVdal; // Partenza viaggio/Pellegrinaggio
	global $sqlVal; // Termine viaggio/Pellegrinaggio
	global $showTessera;

	$debug=false;
// FINE Variabili globali
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
// SQL Socio
$sql_socio = "SELECT CONCAT(cognome,' ', nome) nome,
                                    id_tipo_doc,
                                    n_doc,
                                    data_ril,
                                    data_exp,
                                    cellulare,
                                    email,
                                    telefono_rif,
                                    tipo_viaggio,
                                    anagrafica.id_categoria catsocio,
                                    attivita_detail.id_categoria catviaggio,
                                    attivita_detail.id_servizio,
                                    attivita_detail.dal s_dal,
                                    attivita_detail.al s_al,
                                    attivita_detail.id id_att,
                                    attivita_detail.status_acc,
                                    attivita_detail.note,
                                    SUBSTRING(DATE_FORMAT(data_nascita,'" . $date_format ."'),1,10) dnas,
                                    IFNULL(CONCAT('- ATTENZIONE! &less;> ', disabilita.descrizione),'') desd,
                                    IFNULL(CONCAT('(', categoria.descrizione, ')'),'') descat,
                                    anagrafica.sospeso,
                                    anagrafica.id_cittadinanza,
                                    anagrafica.id_luogo_rilascio
                      FROM   anagrafica
                      LEFT JOIN disabilita
                      ON anagrafica.id_disabilita = disabilita.id
                      LEFT JOIN categoria
                      ON anagrafica.id_categoria = categoria.id
                      LEFT JOIN attivita_detail
                      ON (anagrafica.id = attivita_detail.id_socio AND
                      attivita_detail.id_attpell = ?)
                      WHERE anagrafica.id = ?";
	
$testsql_costi_p = "SELECT costi.id,
                                             costi.descrizione,
                                             costi.costo,
                                             costi.tessera,
                                             1 qt
                                FROM    costi
                                WHERE  id_parent = 0 
                                AND      costi.tipo = ?";

// SQL Costi principali
$sql_costi_p = "SELECT costi.id,
                                       costi.descrizione,
                                       costi.costo,
                                       costi.tessera,
                                       1 qt
                          FROM    costi
                          WHERE  id_parent = 0 
                          AND      costi.tipo = ?";
                          
if($l_tipo == 'A') { // Attivita'
	 $sql_costi_p .= " AND costi.id_attpell IN(SELECT id_attivita
	                                                                 FROM   attivita_m
	                                                                 WHERE id=?)";
} else {
	 $sql_costi_p .= " AND costi.id_attpell = ?";
   }
                        
$sql_costi_p .= " ORDER BY 1";

$tipo=$l_tipo; // Assegno tipo alla variabile globale
$sqlid_attpell=$id_attpell; // Assegno ID attivita/viaggio alla variabile globale
$sqlid_socio=$l_id_socio; // Assegno ID Socio variabile globale

$update=false;
$id_riga=0;
$table_name="attivita_detail";
$redirect="../php/gestione_soci_attivita.php?tipo=" . $tipo;
$index=0;
$insertStm = " ";
$userid=0;
$vociMenu=0;
$dateExpire='';

$showDoc=false; // true se documento non valido
$showLuogoRilascio=false; // true se luogo rilascio documento NON valorizzato
$showCittadinanza=false; // true se cittadinanza NON valorizzata

$sqlid_tipo_doc=0;
$sqln_doc=null;
$sqldata_ril=null;
$sqldata_exp=null;
$sqlcausale=null;
$sqlid_tipopag=1;
$sqlcat_viaggio=0; // Categoria del socio nel viaggio
$sqlstatus_acc=0; // Status accompagnatore

$livello_utente=10;
$tbattpell='';
$totCosto=0;
$txtAlert='UNKNOWN';

$totRicevuta=0.00;
$tesseraOK=false; // Controllo se ha rinnovato la tessera

$sql_totcosti_attivita = "SELECT SUM(costi.costo) tot
                                        FROM  costi
                                        WHERE id_parent = 0 AND tipo = '" . $tipo ."' AND id_attpell IN(SELECT id_attivita
                                                                                                         FROM  attivita_m
                                                                                                         WHERE id = " . $sqlid_attpell . ")";
                                 
/*------------------------------------------------------------

       Tabella costi da socio gia' inserito
       
------------------------------------------------------------*/
$sql_totcosti_socio = "SELECT SUM(costi_detail.costo*costi_detail.qta) tot
                                     FROM  costi_detail
                                     WHERE id_parent   IN(SELECT id
                                                                        FROM  attivita_detail
                                                                        WHERE id_socio = " . $sqlid_socio . "
                                                                        AND     tipo = '" . $tipo. "'
                                                                        AND     id_attpell = " . $sqlid_attpell . ")";
$sql_costi_associato = "SELECT COUNT(*), costi_detail.id,
                                                    costi_detail.descrizione,
                                                    costi_detail.qta qt,
                                                    (costi_detail.costo*costi_detail.qta) costo,
                                                    costi.tessera
                                        FROM   costi_detail,
                                                     costi,
                                                     attivita_detail,";

switch($tipo) {
	         case 'A': // attivita
	                 $tbattpell = 'attivita_detail';
	                 $txtAlert = 'dall\'attivit&agrave;';
	                 break;

	         case 'V': // viaggio/pellegrinaggio
	                 $tbattpell = 'pellegrinaggi';
	                 $txtAlert = 'dal viaggio/pellegrinaggio';
	                 break;

	         default: // sconosciuto
	                  echo "$fname: UNKNOWN OPTION '" . $tipo . "'<br>";
	                  return;
	                  break;
}
// Carico dati del socio selezionato
  if($debug) {
  	  echo "$fname: SQL socio = $sql_socio<br>";
     echo "$fname: ID attivita/viaggio (bind) = $sqlid_attpell<br>";;
     echo "$fname: IDsocio (bind)= $sqlid_socio<br>";
  	  }

  $result = $conn->prepare($sql_socio);
  $result->bind_param("ii", $sqlid_attpell, $sqlid_socio);
  $result->execute();
  $result->store_result();
  $result->bind_result($nome,
                                    $sqlid_tipo_doc,
                                    $sqln_doc,
                                    $sqldata_ril,
                                    $sqldata_exp,
                                    $sqlcellulare,
                                    $sqlemail,
                                    $sqltelefono_rif,
                                    $sqltipo_viaggio,
                                    $sqlcat_socio,
                                    $sqlcat_viaggio,
                                    $sqlid_servizio,
                                    $sqls_dal,
                                    $sqls_al,
                                    $sqlid_att,
                                    $sqlstatus_acc,
                                    $sqlnote,
                                    $sqldata_nascita,
                                    $sqldesd,
                                    $sqldescat,
                                    $sqlsospeso,
                                    $sqlid_cittadinanza,
                                    $sqlid_luogo_rilascio);

  $row = $result->fetch();
  
  $sqlnome_socio = htmlentities(($nome . " " . $sqldescat . " " . $sqldesd), $defCharsetFlags,$defCharset);
  $sqlnome_socio_js = $nome . " " . $sqldescat . " " . $sqldesd;
  if($sqlid_att) { // Socio gia' presente in attivita'/Pellegrinaggio
      $update = true;
	   }
 else{
 	  $sqlid_att = 0;
     }
 $result->close();
 
 if(!$sqlid_servizio) // Servizio socio non selezionato
     $sqlid_servizio = 0;

  echo "<form action='../php/manage_attivita_detail.php' method='post'>";
  echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
  echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
  echo "<input type=\"hidden\" name=\"tipo\" value=\"" . $tipo . "\">";
  echo "<input type='hidden' name='id_sottosezione' value=" . $sottosezione . ">";
  echo "<input type='hidden' name='anno' value=$sqlanno>";
  echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
  echo "<input type='hidden' name='id_socio' value=" . $sqlid_socio . ">";
                                                                          
 
 if(!$sqlcat_viaggio) // Categoria socio non selezionata, di default assegno quella dell'anagrafica
     $sqlcat_viaggio = $sqlcat_socio;
// Inizio HTML
// Dati socio selezionato
  echo "<table align='left' style='width: 100%;'>"; // Tabella principale
  echo "<tr>";
  echo "<td style='text-align: left;  font:bold italic 20px Arial, Helvetica, sans-serif;'>" . $sqlnome_socio .
           " - Nato/a il: " . $sqldata_nascita .  "</td>";
  echo "<td style='text-align: right;  font:bold italic 20px Arial, Helvetica, sans-serif; white-space: nowrap'>Totale&nbsp;<input disabled style='text-align: right; font: bold italic 20px Arial, Helvetica, sans-serif; max-width: 90px; font-color: green;' type='number' id='totale' name='importo' value=$totCosto>&nbsp;&euro;</td>";  
  echo "</tr>";
  
/*------------------------------------------------------------

       Tabella date dal/al 
       
------------------------------------------------------------*/
  if($tipo == 'A') {
	  $sql_dalal = "SELECT dal, al
	                        FROM    attivita_m
	                        WHERE   id = $sqlid_attpell";
	                      
	  if($debug)
	      echo "$fname SQL Dal/al (attivita):$sql_dalal<br>";

	 $r = $conn->query($sql_dalal);
	 $rs = $r->fetch_assoc();
	 $sqlVdal = $rs["dal"];
	 $sqlVal = $rs["al"];
	 $r->close();
	 // Valorizzo dal/al con quelli dell'attivita'
	 echo "<input type='hidden' name='dal' value='$sqlVdal'>";
	 echo "<input type='hidden' name='al' value='$sqlVal'>";
  	}

  if($sqlsospeso) {
    	 $msgAction = "ATTENZIONE! Il socio risulta sospeso.\\n";
    }
/*------------------------------------------------------------

       Permetto di aggiornare eventuali informazioni di contatto
       
------------------------------------------------------------*/
  echo "<tr>";

  echo "<td colspan='3'>";
  echo "<table style='width: 100%;'>"; // Tabella per contatti e altre info
      
  echo "<td><p>Smartphone</p></td>";
  echo "<td><input name='Ucellulare' value='" . $sqlcellulare . "'/></td>";
  echo "<td><p>E-mail</p></td>";
  echo "<td><input name='Uemail' size=40 value='" . $sqlemail . "'/></td>";
  echo "<td><p>Telefono di riferimento</p></td>";
  echo "<td><input name='Utelefono_rif' value='" . $sqltelefono_rif . "'/></p></td>";
  echo "</tr>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  if($tipo == 'V') {
	  $sql_dalal = "SELECT dal, al
	                        FROM    pellegrinaggi
	                        WHERE   id = $sqlid_attpell";
	                      
	  if($debug)
	      echo "$fname SQL Dal/al (viaggio/pellegrinaggio): $sql_dalal<br>";

	 $r = $conn->query($sql_dalal);
	 $rs = $r->fetch_assoc();
	 $sqlVdal = $rs["dal"];
	 $sqlVal = $rs["al"];
	 $r->close();
	 
	 if(!$sqls_dal) { // Socio NON presente in attivita'/Pellegrinaggio
	     $sqls_dal = $sqlVdal;
	     $sqls_al = $sqlVal;
	    }
	 
	 // Controllo la tessera
    $tesseraOK = ritorna_rinnovo($conn, $sqlid_socio, substr($sqls_dal, 0, 4) );
    
    if(!$tesseraOK) {
    	 $msgAction .= "ATTENZIONE! La tessera non risulta rinnovata.\\n";
    	 $showTessera=true;
    	 // Attivita' tesseramento
      }
      
  	 if (!$sqldata_exp || $sqldata_exp == '0000-00-00' || $sqldata_exp < $sqls_al) {
  	       $msgAction .= "ATTENZIONE! Socio con documento in scadenza o non valorizzato.\\n";
          $showDoc=true;
        } 

    if($sqlid_luogo_rilascio == 0) { // Luogo rilascio documento non valorizzato
  	    $msgAction .= "ATTENZIONE! Luogo rilascio documento socio NON valorizzato.\\n";
  	    $showLuogoRilascio=true;
       }

    if($sqlid_cittadinanza == 0) { // Cittadinanza NON valorizzata
  	    $msgAction .= "ATTENZIONE! Cittadinanza socio NON valorizzata.";
  	    $showCittadinanza=true;
       }
     
     echo "<tr><td colspan='3'><hr></td></tr>";
  
     echo "<tr>";
     echo "<td colspan='3'>";
     
     // Apro tabella per servizio/periodo/tipo di viaggio
     echo "<table>";
     echo "<tr>";
     echo "<td style='vertical-align: top;'><p class='required'>Categoria nel Viaggio</p></td>";
     echo "<td style='vertical-align: top;'><select name='id_categoria' class='search'>";
     echo "<option value=0>--- Seleziona categoria ---</option>";
     $result = $conn->query($sql_categoria);
     while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                
                if($row["id"] == $sqlcat_viaggio)
                    echo " selected";
                echo  ">" . htmlentities($row["descrizione"], $defCharsetFlags,$defCharset)  . "</option>";
              }
     echo "</select>";
     echo "</td>";

     // Periodo di permanenza dal
  	  echo "<td style='vertical-align: top;'><p class='required'>Periodo di permanenza dal</td>";
  	  echo "<td style='vertical-align: top;'><input class='required' name='dal' type='date' value='$sqls_dal' min='$sqlVdal' max='$sqlVal'></td>";

// Tipo di viaggio
      echo "<td rowspan='2' style='vertical-align: top;'><p class='required'>Tipo di viaggio</p></td>";
      echo "<td rowspan='2'><p class='required'>";
      echo "<input type='radio' id='rN' name='tipo_viaggio' value=0";
      if($sqltipo_viaggio == NONE)
          echo " checked";
      echo "><label for='rN'>Nessuno</label><br>";

      echo "<input type='radio' id='rA' name='tipo_viaggio' value=1";
      if($sqltipo_viaggio == TO)
          echo " checked";
      echo "><label for='rA'>Solo andata</label><br>";

      echo "<input type='radio' id='rR' name='tipo_viaggio' value=2";
      if($sqltipo_viaggio == FROM)
          echo " checked";
      echo "><label for='rR'>Solo ritorno</label><br>";
      
      echo "<input type='radio' id='rAR' name='tipo_viaggio' value=3";
      if($sqltipo_viaggio == ROUNDTRIP)
          echo " checked";
      echo "><label for='rAR'>Andata e Ritorno</label></p></td>";  
 
  // Accompagantore  
     echo "<td rowspan='2' style='vertical-align: top;'><p class='required'>Accompagna</p></td>";
      echo "<td rowspan='2' style='vertical-align: top;'><p class='required'>";
      echo "<input type='radio' id='aN' name='status_acc' value=0";
      if($sqlstatus_acc == NONE)
          echo " checked";
      echo "><label for='aN'>Nessuno</label><br>";

      echo "<input type='radio' id='aA' name='status_acc' value=1";
      if($sqlstatus_acc == ACCOMPAGNATORE)
          echo " checked";
      echo "><label for='aA'>Accompagnatore</label><br>";

      echo "<input type='radio' id='aC' name='status_acc' value=2";
      if($sqlstatus_acc == ACCOMPAGNATO)
          echo " checked";
      echo "><label for='aC'>Accompagnato</label><br>";
      echo "</tr>";
     
     echo "<tr>";
     echo "<td style='vertical-align: top;'><p class='required'>";
     echo "Servizio</p></td>";
     echo "<td style='vertical-align: top;''><select name='id_servizio' class='search'>";
     echo "<option value=0>--- Seleziona servizio ---</option>";
     $result = $conn->query($sql_servizio);
     while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                
                if($row["id"] == $sqlid_servizio)
                    echo " selected";
                echo  ">" . htmlentities($row["descrizione"], $defCharsetFlags,$defCharset)  . "</option>";
              }
     echo "</select>";
     echo "</td>";

  // Propongo date di permanenza = a inizio e fine viaggio e tipologia di viaggio
  	  echo "<td style='vertical-align: top; text-align: right;'><p class='required'>Al</p></td>";
  	  echo "<td style='vertical-align: top;'><input class='required' name='al' type='date' value='$sqls_al' min='$sqlVdal' max='$sqlVal'>";
  	  echo "</p></td>";
      echo "</table>";
  	  echo "</tr>";


     if($showDoc || $showLuogoRilascio || $showCittadinanza) { // Documento scaduto oppure manca luogo di rilascio oppure manca cittadinanza
  	      echo "<tr><td colspan='3'><hr></td></tr>";
  	   
  	      echo "<tr>";
  	      echo "<td colspan='3' style='text-align: center;'><p class='required'>AGGIORNARE DOCUMENTO</p></td>";
  	      echo "</tr>";

  	      echo "<tr>";
  	      echo "<td><p class='required'>Tipo/Numero</p></td>";
  	   
  	      echo "<td style='text-align: left;'><select class='required' name=Uid_doc>";

  	      $resultDoc = $conn->query($sqlselect_tipo_doc);
  	      while($rowDoc = $resultDoc->fetch_assoc()) {
  	   	             echo "<option value=" . $rowDoc["id"];
  	   	          
  	   	             if($rowDoc["id"] == $sqlid_tipo_doc)
  	   	                 echo " selected";
  	   	             echo ">" . htmlentities($rowDoc["descrizione"], $defCharsetFlags, $defCharset) . "</option>"; 
  	   	           }
  	      echo "</select>";
  	      echo "</td>";
  	      echo "<td><input class='required' name='Un_doc' size='15' maxlength='15' value='" . htmlentities($sqln_doc, $defCharsetFlags, $defCharset) . "' required/></td>";
  	      echo "</tr>";
  	   
  	      echo "<tr>";
  	      echo "<td><p class='required'>Data rilascio/scadenza</p></td>";
  	      echo "<td style='text-align: left;'><input class='required' type='date' name='Udata_ril' value='" . $sqldata_ril . "' required/></td>";
  	      echo "<td><input class='required' type='date' name='Udata_exp' value='" . $sqldata_exp . "' required/></td>";
  	      echo "</tr>";
        }

     if($showCittadinanza) { // Cittadinanza NON valorizzata
         echo "<tr>";
         echo "<td><p class='required'>Cittadinanza</p></td>";
         echo "<td colspan=2>";
         echo "<select class='required' name='Uid_cittadinanza' required>";
         echo "<option value=>--- Seleziona la cittadinanza ---</option>";
  	     $resultCitt = $conn->query($sqlselect_nazioni);
  	      while($rowCitt = $resultCitt->fetch_assoc()) {
  	   	             echo "<option value=" . $rowCitt["id"]. ">";;
  	   	             echo htmlentities($rowCitt["nazione_PS"], $defCharsetFlags, $defCharset) . "</option>"; 
  	   	           }
  	   	   echo "</select>";
         echo "</td>";
         echo "</tr>";
        }
     else {
     	  echo "<input type='hidden' name='Uid_cittadinanza' value=$sqlid_cittadinanza>";
     }
        
     if($showLuogoRilascio) { // Luogo rilascio documento NON valorizzato
          echo "<input type='hidden' name='Uid_luogo_rilascio' id='id_luogo_rilascio' value=$sqlid_luogo_rilascio>";
          echo "<tr>";
          echo "<td><p class='required'>Luogo rilascio documento</p></td>";
          echo "<td colspan='2' style='content-align: left;'>";
          ricerca_comune($conn, 'o', $sqlluogo_rilascio_display, 'div3', 'required', false, true);
          echo "</td>";
         }
     else {
     	  echo "<input type='hidden' name='Uid_luogo_rilascio' value=$sqlid_luogo_rilascio>";
     }


   } // Fine IF viaggio = 'V'

  echo "</table>";
  echo "</td></tr>";
   
  
  echo "<tr>";
  echo "<td colspan='3'><hr></td>";
  echo "</tr>";
  //return;

// Visualizzo eventuali informazioni di contatto del socio per aggiornarlo
  if($msgAction)
      echo "<script>avviso_no('$msgAction');</script>";
// Fine HTML

  if($update) {
     // Se attivita' permetto di aggiungere altre voci (Dicembre 2017)
     if($tipo == 'A') {
         update_socio($conn);
         
         $sqlid_att = 0;
     	// Riapro la form senza dati contatti
     	  echo "<form action='../php/manage_attivita_detail.php' method='post'>";
        echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
        echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
        echo "<input type=\"hidden\" name=\"tipo\" value=\"" . $tipo . "\">";
        echo "<input type='hidden' name='id_sottosezione' value=" . $sottosezione . ">";
        echo "<input type='hidden' name='anno' value=$sqlanno>";
        echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
        echo "<input type='hidden' name='id_socio' value=" . $sqlid_socio . ">";
	     echo "<input type='hidden' name='dal' value='$sqlVdal'>";
	     echo "<input type='hidden' name='al' value='$sqlVal'>";

         add_socio($conn, $sqlid_att);
        }
        
    if($tipo == 'V') {
      add_socio($conn, $sqlid_att);
     }

//     $sqlExec = $sql_totcosti_socio;
     }  
  else {
  	  echo "<tr><td colspan=3>";
     add_socio($conn, $sqlid_att);
     echo "</tr></td>";
     } 
     
  // Calcolo totale
  echo "<script>computeTot();</script>";
  // Form che permette di inserire e stampare la ricevuta in fase di aggiunta socio
}
//============================
//
//		Aggiungo socio a attivita'/Viaggio
//)
//============================
function add_socio($conn, $sqlid_att = 0) {	
  global $fname;
  global $debug;
  global $authMask;
  global $msgAction;
  global $defCharset;
  global $defCharsetFlags; 
  global $date_format;
  global $redirect;
  global $tipo;
  global $sottosezione;
  global $sqlid_attpell;
  global $sqlid_socio;
  global $sottosezione;
  global $authMask;

  global $sql_socio;
  global $sqlnome_socio;
  global $sqlnome_socio_js;
  global $sql_costi_p;
  global $sql_costi_s;
  global $sqlanno;
	
  global $sqlVdal; // Partenza viaggio/Pellegrinaggio
  global $sqlVal; // Termine viaggio/Pellegrinaggio
  global $showTessera;
  global $sql_tessera;
  global $sql_riduzioni;
  global $sql_tipopag;
  global $sqlnote;
  
  global $totCosti;
  global $sqlCostiArray; // Array dei costi
  global $sqlQtaArray; // Array delle quantita'
  global $sqlRiduzione; // Array delle riduzioni
    
//$debug=true;
setlocale(LC_MONETARY, 'it_IT');

// Riduzione rilevata dai costi gia' presenti
$sqlIdRidInserita = 0;
$sqlValRidInserita = 0.00;

 // Apro tabella dei costi del viaggio/pellegrinaggio  
  echo "<table style='width: 100%; border-style: solid; border-width: 3px; z-index: -1;'>";

  if($debug) {
     echo "$fname SQL Costi Principali = $sql_costi_p<br>";
     echo "$fname: tipo (bind) = $tipo<br>";;
     echo "$fname: IDattpell (bind)= $sqlid_attpell<br>";
  }

  $result = $conn->prepare($sql_costi_p);
  $result->bind_param("si", $tipo,
                                            $sqlid_attpell);
  $result->execute();
  $result->store_result();
  $result->bind_result($id_p,
                                    $descrizione_p,
                                    $costo_p,
                                    $tessera_p,
                                    $qta_p);
  $index=0;
  
  while($row = $result->fetch()) {
  	         if($index == 0) { // Intestazione
  	             
  	             echo "<tr>";
  	             echo "<th width='400px'>Descrizione</th>";
  	             echo "<th width='60px'>Qta</th>";
  	             echo "<th width='90px'>Totale</th>";
  	             
  	             if($tipo == 'A') {
  	                 echo "<th>&nbsp;</th>";
  	                }

  	             if($tipo == 'V') { // Se viaggio permetto di inserire le note
  	                 echo "<th>Note</th>";
  	                }
  	             echo "</tr>";

  	             echo "<tr>";
  	             echo "<td colspan='4'><hr></td>";
  	             echo "</tr>";
  	            }
  	            
  	          // Controllo se ci sono gia' dei costi inputati
  	          
  	          $sqlCostiPresenti = "SELECT costi_detail.costo,
  	                                                         costi_detail.id_riduzione,
  	                                                         costi_detail.qta,
  	                                                         costi_detail.valore
  	                                             FROM   costi_detail
  	                                             WHERE costi_detail.id_parent = $sqlid_att
  	                                             AND     costi_detail.id_costo = $id_p";
  	                                             
  	          if($debug) {
  	          	 echo "$fname: Check costi primari presenti = $sqlCostiPresenti<br>";
  	             }
  	             
  	          $rPresenti = $conn->query($sqlCostiPresenti);
  	          if($rPresenti->num_rows > 0 && $tipo == 'V') { // found viaggio/pellegrinaggio
  	             $rsPresenti = $rPresenti->fetch_assoc();
  	             
  	             $qta_p = $rsPresenti["qta"];
  	             $costo_p = $rsPresenti["costo"];
  	             
  	             if($index == 0) { // Carico riduzione se presente
  	                 $sqlIdRidInserita = $rsPresenti["id_riduzione"];
                    $sqlValRidInserita = $rsPresenti["valore"];
  	                }
  	             }
  	          array_push($sqlCostiArray,array('m', $costo_p,1, $id_p));
  	          $sqlQtaArray[$index] = $qta_p;
  	          
             echo "<input type='hidden' name='txt[]' value='" . htmlentities($descrizione_p, $defCharsetFlags,$defCharset) . "' />";

  	          echo "<tr class='highlight'>";
  	          echo "<td><p class='required'>";
// Descrizione 
             echo htmlentities($descrizione_p, $defCharsetFlags,$defCharset); 
             if($index==0 && $tessera_p == 1) { // Propongo Nuova o Rinnovo
                echo " - ";
                echo "&nbsp;<input type='radio' id='t_r' name='nuova' value=0 checked><label for='t_r'>Rinnovo</label>";
                echo "&nbsp;<input type='radio' id='t_n' name='nuova' value=1><label for='t_r'>Nuova</label>";
               } 
           echo "</p></td>"; 
// Quantita' 
            echo "<td style='text-align: right;'><p class='required'><input onChange='computeTot();' name='qta[]' size='4' id='qta' class='numero' type='number' step='1' min='1' max='9000' value='". $qta_p . "'></p></td>";

// Valore
            echo "<td style='text-align: right;'>";
            echo "<input onChange='computeTot();' type='number' id='costo' class='numeror' name='valore[]' min='0.00' max='50000.00' step='0.01' value=$costo_p />&nbsp;&euro;";
            echo "</td>";
            
            //echo "<td>&nbsp;</td>";

            $totCosti += $costo_p;
            if($tipo == 'V' && $index == 0) { // Note
            	   echo "<td rowspan=4>";
            	   echo "<p><textarea name='note' maxlength='300'>" .  htmlspecialchars($sqlnote, $defCharsetFlags, $defCharset) . "</textarea></p>";
            	   echo "</td>";
               }
             
             echo "</tr>";
            //return;
                           
             // Verifico se esistono eventuali costi aggiuntivi legati al costo principale

              if($debug) {
                  echo "$fname SQL Costi Secondari = $sql_costi_s<br>";
                  echo "$fname: id_parent (bind) = " . $id_p . "<br>";;
                 }

             $resultO = $conn->prepare($sql_costi_s);
             $resultO->bind_param("i", $id_p);
             $resultO->execute();
             $resultO->store_result();
 
             $resultO->bind_result($id_s,
                                                  $descrizione_s,
                                                  $costo_s,
                                                  $qta_s);
             while($resultO->fetch()) {
             	       // Controllo se ci sono gia' dei costi inputati
  	          
  	                    $sqlCostiPresenti = "SELECT costi_detail.costo,
  	                                                                   costi_detail.qta
  	                                                      FROM   costi_detail
  	                                                      WHERE costi_detail.id_parent = $sqlid_att
  	                                                      AND     costi_detail.id_costo = $id_s";
  	                                             
  	                    if($debug) {
  	          	          echo "$fname: Check costi secondari presenti = $sqlCostiPresenti<br>";
  	                     }
  	             
                      $qta_s = 0;
  	                   $rPresenti = $conn->query($sqlCostiPresenti);
  	                    if($rPresenti->num_rows > 0 && $tipo == 'V') { // found solo per viaggio/pellegrinaggio
  	                        $rsPresenti = $rPresenti->fetch_assoc();
  	             
  	                        $qta_s = $rsPresenti["qta"];
  	                        $costo_s = $rsPresenti["costo"];
  	                       }

                      echo "<input type='hidden' name='txt[]' value='" . htmlentities($descrizione_s, $defCharsetFlags,$defCharset) . "'/>";             	      
                      echo "<input type='hidden' name='valore[]' value=$costo_s />";
                      
                      echo "<tr class='highlight'>";
             	      echo "<td><p class='search'>&minus;&gt;&nbsp;&nbsp;";

                      echo htmlentities($descrizione_s, $defCharsetFlags,$defCharset) . "</p></td>";
// Quantita' 
                      echo "<td style='text-align: right;'><p class='search'>";
                      echo "<input onChange='computeTot();' size='4' id='qta' name='qta[]' class='numero' type='number' step='1' min='0' max='9000' value=$qta_s /></p></td>";
                      echo "<td style='text-align: right;'><p class='search'>" . money_format('%(!n',$costo_s) . "&nbsp;&euro;</p></td>";
 
 
                      echo "</tr>";
                      $index++;
  	                   array_push($sqlCostiArray,array('s', $costo_s,0, $id_s));
                      $sqlQtaArray[$index] = $qta_s;

              } 
              echo "<tr><td colspan='4'><hr></td></tr>";
              $resultO->close();
              $index++;
     } 

     if($showTessera) { // Tessera non rinnovata, propongo il costo
         if($debug) {
             echo "$fname SQL Tesseramento = $sql_tessera<br>";
             echo "$fname: Anno competenza  (bind) = " . substr($sqlVdal, 0, 4) . "<br>";;
             echo "$fname: Sottosezione = $sottosezione<br>";;
            }
         $rTessera = $conn->prepare($sql_tessera);
         $rTessera->bind_param("ii", (substr($sqlVdal, 0, 4)), $sottosezione);
         $rTessera->execute();
         $rTessera->store_result();
         $rTessera->bind_result($id_t,
                                                $descrizione_t,
                                                $costo_t,
                                                $id_costo_t);
     
         if($rTessera->num_rows > 0) {
             $rTessera->fetch();
  	          array_push($sqlCostiArray,array('t', $costo_t,1, $id_costo_t));
  	          echo "<tr class='highlight'>";
  	      
  	          echo "<td><p class='required'>";
  	          echo "<input type='hidden' name='txt[]' value='" . htmlentities($descrizione_t, $defCharsetFlags,$defCharset) . "'/>";
             echo "<input type='hidden' id='costo' name='valore[]' value=$costo_t />";
             echo htmlentities($descrizione_t, $defCharsetFlags,$defCharset);

             echo " - ";
             echo "Rinnovo";
             echo "<input type='radio' name='new_t' value=0 checked/>";

             echo "Nuova";
             echo "<input type='radio' name='new_t' value=1 />";
             echo "</p></td>";

             echo "<input id='tessera_included' type='hidden' value=0>";
             echo "<td style='text-align: right;'><p class='required'>";
             echo "<input onChange='computeTot();' size='4' id='qta' name='qta[]' class='numero' type='number' step='1' min='0' max='9000' value=0 /></p></td>";

             echo "<td style='text-align: right;'><p class='required'>" . money_format('%(!n',$costo_t) . "&nbsp;&euro;</p></td>";
             echo "</tr>";
             
             // Verifico se ci sono costi secondari tessera (ad esempio Adulti/Ragazzi)

              if($debug) {
                  echo "$fname SQL Costi Secondari Tessera = $sql_costi_s<br>";
                  echo "$fname: id_parent (bind) = " . $id_costo_t . "<br>";;
                 }
             $resultO = $conn->prepare($sql_costi_s);
             $resultO->bind_param("i", $id_costo_t);
             $resultO->execute();
             $resultO->store_result();
             $resultO->bind_result($id_s,
                                                    $descrizione_s,
                                                    $costo_s,
                                                    $qta_s);

             while($resultO->fetch()) {
             	      echo "<tr class='highlight'>";
                      echo "<input type='hidden' name='txt[]' value='" . htmlentities($descrizione_s, $defCharsetFlags,$defCharset) . "'/>";
                      echo "<input type='hidden' name='valore[]' value=$costo_s />";

                      echo "<td><p class='search' style='text-align: left;'>&minus;&gt;&nbsp;" . htmlentities($descrizione_s, $defCharsetFlags,$defCharset) . "</p></td>";
// Quantita' 
                      echo "<td style='text-align: right;'><p class='search'>";
                      echo "<input onChange='computeTot();' size='4' id='qta' name='qta[]' class='numero' type='number' step='1' min='0' max='1000' value=$qta_s /></p></td>";
                      echo "<td style='text-align: right;'><p class='search'>" . money_format('%(!n',$costo_s) . "&nbsp;&euro;</p></td>";
 
                      echo "</tr>";
                      $index++;
  	                   array_push($sqlCostiArray,array('t', $costo_s,0, $id_s));
                      $sqlQtaArray[$index] = '0';
                      echo "</tr>";
                    } 
            }
          else { // Attivita' di tesseramento inesistente
             echo "<tr>";
             echo "<td colspan='4'><p class='required'>ATTENZIONE! Attivit&agrave; di tesseramento non trovata per l'anno " . substr($sqlVdal, 0, 4) . "</p></td>";
             echo "</tr>";
          }
         echo "<tr><td colspan='4'></p><hr></td></tr>";
         
        }  

  if($index > 0) { // Visualizzo scelta riduzione
      $index=0;
      $sqlRiduzione[$index]=0.00;
      $index++;
      
      $result = $conn->prepare($sql_riduzioni);
      $result->bind_param("i", $sottosezione);
      $result->execute();
      $result->bind_result($id, $ridu, $costo);

      echo '<tr>';
      echo "<td colspan='2' width='310px'><select class='search' id='id_riduzione' name='id_riduzione' onChange='computeTot();'>";
      echo "<option value=0>--- Seleziona eventuale riduzione ---</option>";
      while($result->fetch()) {
                echo "<option value=$id";
                if($id == $sqlIdRidInserita)
                    echo " selected";
                echo ">" . htmlentities($ridu, $defCharsetFlags,$defCharset) . "</option>";
                $sqlRiduzione[$index]= $costo;
                $index++;
              } 

      echo "</select></td>";                
      echo "<td style='text-align: right;'><p class='search'><input class='numero'  onChange='computeTot(this.value,0);' type='number' id='valore_riduzione-hidden' name='valore-rid' min='0.00' max='9999.99' step='0.01' value=$sqlValRidInserita>"; 
      echo "&nbsp;&euro;</p></td>";
      
      echo "<td>&nbsp;</td>";
      echo "</tr>";
          
     }

  echo "<tr>";
  echo "<td colspan=4>";
  echo "<ul>";
  echo "<li>";
  echo "Causale&nbsp;<input class='field' name='causale' size='60' maxlength='80'/>";
  echo "<ul>";
  echo "<li>";
  echo "<p class='required'>Pagato&nbsp;<input name='importo' id='importo' type='number' class='prezzo' min='0.00' max='99999.00' step='0.01' size='5' value=" . sprintf('%01.2f',$totCosti) . ">";

  echo "&nbsp;<select name='id_pagamento' class='required' required>";
  
  $result = $conn->query($sql_tipopag);
  while($row = $result->fetch_assoc()) {
          echo "<option value=" . $row["id"];
          if(1 == $row["id"])
              echo " selected";
          echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo "</select></p></li>";

  echo "<li>";
  echo "<input class='field' id='cbRic' type='checkbox' name='emetti' value=1><label for='cbRic'> Seleziona per emettere ricevuta</label>";
  echo "</li>";
  echo "</ul>";
  echo "</li>";
  echo "</ul>";
  echo "</td>";
  echo "</tr>";  
  echo "</table>";

  echo "<tr>";
  echo "<td colspan='4' align='center'>";
  
  // Apro la tabella dei pulsanti
  echo "<table style='width: 100%;'>";
  echo "<tr>";

//  	echo "AUTH = " . $authMask["update"];
 
  if($sqlid_att == 0) { // Inserimento   
      if($authMask["insert"]) {
      	    echo "<td style='text-align: center;'>"; 
          echo "<input type='hidden' name='sqlArray' value='" . htmlentities(serialize($sqlCostiArray)) . "'>";
          echo "<input class='in_btn' id='btn' type='submit' value='Aggiungi' align='center'></td>";
          echo "</td>";
          echo "</form>";
         }
      }
  else {
  	   if($authMask["update"]) {
          echo "<td style='text-align: center;'>";
          echo "<input type='hidden' name='sqlArray' value='" . htmlentities(serialize($sqlCostiArray)) . "'>";
          echo "<input class='in_btn' id='btn' type='submit' value='Modifica' align='center'></td>";
          echo "</td>";
         }
      echo "</form>";
 
  	   if($authMask["delete"]) {
  	   	    echo "<form action='../php/manage_attivita_detail.php' method='post'>";
          echo "<input type='hidden' name='removeDtl[]' id='removeDtl' value=$sqlid_att>";
  	   	    echo "<input type='hidden' name='tipo' value='V'>";
  	   	    echo "<input type='hidden' name='removeIt' value=1>";
  	   	    echo "<input type='hidden' name='id_sottosezione' value=$sottosezione>";
  	   	    echo "<input type='hidden' name='anno' value=$sqlanno>";
  	   	    echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";


          echo "<td style='text-align: center;'>";

          echo "<input class='in_btn' id='btn' type='submit' value='Rimuovi socio' align='center'  onclick=\"{return conferma('". ritorna_js("Rimuovo il socio ?") ."');}\"></td>";
          echo "</td>";
         }
      }
  echo "</tr>";
  echo "</td>";
  echo "</tr>";
  echo "</table>"; // Chiudo la tabella dei pulsanti

  echo "</td>";
  echo "</tr>";

  echo "</table>";
  if($debug)
      var_dump($sqlCostiArray); 

?>
<script type="text/javascript">
/*------------------------------------------------

	Funzione che calcola e visualizza il totale aggiornato
	quando vengono modificate le quantita'
	
	N.B. Lo script deve essere incluso prima della graffa di chiusura funzione
	
-------------------------------------------------*/
 function computeTot(rid=0, ts=0) {	    
           var debug=false,
                 cArr = document.getElementsByName('valore[]'),
                 qArr = document.getElementsByName('qta[]'),

                 rArr = <?php echo json_encode($sqlRiduzione); ?>,
                 ridArr = document.getElementById('id_riduzione'),

                 totField = document.getElementById('totale'), // Totale costi
                 importoRicevuta = document.getElementById('importo'), // Importo ricevuta
                 valRiduzione = document.getElementById('valore_riduzione-hidden'),
                 i = 0,
                 totValue = 0,
                 c = 0,
                 q = 0,
                 riduzioneValue = 0.00, 
                 a = ridArr.selectedIndex;

           if(debug) {
   	           alert("DEBUG: Costi length "+cArr.length);
   	           alert("DEBUG: Qta length "+qArr.length);
   	           alert("DEBUG: Riduzioni length "+rArr.length);
   	           alert("DEBUG: Riduzioni Index "+a);
   	           alert("DEBUG: Parametro rid "+rid);
   	           alert("DEBUG: Parametro ts "+ts);
     	       }   

           if(rid <= 0)
               riduzioneValue=rArr[a];
           else
               riduzioneValue = rid;
               
          /* if(!rArr[a])
               riduzioneValue = 0.00; */
           valRiduzione.value = riduzioneValue; 

           for(i = 0; i < cArr.length; i++) {
           	    if(debug) {
           	    	alert("DEBUG: Costo ("+i+") "+cArr[i].value);
           	    	alert("DEBUG: Qta ("+i+") "+qArr[i].value);
           	       }
                 c = cArr[i].value;
                 q = qArr[i].value;
                 totValue += (c*q);
                } 
           if(debug) {
              alert("DEBUG: Totale calcolato = "+totValue);
             } 
          totValue -=  riduzioneValue;
          totField.value=totValue.toFixed(2);
          importoRicevuta.value=totValue.toFixed(2);
       //  alert(totValue);
          if(ts == 1) {
          	var te = document.getElementById("tessera_included");
          	te.value = 1;
          	//alert(ts.value);
           }           
}
</script>
<?php

// Se aggiornamento calcolo totali
if($sqlid_att > 0) {
	 echo "<script>computeTot($sqlValRidInserita)</script>";
   }
 } // FINE inserimento nuovo socio
//============================
//
//		Modifico/Cancello socio gia' inserito in attivita'/Viaggio
//)
//============================
function update_socio($conn) {	
  global $fname;
  global $debug;
  global $authMask;
  global $msgAction;
  global $defCharset;
  global $defCharsetFlags; 
  global $date_format;
  global $redirect;
  global $tipo;
  global $sottosezione;
  global $sqlid_attpell;
  global $sqlid_socio;
  global $sottosezione;

  global $sql_socio;
  global $sqlnome_socio;
  global $sqlnome_socio_js;
  global $sql_costi_p;
  global $sql_costi_s;
	
  global $sqlVdal; // Partenza viaggio/Pellegrinaggio
  global $sqlVal; // Termine viaggio/Pellegrinaggio
  global $showTessera;
  global $sql_tessera;
  global $sql_riduzioni;
  global $sql_tipopag;
  
  global $totCosti;
  global $sqlCostiArray; // Array dei costi
  global $sqlQtaArray; // Array delle quantita'
  global $sqlRiduzione; // Array delle riduzioni
  
  $sqlid_remove=0;
/*-------------------------- Costi  ----------------------------
       Tabella da ID Attpell
 ------------------------------------------------------------*/
//======= ID Attpell=========
$sql_att = "SELECT attivita_detail.id,
                                attivita_detail.id_attpell
                   FROM   attivita_detail
                   WHERE  tipo = ?
                   AND     id_attpell = ?
                   AND     id_socio = ?";
/*--------------------- Costi  ---------------------------------
       Tabella costi da anagrafica costi
 ------------------------------------------------------------*/
$sql_costi_sp = "SELECT costi_detail.id,
                                        costi_detail.descrizione,
                                        costi_detail.costo,
                                        costi_detail.qta,
                                        costi_detail.principale,
                                        costi_detail.id_parent,
                                        SUBSTRING(DATE_FORMAT(data,'" . $date_format ."'),1,10) dt
                          FROM    costi_detail
                          WHERE  costi_detail.id_parent IN(SELECT attivita_detail.id
                                                                              FROM   attivita_detail
                                                                              WHERE  tipo = ?
                                                                              AND     id_attpell = ?
                                                                              AND     id_socio = ?)
                          ORDER BY 1";

/*--------------------- Ricevute  -------------------------
       Tabella ricevute (se emesse)
 ------------------------------------------------------------*/
 $sql_fatture_s;
//$debug=true;
setlocale(LC_MONETARY, 'it_IT');
  
 // Apro tabella dei costi associati dell'attivita'/viaggio/pellegrinaggio  
  echo "<table style='width: 100%; border-style: solid; border-width: 2px;'>";
    
  echo "<tr>";
  echo "<td colspan='4' class='titolo'>Elenco costi gi&agrave; associati al socio</td>";
  echo "</tr>";

  if($debug) {
     echo "$fname SQL Costi Socio = $sql_costi_sp<br>";
     echo "$fname: Tipo (bind)= $tipo<br>";
     echo "$fname: IDattpell (bind)= $sqlid_attpell<br>";
     echo "$fname: IDsocio (bind)= $sqlid_socio<br><br>";

     echo "$fname SQL Attivita detail = $sql_att<br>";
     echo "$fname: Tipo (bind)= $tipo<br>";
     echo "$fname: IDattpell (bind)= $sqlid_attpell<br>";
     echo "$fname: IDsocio (bind)= $sqlid_socio<br>";
    }
// ID attivita
  $stmt = $conn->prepare($sql_att);
  $stmt->bind_param("sii", $tipo,
                                           $sqlid_attpell,
                                           $sqlid_socio);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlid_remove,
                                  $sqlid_attpell);
  $stmt->fetch();
  $stmt->close();

// ID Costi
  $result = $conn->prepare($sql_costi_sp);
  $result->bind_param("sii", $tipo,
                                             $sqlid_attpell,
                                             $sqlid_socio);
  $result->execute();
  $result->store_result();
  $result->bind_result($id_sp,
                                    $descrizione_sp,
                                    $costo_sp,
                                    $qta_sp,
                                    $principale_sp,
                                    $parent_sp,
                                    $dtins);
 
  // Chiudo la form che non serve in fase di modifica
  echo "</form>";
  $inx = 0; 
  while($result->fetch()) {
  	         if($inx == 0) { // Intestazione e form
                echo "<form action='../php/manage_attivita_detail.php' method='post'>";
                echo "<input type='hidden' name='tipo' value='" . $tipo . "'>";
                echo "<input type='hidden' name='id_sottosezione' value=$sottosezione>";
                echo "<input type='hidden' name='anno' value=" . substr($sqlVdal, 0, 4) . ">";
                echo "<input type='hidden' name='tipo' value='" . $tipo . "'>";
                echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
                echo "<input type='hidden' name='removeIt' value=1>";
                  	             
  	             echo "<tr>";
  	             echo "<th width='250px';>Descrizione</th>";
  	             echo "<th width='50px'>Qta</th>";
  	             echo "<th width='90px'>Totale</th>";
  	             echo "<th>&nbsp;</th>";
  	             echo "</tr>";

  	             echo "<tr>";
  	             echo "<td colspan='4'><hr></td>";
  	             echo "</tr>";
  	            }
  	         $inx++;
  	         echo "<tr class='highlight'>";

  	         if($principale_sp) { // Costi principali
  	             echo "<td><p class='required'>";
// Descrizione 
                echo htmlentities($descrizione_sp, $defCharsetFlags,$defCharset);
                
                if($tipo == 'A')
                    echo " (" . $dtins . ")";
                echo  "</p></td>";
// Quantita' 
                echo "<td style='text-align: right;'><p class='required'>$qta_sp</p></td>";
// Costo
                echo "<td style='text-align: right;'><p class='required'>" . money_format('%(!n',($costo_sp*$qta_sp)) . "&nbsp;&euro;</p></td>";

                if($authMask["delete"]) { // Propongo eliminazione se autorizzato
                    if($tipo == 'A') 
                        echo "<td><input type='checkbox' name='removeDtl[]' id='removeDtl' value=$parent_sp><label for='removeDtl'>Elimina</label></td>";
                    else {
                    	 echo "<input type='hidden' name='removeDtl[]' id='removeDtl' value=$parent_sp>";
                	   }
                   }
                else {
                	echo "<td>&nbsp;</td>";
                   }
  	         }
  	         else { // Costi secondari
// Descrizione 
  	             echo "<td><p class='search'>&minus;&gt;&nbsp;&nbsp;";
  	          
                echo htmlentities($descrizione_sp, $defCharsetFlags,$defCharset) . "</p></td>";
// Quantita' 
                echo "<td style='text-align: right;'><p class='search'>$qta_sp</p></td>";
// Costo
                echo "<td style='text-align: right;'><p class='search'>" . money_format('%(!n',($costo_sp*$qta_sp)) . "&nbsp;&euro;</p></td>";
  	       	 }
          echo "</tr>";       
           }  
  echo "</table></td></tr>";
  
  echo "<tr>";
  echo "<td colspan='4'><table style='width: 100%;'><tr>";
  
  if($inx > 0 && $authMask["delete"]) { // Tasto elimina
  
      $txtDel='Elimina selezionati';
      
      if($tipo == 'V')
          $txtDel = 'Rimuovi socio';
      echo "<td align='center'>";
      echo "<input class='in_btn' id='btn' type='submit'  value='$txtDel' onclick=\"{return conferma('". ritorna_js("Cancello le righe selezionate ?") ."');}\"></td>";
     }
  echo "</form>";

  
  // Form per tornare alla pagina principale
  
  echo "<form action='../php/gestione_soci_attivita.php?tipo=" . $tipo . "' method='post'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sottosezione>";
  echo "<input type='hidden' name='anno' value=" . substr($sqlVdal, 0, 4) . ">";
  echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";

  echo "<td align='center'>";
  echo "<input class='in_btn' id='btn' type='submit' value='Torna a' align='center'></td>";
  echo "</form>";
  
  // Se abilitato propongo aggiornamento (solo in caso di viaggio/pellegrinaggio'
 /* if($authMask["update"] && $tipo == 'V') {
      echo "<form action='../php/manage_attivita_detail.php' method='post'>";
      echo "<td align='center'>";
      echo "<input class='in_btn' id='btn' type='submit'  value='Aggiorna'></td>";
      echo "</form>";
  	  } */

  // Se abilitato propongo cancellazione
  echo "</tr></table></td></tr>";
}