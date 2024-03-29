<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
/****************************************************************************************************
*
*  Inserisce e stampa la ricevuta alla persona
*
*  @file inserisci_e_stampa.php
*  @abstract Inserisce e stampa la ricevuta
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-19
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-19
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/ritorna_numero_ricevuta.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$debug=false;
$fname=basename(__FILE__);
$table_name="ricevute";
$redirect="../php/emissione_ricevuta.php";
$print_target="../php/stampa_ricevuta.php";
$reloadTarget=null;
$parentForm=

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_anagrafica=0;
$sqlid_attpell=0;
$sqltipo='-';
$sqlcausale='';
$sqlimporto=0;
$sqlid_pagamento=0;
$sqln_ricevuta=0;
$sqln_ricevuta_tessera=0;
$sqltessera=0;
$tesseraNew=false; // Tessera Nuova o Rinnovo

$sqldata_ricevuta=date('Y-m-d');

$reloadWin=true;

$desc_sottosezione='';

$sqlselect_anagrafica  = "SELECT id, CONCAT(cognome, ' ',nome) nome
                                          FROM   anagrafica
                                          WHERE 1";

if(!$multisottosezione) {
   $sqlselect_anagrafica .= " AND id_sottosezione = " . $sott_app; 
 }

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

  if ($_POST) { // se post allora la chiamata alla funzione e' corretta
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                     switch($key) {

      		                     case "reload": // Reload parent (default false)
      					                    $reloadWin = $value;
      					                    break;

      		                     case "reloadTarget": // Reload target (in caso di emissione da associazione soci)
      					                    $reloadTarget = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                      case "id-hidden": // id anagrafica socio
      					                    $sqlid_anagrafica = $value;
      					                    break;

      		                      case "causale": // causale versamento
      					                    $sqlcausale = $value;
      					                    break;

      		                      case "id_pagamento": // tipo di pagamento
      					                    $sqlid_pagamento = $value;
      					                    break;

      		                      case "anno": //Anno di competenza
      					                    $sqlanno = $value;
      					                    break;

      		                      case "id_attpell": // identificativo attivita'/viaggio
      					                    $sqlid_attpell = $value;
      					                    break;

      		                      case "tipo": // 'A' = attivita' / 'V' = viaggio/pellegrinaggio
      					                    $sqltipo = $value;
      					                    break;

      		                      case "importo": // importo versamento
      					                    $sqlimporto = $value;
      					                    break;

      		                      case "numero_doc": // forzo numero documento
      					                    $sqln_ricevuta = $value;
      					                    break;

      		                      case "dt_doc": // forzo data documento
      					                    $sqldata_ricevuta = $value;
      					                    break;

      		                      case "tessera": // Nel totale la tessera e' pagata
      					                    $sqltessera = $value;
      					                    break;

      		                      case "new_t": // Tessera nuova o rinnovo
      					                    $tesseraNew = $value;
      					                    break;
                    }
                  $index++;
                  } // End foreach

// Verifico se l'attivita selezionata e' un rinnovo tessera
                  if(($sqltipo == 'A' && $sqlid_attpell > 0) && ($sqltessera == 0)) {
                  	   $sqlCheckRinnovo = "SELECT costi.id
                  	                                       FROM   costi
                  	                                       WHERE costi.tipo = 'A'
                  	                                       AND     id_attpell IN(SELECT id_attivita
                  	                                                                       FROM   attivita_m
                  	                                                                       WHERE id = $sqlid_attpell 
                  	                                     ) AND     id_parent = 0
                  	                                       AND     tessera = 1";
                       $resultCR = $conn->query($sqlCheckRinnovo);
                       if($resultCR->num_rows > 0) // Pagamento tessera
                          $sqltessera = 1;    	                                       
                  	                                    
                     }

                  $okCommit=true;
                  $conn->query('begin');
                   	
                  if($sqln_ricevuta == 0) {// Numerazione automatica
                      $sqln_ricevuta = ritorna_numero_ricevuta($conn, $sqlid_sottosezione);
                     }
                     
                  if($sqln_ricevuta > 0 && $sqlid_anagrafica > 0) {// Tutto OK! Verifico se devo emettere ricevuta per la tessera
                      if($sqltessera && $sqltipo == 'V') {
                      	   $sql = "SELECT SUM(costi_detail.costo*costi_detail.qta) totTessera,
                      	                             attivita_detail.id_attpell
                      	                FROM    costi_detail,
                      	                             attivita_detail,
                      	                             costi
                      	                WHERE  costi_detail.tessera = 1
                      	                AND      costi_detail.id_costo = costi.id
                      	                AND      costi_detail.tessera = costi.tessera
                      	                AND      costi_detail.id_parent = attivita_detail.id
                      	                AND      attivita_detail.tipo = 'A'
                      	                AND      attivita_detail.id_socio = $sqlid_anagrafica
                      	                AND      attivita_detail.id = costi_detail.id_parent
                      	                AND      attivita_detail.anno = " . date('Y') .
                      	                " GROUP BY 2";
                                                                                      
                      if($debug)
                          echo "$fname SQL Costi Tessera da inserire in ricevuta = $sql<br>";
                          
                      $rT = $conn->query($sql);

                      $importoTessera = 0;
                      $rsT = $rT->fetch_assoc();
                      	$importoTessera += $rsT["totTessera"];
                      	
                      	if($importoTessera > $sqlimporto) {
                      		$importoTessera = $sqlimporto;
                      	   }
                      
                      if($importoTessera > 0) { // Totale Positivo, inserisco ricevuta per la tessera
                          $sqlRice = "INSERT INTO ricevute (id_sottosezione,
                                                                                 n_ricevuta,
                                                                                 data_ricevuta,
                                                                                 id_socio,
                                                                                 n_socio,
                                                                                 id_gruppo,
                                                                                 id_acconto,
                                                                                 id_luogo,
                                                                                 id_attpell,
                                                                                 id_pagamento,
                                                                                 tipo,
                                                                                 causale,
                                                                                 importo,
                                                                                 tessera,
                                                                                 utente) VALUES
                                                                                (" . $sqlid_sottosezione . "," .
                                                                                     $sqln_ricevuta . ", '" .
                                                                                     $sqldata_ricevuta . "', " .
                                                                                     $sqlid_anagrafica . ",(
                                                                                     SELECT n_socio
                                                                                     FROM   anagrafica
                                                                                     WHERE id = " . $sqlid_anagrafica ."),(
                                                                                     SELECT id_gruppo_par
                                                                                     FROM   anagrafica
                                                                                     WHERE id = " . $sqlid_anagrafica ."),
                                                                                     0,
                                                                                     0," .
                                                                                     $rsT["id_attpell"] . ", " .
                                                                                     $sqlid_pagamento . ", 'A', '".
                                                                                     $conn->real_escape_string($sqlcausale) . "'," .
                                                                                     $importoTessera  . ", 1,  '" .
                                                                                     $conn->real_escape_string($current_user) . "')";
                                                                                     
                          if($debug)
                              echo "$fname SQL INSERT RICEVUTA TESSERAMENTO = $sqlRice<br>";

                          if(!($conn->query($sqlRice))) {// OPS!
         	                    echo mysqli_error($conn);
                              $conn->query('rollback'); 
                              $okCommit=false;              
                             }
                           else { // Prendo nuovo numero ricevuta e aggiorno il saldo (solo se > 0)
                              $sqln_ricevuta_tessera =  $sqln_ricevuta;
                              $sqlimporto -= $importoTessera;
                              
                              if($sqlimporto > 0)
                                  $sqln_ricevuta = ritorna_numero_ricevuta($conn, $sqlid_sottosezione);
                               }
                           }
                    	                
                          }
                     }
                  
                  if($sqln_ricevuta > 0 && $sqlid_anagrafica > 0 && $okCommit && $sqlimporto > 0) {// Tutto OK!
                      if($sqltipo == 'V')
                          $sqltessera = 0;
                          $sqlRice = "INSERT INTO ricevute (id_sottosezione,
                                                                                 n_ricevuta,
                                                                                 data_ricevuta,
                                                                                 id_socio,
                                                                                 n_socio,
                                                                                 id_gruppo,
                                                                                 id_acconto,
                                                                                 id_luogo,
                                                                                 id_attpell,
                                                                                 id_pagamento,
                                                                                 tipo,
                                                                                 causale,
                                                                                 importo,
                                                                                 tessera,
                                                                                 utente) VALUES
                                                                                (" . $sqlid_sottosezione . "," .
                                                                                     $sqln_ricevuta . ", '" .
                                                                                     $sqldata_ricevuta . "', " .
                                                                                     $sqlid_anagrafica . ",(
                                                                                     SELECT n_socio
                                                                                     FROM   anagrafica
                                                                                     WHERE id = " . $sqlid_anagrafica ."),(
                                                                                     SELECT id_gruppo_par
                                                                                     FROM   anagrafica
                                                                                     WHERE id = " . $sqlid_anagrafica ."),
                                                                                     0,
                                                                                     0," .
                                                                                     $sqlid_attpell . ", " .
                                                                                     $sqlid_pagamento . ", '" .
                                                                                     $sqltipo . "','" .
                                                                                     $conn->real_escape_string($sqlcausale) . "',
                                                                                     $sqlimporto, $sqltessera,  '" .
                                                                                     $conn->real_escape_string($current_user) . "')";
                                                                                     
                      if($debug)
                          echo "$fname SQL INSERT RICEVUTA = $sqlRice<br>";

                      if(!($conn->query($sqlRice))) {// OPS!
         	                echo mysqli_error($conn);
                         $conn->query('rollback');
                         $okCommit=false;               
                           }

                        }
                  if($okCommit) {// Tutto OK!
                      $conn->query('commit');
                      if($reloadWin)
                          echo "<script>window.opener.location.reload(true);</script>";

                      if($reloadTarget) {
                          echo "<script>window.opener.location.replace('" . $reloadTarget . "').reload(true);</script>";
                        }
          /*              echo $sqldata_ricevuta;
          echo date("Y", strtotime("$sqldata_ricevuta"));
          echo strtotime('" . $sqldata_ricevuta . "');
          echo "<br>";
          echo var_dump(date("Y", strtotime("$sqldata_ricevuta")));
          return;    */         
                      if(!$debug) {                     
                         echo "<form name='prn' action='" . $print_target . "' method='post'>";
                         echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";            
//                      echo "<input type='hidden' name='anno' value=" . date('Y') . ">";
                         echo "<input type='hidden' name='anno' value=" . date('Y', strtotime($sqldata_ricevuta)) . ">";
                         echo "<input type='hidden' name='id-hidden' value=$sqln_ricevuta>";
                         echo "<input type='hidden' name='n_ricTessera' value=$sqln_ricevuta_tessera>";
                         echo "</form>";
                         echo "<script>this.document.prn.submit();</script>";
                       }
                  }
             } // Fine POST 
