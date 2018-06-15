<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Ricerca dati</title>
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
  
</head>
<body>
<?php
/****************************************************************************************************
*
*  Gestione della dei dati di input per la ricerca
*
*  @file search.php.php
*  @abstract Gestisce la descrizione della categoria
*  @author Luca Romano
*  @version 1.1
*  @time 2017-10-02
*  @history prima versione
*  
*  @history (1.1) aggiunto campi di selezione
*  @first 1.0
*  @since 2017-01-31
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__);$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$sqlid_sottosezione=$sott_app;
$sqlid_gruppo_par=0;
$sqlid_old=$sqlid_sottosezione;
$redirect="../php/search.php";
$arrMonth = array("Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                               "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre");


     if(($userid = session_check()) == 0)
          return;

    $sqlselectprovincia = "SELECT id, CONCAT(nome,' (', sigla,')') nome FROM province ORDER BY 2";
    $sqlselectstato_civile = "SELECT id, descrizione FROM stato_civile ORDER BY 2";
    $sqlselectsottosezione = "SELECT sottosezione.id id,
                                                            sottosezione.nome
                                               FROM    sottosezione
                                               ORDER BY 2";

    $sqlselectgruppopar = "SELECT 0, id, descrizione FROM gruppo_parrocchiale";
    $sqlselectcategoria = "SELECT id, descrizione FROM categoria ORDER BY 2";
    $sqlselectprofessione = "SELECT id, descrizione FROM professione ORDER BY 2";
    $sqlselecttipo_personale = "SELECT id, descrizione FROM tipo_personale ORDER BY 2";
    $sqlselectclassificazione = "SELECT id, descrizione FROM classificazione ORDER BY 2";
    $sqlselectdisabilita = "SELECT 0, id, descrizione FROM disabilita ORDER BY 2";
               
     config_timezone();
     $current_user = ritorna_utente();
     $conn = DB_connect();

       // Check connection
     if ($conn->connect_error) 
          die("Connection failed: " . $conn->connect_error);

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

      		                            case "id_gruppo_par": // gruppo parrocchiale
      					                           $sqlid_gruppo_par = $value;
      					                           break;
                                   }
                          }
       }

     if($sqlid_sottosezione != $sqlid_old) {
  	      $sqlid_old = $sqlid_sottosezione;
  	      $sqlid_gruppo_par=0;
        }

// Version 1.1, altri parametri di selezione

  $sqlselect_attivita = "SELECT COUNT(*) ctr,
                                                  SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                  SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                  attivita_m.dal dal_order,
                                                  attivita_m.id id_prn, 
                                                  CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa
                                      FROM   attivita_detail,
                                                  attivita_m,
                                                  attivita,
                                                  anagrafica
                                      WHERE attivita.id =attivita_m.id_attivita
                                      AND     attivita_detail.id_attpell = attivita_m.id
                                      AND     attivita_detail.id_socio = anagrafica.id
                                      AND     attivita_m.id_sottosezione = $sqlid_sottosezione
                                      GROUP BY 2, 3, 4, 5, 6
                                      ORDER BY YEAR(dal_order) DESC, 6, 5"; 

  $sqlselect_pellegrinaggio = "SELECT COUNT(*) ctr,
                                                 SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
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
                                      AND     attivita_detail.id_attpell = pellegrinaggi.id
                                      AND     pellegrinaggi.id_sottosezione = $sqlid_sottosezione
                                      GROUP BY 2, 3, 4, 5, 6
                                      ORDER BY YEAR(dal_order) DESC, 6, 5"; 

// Fine Version 1.1, altri parametri di selezione
     $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 

     disegna_menu();
     echo "<div class='background' style='position: absolute; top: 100px;'>";

     echo "<table class='search'>";
     echo "<tr>";
     echo "<td colspan='4' class='titolo'>Ricerca dati</td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td><p class='search'>Sottosezione</p></td>";
     echo "<form action='" . $redirect . "' method='POST'>";
     if(!$multisottosezione) {
  	      echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
         echo "<td><p class='search'>" . htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
        }
     else { 

         echo "<td><p class='required'><select name='id_sottosezione' class='search' onChange='this.form.submit();'>";
         echo "<option value=0>--- Tutte ---</option>";
 
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
     echo '</p></td>'; 
     echo '</form>';  

     echo "<form action='../php/do_anagrafica_search.php' method='post'>";
  	  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
  
     if($sqlid_sottosezione > 0)     
         $sqlTmp = $sqlselectgruppopar . " WHERE id_sottosezione = " . $sqlid_sottosezione .
                                                                 " ORDER BY 3";
     else
           $sqlTmp = $sqlselectgruppopar . " WHERE id_sottosezione = " . $sott_app . " UNION
                                            SELECT 1, gruppo_parrocchiale.id,
                                            CONCAT(descrizione,' (Sottosezione di ' , sottosezione.nome,')') descrizione
                                            FROM    gruppo_parrocchiale,
                                                         sottosezione
                                             WHERE  gruppo_parrocchiale.id_sottosezione = sottosezione.id
                                             AND      id_sottosezione != " . $sott_app .
                                           " ORDER BY 1,3";
     $result = $conn->query($sqlTmp);

     echo "<td><p>Gruppo</p></td>";
     echo "<td><p class='required'><select class='search' name='id_gruppo_par'>";
     echo '<option value=0>--- Tutti ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";
     echo "</tr>";
 
     echo "<tr>";
     echo "<td><p>Cognome</p></td>";
     echo "<td><input class='search' id='cognome' size='60' maxlength='50' type='value' name='cognome' value=''/></td>";

     echo "<td><p>Nome</p></td>";
     echo "<td><input class='search' id='nome' size='60' maxlength='50' type='value' name='nome' value=''/></td>";
     echo "</tr>";
     
     // Versione 1.1

    // Attivita'
 
     echo "<tr>";
     echo "<td><p class='search'>Attivit&agrave;</p></td>";
     echo "<td><select name='id_attivita' class='search'>" ;
     echo "<option value=0>--- Seleziona eventuale attivit&agrave; ---</option>";
     $rAtt = $conn->query($sqlselect_attivita);
  
     while($rA = $rAtt->fetch_assoc()) {
     	         echo "<option value=" . $rA["id_prn"] . ">";
       	      echo  htmlentities($rA["desa"],$defCharsetFlags, $defCharset) . " (#" . $rA["ctr"] .") &minus;&gt; (". $rA["dal"] . " - " . $rA["al"] . ")</option>";
  	          }
     echo "</select></td>";
     $rAtt->close();

// Pellegrinaggio
     echo "<td><p class='search'>Pellegrinaggio</p></td>";
     echo "<td><select name='id_pellegrinaggio' class='search'>" ;
     echo "<option value=0>--- Seleziona eventuale Pellegrinaggio ---</option>";
     $rPel = $conn->query($sqlselect_pellegrinaggio);
  
     while($rP = $rPel->fetch_assoc()) {
               echo "<option value=" . $rP["id_prn"] . ">";
       	      echo htmlentities($rP["desa"],$defCharsetFlags, $defCharset) . " (#" . $rP["ctr"] .") &minus;&gt; (". $rP["dal"] . " - " . $rP["al"] . ")</option>";
  	          }
     echo "</select></td>";
     echo "</tr>";
     $rPel->close();
     // Fine versione 1.1

     echo "<tr>";
     echo "<td><p>Sesso</p></td>";
     echo "<td><p class='required'><select class='search' name='sesso'>";
     echo "<option value='T'>--- Tutti ---</option>";
     echo "<option value='F'>Femmina</option>";
     echo "<option value='M'>Maschio</option>";
     echo "</select></td>";
     echo "<td><p>Citt&agrave;</p></td>";
     echo "<td><input class='search' id='citta' size='60' maxlength='50' type='value' name='citta' value=''/></td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td><p>CAP</p></td>";
     echo "<td><input class='search' id='cap' size='6' maxlength='5' type='value' name='cap' value=''/></td>";

     $result = $conn->query($sqlselectprovincia);

     echo "<td><p>Provincia</p></td>";
     echo "<td><p class='required'><select class='search' name='id_provincia'>";
     echo '<option value=0>--- Tutte ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";
     echo "</tr>";

     $result = $conn->query($sqlselectstato_civile);

     echo "<tr>";
     echo "<td><p>Stato civile</p></td>";
     echo "<td><p class='search'><select class='search' name='id_stato_civile'>";
     echo '<option value=0>--- Tutti ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";
     $result = $conn->query($sqlselectprofessione);
     echo "<td><p>Professione</p></td>";
     echo "<td><p class='search'><select class='search' name='id_professione'>";
     echo '<option value=0>--- Tutte ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td><p>Pensionato</p></td>";
     echo "<td><p class='required'><select class='search' name='pensionato'>";
     echo "<option value='T'>--- Tutti ---</option>";
     echo "<option value=1>S&igrave;</option>";
     echo "<option value=0>No</option>";
     echo "</select></td>";
     echo "<td><p>Deceduto</p></td>";
     echo "<td><p class='required'><select class='search' name='deceduto'>";
     echo "<option value='T'>--- Tutti ---</option>";
     echo "<option value=1>S&igrave;</option>";
     echo "<option value=0>No</option>";
     echo "</select></td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td><p>Sospeso</p></td>";
     echo "<td><p class='required'><select class='search' name='sospeso'>";
     echo "<option value='T'>--- Tutti ---</option>";
     echo "<option value=1>S&igrave;</option>";
     echo "<option value=0>No</option>";
     echo "</select></td>";

     $result = $conn->query($sqlselectcategoria);
     echo "<td><p>Categoria</p></td>";
     echo "<td><p class='required'><select class='search' name='id_categoria'>";
     echo '<option value=0>--- Tutte ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";
     echo "</tr>";
     echo "<tr>";
     $result = $conn->query($sqlselecttipo_personale);

     echo "<td><p>Tipo Personale</p></td>";
     echo "<td><p class='required'><select class='search' name='id_tipo_personale'>";
     echo '<option value=0>--- Tutti ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";

     $result = $conn->query($sqlselectclassificazione);
     echo "<td><p>Classificazione</p></td>";
     echo "<td><p class='required'><select class='search' name='id_classificazione'>";
     echo '<option value=0>--- Tutte ---</option>';
     while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>";

     echo "<tr>";
     echo "<td><p>Effettivo</p></td>";
     echo "<td><p class='required'><select class='search' name='effettivo'>";
     echo "<option value='T'>--- Tutti ---</option>";
     echo "<option value=1>S&igrave;</option>";
     echo "<option value=0>No</option>";
     echo "</select></td>";


//     $result = $conn->query($sqlselectdisabilita);
     echo "<td><p>Con Disabilit&agrave;</p></td>";
     echo "<td><p class='required'><input name='id_disabilita' value=1 type='checkbox'></td>";
//     echo '<option value=0>--- Tutte ---</option>';
   /*  while($row = $result->fetch_assoc()) {
       	   	   echo "<option value='" . $row["id"] . "'";
       	   	   echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset). "</option>";
       	} 
     echo "</select></td>"; */
     echo "</tr>";

     echo "<tr>";
     echo "<td><p>Giorno/mese/anno di nascita</p></td>";
     echo "<td><select class='search' name='gg_nas'><option value=0>--</option>";
     for($index=1; $index < 32; $index++) {
           echo "<option value=" . $index . ">" . sprintf('%02d', $index) . "</option>";
        }
     echo "</select>";  

     echo "&nbsp;&nbsp;<select class='search' name='mm_nas'><option value=0>--</option>";
     for($index=1; $index < 13; $index++) {
           echo "<option value=" . $index . ">" . $arrMonth[$index-1] . "</option>";
        }
     echo "</select>";

     echo "&nbsp;&nbsp;<select class='search' name='aa_nas'><option value=0>----</option>";
     for($index=date('Y'); $index > (date('Y') - 110); $index--) {
           echo "<option value=" . $index . ">" . sprintf('%02d', $index) . "</option>";
        }
     echo "</select></td>";

     echo "</tr>";  
        

     echo "<tr>";
     echo "<td class='button' align='center' colspan='2'><input type='reset' name='azzera' value='Pulisci'/></td>";
     echo "<td class='button' align='center'colspan='2'><input type='submit' name='avvia' value='Avvia ricerca'/></td>";
     echo "</tr>";

     echo "</form>";
     echo "<tr><td colspan='4' style='vertical-align: top; font: italic 12px Arial, Helvetica;'>";
     echo "<br><b>Consigli utili:</b>";
     echo "<ul>";
     echo "<li>nei campi editabili si pu&ograve; utilizzare la \"wildcard\" <b>%</b></li>";
     echo "<li>esempi di ricerca validi:</li>";

     echo "<ul>";
     echo "<li><b>\"scuola%\"</b> ritorna tutti gli elementi che <u>INIZIANO</u> con la parola 'Scuola' o 'scuola'</li>";
     echo "<li><b>\"%scUola%\"</b> ritorna tutti gli elementi che <u>CONTENGONO</u> la parola 'Scuola' o 'scuola'</li>";
     echo "</ul><br>";

     echo "<li>o una combinazione degli stessi:</li>";

     echo "<ul>";
     echo "<li>Per cercare le anagrafiche il cui cognome inizia con la parola <b>scuola</b> di sesso femminile</b><br>
       digiter&ograve; <b>\"scuola%\"</b> nel campo <b>Cognome</b>
       e selezioner&ograve; <b>\"Femmina\"</b> nella lista <b>Sesso</b></li>";
     echo "</ul>";

     echo "</ul>";

     echo "</td>";
     echo "</tr>";
     echo "</table>";
     $conn->close();

     echo "</body>";
     echo "</html>";