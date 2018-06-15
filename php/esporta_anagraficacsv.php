<?php
/****************************************************************************************************
*
*  Funzione che esporta i dati anagrafici (tutti) in formato csv (EXCEL)
*
*  @file esporta_anagraficacsv.php
*  @abstract Esporta i dati della tabella anagrafica in formato csv
*  @author Luca Romano
*  @version 1.0
*  @time 2017-10-26
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-10-26
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
require_once('../php/unitalsi_include_common.php');
$fname=basename(__FILE__); 
$debug=false;
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$date_format=ritorna_data_locale();

$csv_export = '';
$csv_filename = 'anagrafica.csv';
$sqlSearch='';
$index=0;

$intestazioneCSV=array("COGNOME", "NOME", "SESSO", "INDIRIZZO", "CITTA", "CAP",
                                       "LUOGO NASCITA", "DATA NASCITA", "STATO DI NASCITA", "CITTADINANZA", "CODICE CATASTALE",
                                       "CODICE FISCALE", "DOCUMENTO", "NUMERO", "RILASCIO", "SCADENZA",
                                       "LUOGO RILASCIO",
                                       "STATO CIVILE", "GRUPPO", "CATEGORIA", "TIPO PERSONALE", "CLASSIFICAZIONE",
                                       "CLASSIFICAZIONE 1", "TELEFONO", "CELLULARE","TELEFONO RIFERIMENTO", "EMAIL",
                                       "TESSERA SANITARIA", "N. TESSERA", "N. SOCIO", "TESSERA UNITALSI",
                                       "PENSIONATO", "DECEDUTO", "DATA DECESSO", 
                                       "PROFESSIONE", "TITOLO DI STUDIO", "BIANCHERIA", "SOSPESO",
                                       "SOCIO EFFETTIVO", "DAL", "DISABILITA");

// SQL per dati anagrafici
$sqlCSV     = "SELECT anagrafica.id,
                                    anagrafica.cognome, anagrafica.nome,
                                    anagrafica.sesso, anagrafica.indirizzo,
                                    anagrafica.citta, anagrafica.cap,
                                    anagrafica.luogo_nascita, SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                    id_stato_nascita, 
                                    id_cittadinanza,
                                    codice_catastale,
                                    anagrafica.cf, anagrafica.id_tipo_doc,
                                    anagrafica.n_doc, SUBSTR(DATE_FORMAT(data_ril, '" . $date_format . "'),1,10) data_ril,
                                    SUBSTR(DATE_FORMAT(data_exp, '" . $date_format . "'),1,10) data_exp,
                                    anagrafica.id_luogo_rilascio, anagrafica.id_stato_civile, 
                                    gruppo_parrocchiale.descrizione des_par, IFNULL(categoria.descrizione, 'Sconosciuta') des_cat,
                                    id_titolo_studio, id_professione,
                                    anagrafica.telefono, anagrafica.cellulare,
                                    anagrafica.telefono_rif, anagrafica.email,
                                    anagrafica.n_biancheria, anagrafica.pensionato,
                                    anagrafica.deceduto, SUBSTR(DATE_FORMAT(data_decesso, '" . $date_format . "'),1,10) dt_dec,
                                    anagrafica.sospeso, anagrafica.socio_effettivo,
                                    SUBSTR(DATE_FORMAT(effettivo_dal, '" . $date_format . "'),1,10) dt_eff,
                                    anagrafica.ts, anagrafica.n_tessera, anagrafica.n_socio,
                                    anagrafica.n_tessera_unitalsi, anagrafica.id_tipo_personale,
                                    anagrafica.id_classificazione, anagrafica.id_classificazione1, anagrafica.id_disabilita
                                    
                        FROM   anagrafica 
                        LEFT JOIN gruppo_parrocchiale
                                 ON    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
                        LEFT JOIN categoria
                                 ON    anagrafica.id_categoria = categoria.id";
                                    

$conn = DB_connect();

// Check connection
if ($conn->connect_error) 
    die("Connection failed: " . $conn->connect_error);

if(!$_POST) { // se NO post allora chiamata errata
   echo "Chiamata alla funzione errata";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {
                  $kv[] = "$key=$value";
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                   switch($key) {
      		                      case "fileCSV": // File Output
      				                        $csv_filename = $value;
      				                        break;

      		                      case "sqlWhere": // Stringa con le clausole WHERE di selezione
      				                        $sqlWhere = $value;
      				                        break;

      		                      case "extras": // Stringa intestazione CSV
      				                        $intestazioneCSV = unserialize($value);
      				                        break;
      	
      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
      				                        break;
                              }
                  }
               $index++;
          }
 
    $sqlSearch = $sqlCSV . $sqlWhere . ", 3";         
    if($debug)
       echo "$fname (CSV) SQL = $sqlSearch<br>";;
    $result = $conn->query($sqlSearch);
    $field = mysqli_num_fields($result);
    
    if($debug)
        echo "$fname (CSV) Numero colonne = $field<br>";
// Intestazione CSV

    for($i=0; $i < count($intestazioneCSV); $i++)
          $csv_export .= '"' . $intestazioneCSV[$i] . '";';

    $csv_export .= '
';	

    while($row = $result->fetch_assoc()) {
    	      // Valorizzo il file CSV
              $csv_export .=  '"'.$row["cognome"].'";';
              $csv_export .=  '"'.$row["nome"].'";';
              $csv_export .=  '"'.$row["sesso"].'";';

              $csv_export .=  '"'.$row["indirizzo"].'";';
              $csv_export .=  '"'.$row["citta"].'";';
              $csv_export .=  '"'.$row["cap"].'";';

              $csv_export .=  '"'.$row["luogo_nascita"].'";';
              $csv_export .=  '"'.$row["dt_nas"].'";';
              
              // Stato nascita
              $des = 'Sconosciuto';
              
              if($row["id_stato_nascita"] > 0) { // Valorizzo stato nascita
                  $sql = "SELECT nazione_PS n FROM PS_nazioni WHERE id = " . $row["id_stato_nascita"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Cittadinanza
              $des = 'Sconosciuta';
              
              if($row["id_cittadinanza"] > 0) { // Valorizzo cittadinanza
                  $sql = "SELECT nazione_PS n FROM PS_nazioni WHERE id = " . $row["id_cittadinanza"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Codice catastale
              $csv_export .=  '"'.$row["codice_catastale"].'";';

              // Codice fiscale
              $csv_export .=  '"'.$row["cf"].'";';

              // Tipo documento
              $des = 'Sconosciuto';
              
              if($row["id_tipo_doc"] > 0) { // Valorizzo tipo di documento
                  $sql = "SELECT descrizione n FROM tipo_documento WHERE id = " . $row["id_tipo_doc"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Numero documento
              $csv_export .=  '"'.$row["n_doc"].'";';

              // Data rilascio
              $csv_export .=  '"'.$row["data_ril"].'";';

              // Data scadenza
              $csv_export .=  '"'.$row["data_exp"].'";';
              
              // Luogo rilascio
              $des = 'Sconosciuto';
              
              if($row["id_luogo_rilascio"] > 0) { // Valorizzo luogo rilascio
                  $sql = "SELECT CONCAT(comuni.nome, ' (' , province.sigla, ')') n FROM 
                               comuni, province WHERE comuni.id = " . $row["id_luogo_rilascio"] .
                             " AND comuni.id_provincia = province.id";

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';
              
              // Stato civile
              $des = 'Sconosciuto';
              
              if($row["id_stato_civile"] > 0) { // Valorizzo stato civile
                  $sql = "SELECT descrizione n FROM stato_civile WHERE id = " . $row["id_stato_civile"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Gruppo
              $csv_export .=  '"'.$row["des_par"].'";';

              // Categoria
              $csv_export .=  '"'.$row["des_cat"].'";';
              
              // Tipo personale
              $des = 'Sconosciuto';
              
              if($row["id_tipo_personale"] > 0) { // Valorizzo tipo personale
                  $sql = "SELECT descrizione n FROM tipo_personale WHERE id = " . $row["id_tipo_personale"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';
              
              // Classificazione
              $des = 'Sconosciuta';
              
              if($row["id_classificazione"] > 0) { // Valorizzo la classificazione
                  $sql = "SELECT descrizione n FROM classificazione WHERE id = " . $row["id_classificazione"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';
              
              // Classificazione 1
              $des = 'Sconosciuta';
              
              if($row["id_classificazione1"] > 0) { // Valorizzo la classificazione
                  $sql = "SELECT descrizione n FROM classificazione WHERE id = " . $row["id_classificazione1"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Telefono
              $csv_export .=  '"\''.$row["telefono"].'";';

              // Cellulare
              $csv_export .=  '"'.$row["cellulare"].'";';

              // Telefono Riferimento
              $csv_export .=  '"\''.$row["telefono_rif"].'";';

              // Email
              $csv_export .=  '"'.$row["email"].'";';

              // Tessera sanitaria
              $csv_export .=  '"\''.$row["ts"].'";';

              // N tessera
              $csv_export .=  '"\''.$row["n_tessera"].'";';

              // N socio
              $csv_export .=  '"\''.$row["n_socio"].'";';

              // N tessera unitalsi
              $csv_export .=  '"\''.$row["n_tessera_unitalsi"].'";';

              // Pensionato
              $des = 'No';
              
              if($row["pensionato"] == 1)
                  $des='Si';
              $csv_export .=  '"'.$des.'";';

              // Deceduto
              $des = 'No';
              
              if($row["deceduto"] == 1) {
                  $des='Si';
               }
              $csv_export .=  '"'.$des.'";';

              // Data decesso
              $csv_export .=  '"'.$row["dt_dec"].'";';
              
              // Professione
              $des = 'Sconosciuta';
              
              if($row["id_professione"] > 0) { // Valorizzo la Professione
                  $sql = "SELECT descrizione n FROM professione WHERE id = " . $row["id_professione"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';
              
              // Titolo di studio
              $des = 'Sconosciuto';
              
              if($row["id_titolo_studio"] > 0) { // Valorizzo il titolo di studio
                  $sql = "SELECT descrizione n FROM titolo_studio WHERE id = " . $row["id_titolo_studio"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

              // Biancheria
              $csv_export .=  '"'.$row["n_biancheria"].'";';

              // Sospeso
              $des = 'No';
              
              if($row["sospeso"] == 1) {
                  $des='Si';
               }
              $csv_export .=  '"'.$des.'";';

              // Socio effettivo
              $des = 'No';
              
              if($row["socio_effettivo"] == 1) {
                  $des='Si';
               }
              $csv_export .=  '"'.$des.'";';

              // Socio effettivo dal
              $csv_export .=  '"'.$row["dt_eff"].'";';
              
              // Disabilita'
              $des = 'Nessuna';
              
              if($row["id_disabilita"] > 0) { // Valorizzo la disabilita
                  $sql = "SELECT descrizione n FROM disabilita WHERE id = " . $row["id_disabilita"];

                  $r = $conn->query($sql);
                  
                  $rr = $r->fetch_assoc();
                  $des = $rr["n"];
                  
                  $r->close();
                 }
              $csv_export .=  '"'.$des.'";';

//---------------------------------------
              $csv_export.= '
';	
               }


// Export the data and prompt a csv file for download
header("Content-Description: File Transfer");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header("Content-type: text/x-csv");
header("Content-Disposition: attachment; filename=".$csv_filename."");
echo($csv_export);


?>