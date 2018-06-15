<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <META HTTP-EQUIV="Expires" CONTENT="-1">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  
</head>
<body>
<?php
/****************************************************************************************************
*
*  Gestione inserimento/modifica dei dettagli attivita' / pellegrinaggio 
*
*  @file manage_attivita_detail.php
*  @abstract Gestisce gli inserimenti dei costi attivita / pellegrinaggio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-26
*  @history first release
*  
*  @first 1.0
*  @since 2017-02-26
*  @CompatibleAppVer All
*  @where Monza
*
*
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
error_reporting(E_ALL ^ E_NOTICE);

$sqlID=0; // ID attivita' detail
$index=0;
$indexA=0;
$print_target="../php/inserisci_e_stampa.php";

$insertStm='';
$updateStm='';
$okCommit=false;
$valueList='(';

$sqlQta=array();
$sqlValue=array();
$sqlText=array();
$sqltipo='A';
$sql='';
$sqltxt='';
$sqlIDParent=0;
$emetti=false; // Se true emetto ricevuta
$causale='';
$importo=0.00;
$sqlValoreRid=0.00;
$sqlid_categoria=0; // Categoria nel viaggio/pellegrinaggio
$sqlid_servizio=0; // Servizio nel viaggio/pellegrinaggio

$removeRow=false; 
$removeDtl=array(); // Se valorizzato richiedo la rimozione dei costi socio da attivita'/Viaggio
$sqlToDelete = " IN(";

// Dati di contatto
$sqlcellulare=null;
$sqlemail=null;
$sqltelefono_rif=null;

// Tessera
$sqlAddTessera=false; // Se true pagata anche la tessera
$sqlNewTessera=0; // 0 = Rinnovo, 1 = Nuova
$sqlcosto_tessera=array();
$sqlqta_tessera=array();
$sqlp_tessera=array();
$sqlid_costo_parent=array();
$sqlid_text_tessera = array();
$ix_tessera=0;

// Campi per aggiornare documento socio
$sqlid_doc=0;
$sqlid_cittadinanza=0;
$sqlid_luogo_rilascio=0;

// Campi per inserire periodo di permanenza socio
$sqlUdal = null;
$sqlUal = null;

if(($userid = session_check()) == 0)
     return;

config_timezone();

$current_user = ritorna_utente();
$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  
  // Controllo se il socio non sia stato gia' inserito da altro utente
  mysqli_set_charset($conn, "utf8");
  if ($_POST) {
    $kv = array();
    foreach ($_POST as $key => $value) {
    	
    	     if($key == "removeDtl") { // Array per cancellazione
    	         $i_array=0;
    	         
      		      foreach($value as $value1) {
                             if($debug) {
                                 echo $fname . ": KEY (Array) = " . $key . '<br>';
                                 echo $fname . ": VALUE = $value1<br>"; 
                                 echo $fname . ": INDEX (Array) = " . $i_array . '<br>';                    	
                	
                                }
                             $removeDtl[$i_array] = $value1;
                             $sqlToDelete .= "$value1, "; 	
                             $i_array++;
                 	         }
                 	         
                $sqlToDelete = rtrim($sqlToDelete, " ,") . ")";
                
                if($debug) {
                	 echo "$fname: ID remove $sqlToDelete<br>";
                   }
      			   continue;
    	        }
    	     else {
               $kv[] = "$key=$value";
               }  
                     
           if($debug) {
                echo $fname . ": KEY = " . $key . '<br>';
                echo $fname . ": VALUE = " . $value . '<br>';
                echo $fname . ": INDEX = " . $index . '<br><br>';                    	
               }

            switch($key) {
      		            case "redirect": // redirect page
      					            $retPage = $value;
      					             break;

      				      case "table_name": // table_name
      					            $tableName = $value;
                               $insertStm = "INSERT INTO " . $tableName . " (";
                               $updateStm = "UPDATE " . $tableName . " SET ";
      					            break;

      		            case "sqlArray": // Array costi
             	            	   $sqlcostiArray = unserialize($value);
             	            	   if($debug)
             	            	       var_dump($sqlcostiArray);
      					             break;

      		            case "new_t": // Tipo Tessera Nuova o Rinnovo
      					            $sqlNewTessera = $value;
      					            break;

      		            case "emetti": // richiesta di emissione ricevuta
      					            $emetti = $value;
      					            break;

      		            case "causale": // causale ricevuta
      					            $causale = $value;
      					            break;

      		            case "importo": // importo ricevuta
      					            $importo = $value;
      					            break;

      		            case "id_pagamento": // tipo pagamento
      					            $sqlid_pagamento = $value;
      					            break;

      		            case "id_riduzione": // eventuale riduzione
      					            $sqlid_riduzione = $value;
      					            break;

      		            case "valore-rid": // eventuale valore riduzione
      					            $sqlValoreRid = $value;
      					            break;

      		            case "Ucellulare": // Cellulare socio
      					            $sqlcellulare = $value;
      					            break;

      		            case "Uemail": // Mail socio
      					            $sqlemail = $value;
      					            break;

      		            case "Utelefono_rif": // Telefono riferimento socio
      					            $sqltelefono_rif= $value;
      					            break;

      		            case "Uid_doc": // Aggiorno documento socio
      					            $sqlid_doc = $value;
      					            break;

      		            case "Un_doc": // Aggiorno documento socio
      					            $sqln_doc = $value;
      					            break;

      		            case "Udata_ril": // Aggiorno documento socio
      					            $sqldata_ril = $value;
      					            break;

     		            case "Udata_exp": // Aggiorno documento socio
      					            $sqldata_exp = $value;
      					            break;

     		            case "Uid_cittadinanza": // Aggiorno documento socio
      					            $sqlid_cittadinanza = $value;
      					            break;

     		            case "Uid_luogo_rilascio": // Aggiorno documento socio
      					            $sqlid_luogo_rilascio = $value;
      					            break;

     		            case "o": // Ignore it
      					            break;
 
                      case 'removeIt': // Se valorizzato richiesta cancellazione
                              $removeRow=true;
                              break;
     	
      				      case "txt": // descrizione
                               $i=0;
                               while(isset($value[$i])) {
                                         $sqlText[$i] = $value[$i];
                                         if($debug)
                                             echo $fname . " TEXT = " . $sqlText[$i] . '<br>';
                                         $i++;
                                        }     
      					            break;
      	
      				      case "valore": // Valore
                               $i=0;
                               while(isset($value[$i])) {
                                         $sqlValue[$i] = $value[$i];
                                         if($debug)
                                             echo $fname . " COST = " . $sqlValue[$i] . '<br>';
                                         $i++;
                                        }     
      					            break;
      	
      				      case "qta": // array delle quantita'
                               $i=0;
                               while(isset($value[$i])) {
                                         $sqlQta[$i] = $value[$i];
                                         if($debug)
                                             echo $fname . " QTA = " . $sqlQta[$i] . '<br>';
                                         $i++;
                                        }     
      					            break;

                      case 'tipo':
                              $sqltipo=$value;

                      case 'id_sottosezione':
                              $sqlid_sottosezione=$value;

                      case 'anno':
                              $sqlanno=$value;

                      case 'id_attpell':
                              $sqlid_attpell=$value;

                      case 'id_socio':
                              $sqlid_socio=$value;
                               
      		            default: // Column values
      		                      $insertStm .= $key . ",";
      		                      $updateStm .= $key . " = ";
      		                      if($key == 'pwd') { // PASSWORD COLUMN
                 	                $valueList .= " PASSWORD('" . $conn->real_escape_string($value) . "'), ";
                 	                $updateStm .= "  PASSWORD('" . $conn->real_escape_string($value) . "'), ";
      		          	            }
      		                      else {
      		                      		if(!strlen($value)) { // Se vuoto inserisco NULL
                 	                   $valueList .= "NULL, ";
                 	                   $updateStm .= "NULL, ";
                 	                   }
                 	                else {
                 	                	if(TOUPPER) { // Trasformo in maiuscolo se richiesto
                 	                      $valueList .= "UPPER('" . $conn->real_escape_string($value) . "'), ";
                 	                      $updateStm .= "UPPER('" . $conn->real_escape_string($value) . "'), ";
                 	                      }
                 	                	else {
                 	                      $valueList .= "'" . $conn->real_escape_string($value) . "', ";
                 	                      $updateStm .= "'" . $conn->real_escape_string($value) . "', ";
                 	                   }
                 	                  }
                 	               }
      		        	            break;
      		               }  
      		    $index++;	  
         }

    $sqltessera = 0;
    if($sqltipo == 'A') { // Verifico se attivita' di tesseramento
        $sql = "SELECT attivita_m.id
                     FROM   attivita_m
                     WHERE attivita_m.id = $sqlid_attpell
                     AND     attivita_m.id_attivita IN(SELECT attivita.id
                                                                          FROM   attivita,
                                                                                      costi
                                                                           WHERE costi.tessera = 1
                                                                          AND    attivita.id = costi.id_attpell)";
       if($debug) {
       	 echo "$fname: SQL att tess = $sql<br>";
          }       
       $cT = $conn->query($sql);
       if($cT->num_rows > 0) { // Tesseramento
           $sqltessera = 1;
         }
                    
       }

    if($removeRow) { // Richiesta la rimozione della singola attività
    
       if($sqlToDelete == " IN(") { // Nessuna riga selezionata
    	    echo "<form id='remove_none' name='remove_none' action='../php/gestione_soci_attivita.php?tipo=" . $sqltipo . "' method='post'>";
    	    echo "<input type='hidden' name='msg' value='Nessuna riga selezionata'>";
    	    echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	    echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	    echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	    echo "</form>";
    	
    	    if(!$debug)
              echo "<script type='text/javascript'>document.forms['remove_none'].submit();</script>"; 
   	       return;
   	      }

       $okCommit=true;
       $conn->query("begin");
       // Cancello da occupazione
       $sql = "DELETE FROM AL_occupazione
                    WHERE   id_socio IN(SELECT id_socio
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete
                                                    AND      id_attpell IN(SELECT id_attpell
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete
                                                    AND     tipo = '" . $sqltipo . "'))";

       if($debug) {
           echo "$fname SQL remove (occupazione) $sql<br>";
           }
       if(!$conn->query($sql))  {   // Failed
   	        $msg = "Errore remove (occupazione) = " . mysqli_error($conn);
           $okCommit = false;
          }
       // Cancello da bus
       $sql = "DELETE FROM mezzi_detail
                    WHERE   id_socio IN(SELECT id_socio
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete)
                    AND      id_attpell IN(SELECT id_attpell
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete
                                                    AND     tipo = '" . $sqltipo . "')";

       if($debug) {
           echo "$fname SQL remove (mezzi) $sql<br>";
           }
       
       if($okCommit)  { 
           if(!$conn->query($sql))  {   // Failed
   	            $msg = "Errore remove (mezzi) = " . mysqli_error($conn);
               $okCommit = false;
              }
          }
       // Cancello da abbinamenti
       $sql = "DELETE FROM accompagnatori
                    WHERE  (id_accompagnato IN (SELECT id_socio
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete)
                     OR       (id_accompagnatore IN (SELECT id_socio
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete)))
                    AND      id_attpell IN(SELECT id_attpell
                                                    FROM   attivita_detail
                                                    WHERE id $sqlToDelete
                                                    AND     tipo = '" . $sqltipo . "')";

       if($debug) {
           echo "$fname SQL remove (abbinamenti) $sql<br>"; 
           }
       
       if($okCommit)  { 
           if(!$conn->query($sql))  {   // Failed
   	            $msg = "Errore remove (abbinamenti) = " . mysqli_error($conn);
               $okCommit = false;
              }
          }

       // Inserisco in tabella ritiri
       $sql = "INSERT INTO ritiri_detail (id_sottosezione,
                                                            anno,
                                                            id_attpell,
                                                            tipo,
                                                            id_socio,
                                                            dal,
                                                            al,
                                                            id_categoria,
                                                            id_servizio,
                                                            tipo_viaggio,
                                                            utente)
                    SELECT                            id_sottosezione,
                                                            anno,
                                                            id_attpell,
                                                            tipo,
                                                            id_socio,
                                                            dal,
                                                            al,
                                                            id_categoria,
                                                            id_servizio,
                                                            tipo_viaggio,
                                                            '" . $conn->real_escape_string($current_user) . "'
                   FROM attivita_detail WHERE id = $sqlToDelete";

       if($debug) {
           echo "$fname SQL inserisci (ritiri) $sql<br>"; 
          }

       if($okCommit)  { 
           if(!$conn->query($sql))  {   // Failed
   	            $msg = "Errore inserimento (ritiri) = " . mysqli_error($conn);
               $okCommit = false;
              }
          }
       
       // Cancello da attivita
        $sql = "DELETE FROM attivita_detail
                    WHERE  id $sqlToDelete";

       if($debug) {
           echo "$fname SQL remove (attivita) $sql<br>";
           }

       if($okCommit)  { 
           if(!$conn->query($sql))  {   // Failed
   	            $msg = "Errore remove (attivita) = " . mysqli_error($conn);
               $okCommit = false;
              }
          }
           
  	    if($okCommit) { // Tutto OK
  	        $conn->query("commit");
  	        $msg = "Socio rimosso correttamente";
          }
          else
  	         $conn->query("rollback");

    	echo "<form id='remove_ok' name='remove_ok' action='../php/gestione_soci_attivita.php?tipo=" . $sqltipo . "' method='post'>";
    	echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	echo "</form>";
    	
    	if(!$debug)
           echo "<script type='text/javascript'>document.forms['remove_ok'].submit();</script>"; 
   	    return;
   	 }
   	 
   	 // Fine funzionalita' di rimozione dati attivita'/pellegrinaggio

    $sql = "SELECT attivita_detail.id c
                 FROM   attivita_detail
                 WHERE id_attpell = $sqlid_attpell
                 AND     id_socio  =   $sqlid_socio
                 AND     tipo  =  '" . $sqltipo . "'";               

    if($debug)
       echo "$fname: SQL check = $sql<br>";

    $rs = $conn->query($sql);
    $rw = $rs->fetch_assoc();
    if($rw["c"]  && $sqltipo == 'V') { // Socio gia' associato, aggiorno i dati
        $sqlID = $rw["c"];
        $fQuery = $updateStm . " utente = " . "'" . $current_user . "'";
        $fQuery .= " WHERE id = $sqlID";
     
     /*  echo "<script>avviso_no('Socio gia\' inserito');</script>";
    	 echo "<form id='ok' name='ok' action='../php/gestione_soci_attivita.php?tipo=" . $sqltipo . "' method='post'>";
    	 echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	 echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	 echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	 echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	 echo "</form>";
       echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
       return; */
       
       
       }
    else  { // inserisco
    
         $fQuery = $insertStm . " utente) VALUES " . $valueList . " '" . $current_user . "')";
        }

    if($debug)
       echo "$fname: SQL per attivita detail = $fQuery<br>";
//       return;

    $conn->query("begin");         

    if($conn->query($fQuery)) { // Inserimento/aggiornamento OK 
       $okCommit = true;
              
       if($sqlID == 0) {
           $sqlID = $conn->insert_id; // Prendo id della riga per caricare i costi
           $msg = "Socio inserito correttamente";
       }
       else {
           $msg = "Socio aggiornato correttamente";
          }
 
    }
   else {
  	    $msg = "Dato NON inserito/aggiornato ERR = " . mysqli_error($conn);
       $okCommit=false;
    } 
    if($okCommit) { // Elimino e inserisco i costi in array se presenti
        // Elimino i costi
        $sql = "DELETE FROM costi_detail
                     WHERE  id_parent = $sqlID";
                     
        if($debug) {
           echo "$fname elimino i costi associati $sql<br>";
           echo "$fname inserisco eventuali costi principali associati<br>";
          }

        if($conn->query($sql))
            $okCommit = true;
        else
            $okCommit = false;

        $sqlAddTessera=0;
        for($index = 0; $okCommit && $index < count($sqlQta) ;$index++) {

        	     if($sqlcostiArray[$index][0] == 't' && (($sqlQta[$index] > 0) || ($sqlcostiArray[$index][2] == 1))) {
        	         $sqltessera=0;
//       	     	   $sqltessera = 1;
        	     	  if($sqlQta[$index] > 0) {
        	     	      $sqlAddTessera=true;
        	     	     }
        	     	  $sqlcosto_tessera[$ix_tessera] = $sqlValue[$index];
        	     	  
        	     	  if($sqlcostiArray[$index][2] == 1)  {
        	     	 // 	  if($sqlQta[$index] == 0)
        	     	  //       $sqlQta[$index] = 1;
        	     	     
        	     	     if($sqlValue[$index] > 0)
        	     	        $sqlAddTessera=true;
        	     	    }
        	     	  $sqlqta_tessera[$ix_tessera] = $sqlQta[$index];
        	     	  $sqlp_tessera[$ix_tessera] = $sqlcostiArray[$index][2];
        	     	  $sqlid_costo_parent[$ix_tessera] = $sqlcostiArray[$index][3];
        	     	  $sqltext_tessera[$ix_tessera] = $sqlText[$index]; 

        	     	  if($debug)
        	     	     echo "$fname: Richiesta anche tessera ix = ($ix_tessera) <br>";
        	     	  $ix_tessera++;
        	     	  continue;
        	        }

              if($sqlQta[$index] == 0)
                  continue;

        	     $sqltxt = $sqlText[$index];
        	     $sqlid_costo = $sqlcostiArray[$index][3];
        	     if(TOUPPER) {
        	     	  $sqltxt = strtoupper($sqltxt);
                 }
                 
               if($sqlcostiArray[$index][0] == 'm')
                   $sqlprincipale=1;
                 
               if($sqlcostiArray[$index][0] == 's' )
                   $sqlprincipale=0;
                   
        	     $sql="INSERT INTO costi_detail(id_parent, id_costo, qta, descrizione, principale, id_riduzione, costo, 
        	                                                        valore, tessera, utente)
        	               VALUES(" . $sqlID . ", $sqlid_costo, " . $sqlQta[$index] . ", '" . $conn->real_escape_string($sqltxt) . "', " . $sqlprincipale . ", " .
        	               $sqlid_riduzione . ", " . $sqlValue[$index] . ", " .$sqlValoreRid . ", " . $sqltessera . ",'" . 
        	               $conn->real_escape_string($current_user) . "')";

                if($debug)
                    echo "$fname SQL costi (ciclo) = $sql<br>";
                    
                $sqlValoreRid=0.00;
                $sqlid_riduzione=0;
   
                if(!$conn->query($sql)) { // Inserimento KO
  	                $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
                   $okCommit=false;
                   break;
                  } 
         }
    }
    else  {
        $conn->query("rollback");  
        $okCommit = false;
     }

    if($okCommit) {
    	  // Verifico se devo aggiornare dati documento socio
    	  if($sqlid_doc > 0) { // Aggiorno dati documento socio
    	      $sql = "UPDATE anagrafica SET id_tipo_doc = " . $sqlid_doc . ",
    	                                                         n_doc = '" . $conn->real_escape_string($sqln_doc) . "',
    	                                                         data_ril = '" . $sqldata_ril . "',
    	                                                         data_exp = '"  . $sqldata_exp . "',
    	                                                         utente = '" . $conn->real_escape_string($current_user) . "'
    	                   WHERE id = " . $sqlid_socio;

    	      if($debug)
    	         echo "$fname UPDATE DOC = $sql<br>";

    	      if(!$conn->query($sql)) { // Inserimento KO
  	             $msg = "Documento socio non aggiornato ERR = " . mysqli_error($conn);
                $okCommit=false;
               } 
             else {
             	$msg .= ". Aggiornato documento socio";
             }
          }                                 
        if($okCommit && $sqlid_cittadinanza > 0) { // Verifico se devo aggiornare cittadinanza
    	      $sql = "UPDATE anagrafica SET id_cittadinanza = $sqlid_cittadinanza
    	                   WHERE  id = $sqlid_socio";

    	      if($debug)
    	         echo "$fname UPDATE CITTADINANZA = $sql<br>";
    	      if(!$conn->query($sql)) { // Aggiornamento KO
  	             $msg = "Cittadinanza socio non aggiornata ERR = " . mysqli_error($conn);
                $okCommit=false;
               } 
    	   }

        if($okCommit && $sqlid_luogo_rilascio > 0) { // Verifico se devo aggiornare luogo rilascio
    	      $sql = "UPDATE anagrafica SET id_luogo_rilascio = $sqlid_luogo_rilascio
    	                   WHERE  id = $sqlid_socio";

    	      if($debug)
    	         echo "$fname UPDATE LUOGO RILASCIO = $sql<br>";
    	      if(!$conn->query($sql)) { // Aggiornamento KO
  	             $msg = "Luogo rilascio documento socio socio non aggiornata ERR = " . mysqli_error($conn);
                $okCommit=false;
               } 
    	   }
    }
    else   
        $conn->query("rollback");

    if($okCommit) { // Aggiorno informazioni di contatto se valorizzate
    	 $sql = "UPDATE anagrafica SET ";
    	 if(rtrim($sqlcellulare)) // Valid
    	    $sql .= "cellulare = '" . $conn->real_escape_string($sqlcellulare) . "', ";

    	 if(rtrim($sqlemail)) // Valid
    	    $sql .= "email = '" . $conn->real_escape_string($sqlemail) . "', ";

    	 if(rtrim($sqltelefono_rif)) // Valid
    	    $sql .= "telefono_rif = '" . $conn->real_escape_string($sqltelefono_rif) . "',";

       if($sql != "UPDATE anagrafica SET ") { // Aggiorno contatti socio
    	    $sql .= "utente = '" . $conn->real_escape_string($current_user) . "'
    	    WHERE id = " . $sqlid_socio;

    	    if($debug)
    	        echo "$fname UPDATE DOC = $sql<br>";

    	    if(!$conn->query($sql)) { // Inserimento KO
  	           $msg = "Dati contatto socio non aggiornati ERR = " . mysqli_error($conn);
               $okCommit=false;
              } 
          else {
             	  $msg .= ". Aggiornati contatti socio";
                }                                   
         }
      }

    if($okCommit && $sqltipo == 'V' && $sqlAddTessera) {
    	
    	 $sqlid_tessera=0;
    	// Prendo id attivita' di tesseramento
       $sql = "SELECT attivita_m.id,
      					              attivita.descrizione,
      					              attivita_m.dal,
      					              attivita_m.al,
      					              costi.costo,
      					              costi.id id_costo
      					 FROM   attivita_m,
      					              attivita,
      			                    costi
      	    		    WHERE  attivita_m.anno = $sqlanno
      		          AND      costi.tipo = 'A'
      			       AND     costi.tessera = 1
      				    AND     costi.id_attpell = attivita.id
      					 AND     attivita_m.id_attivita = attivita.id
      					 AND     attivita_m.id_sottosezione = $sqlid_sottosezione
      					 AND     costi.id_parent = 0";

       if($debug)
           echo "$fname: SQL Attivit&agrave; tesseramento = $sql<br>";

       $rs = $conn->query($sql);      
       if($rs->num_rows > 0) { // Attivita' trovata
           $rw = $rs->fetch_assoc();
           $sqlid_tessera =  $rw["id"];
           $sqlid_costo_tessera =  $rw["id_costo"];
           $sqltessera_dal = $rw["dal"];
           $sqltessera_al = $rw["al"];          
           $sqltessera_descrizione = $rw["descrizione"]; 
           $rs->close(); 
           
           // Verifico se socio gia' presente
           $sql = "SELECT attivita_detail.id
                        FROM   attivita_detail
                        WHERE attivita_detail.id_attpell = $sqlid_tessera
                        AND     attivita_detail.id_socio   = $sqlid_socio
                        AND     attivita_detail.tipo = 'A'";        
           if($debug)
               echo "$fname: SQL Controllo tesseramento = $sql<br>";

          $rs = $conn->query($sql);      
           if($rs->num_rows > 0) { // Socio gia' presente
               $rsd = $rs->fetch_assoc();
               $p = $rsd["id"];
               $sqlid_tessera = 0;
            }
          }

       if($sqlid_tessera > 0) { // Inserisco socio in attivita' di tesseramento (se NON gia' presente)

    	    $sql = "INSERT INTO attivita_detail (id_sottosezione, anno, id_attpell, tipo,
    	                                                             dal, al, nuova, id_socio, utente)
    	                 VALUES ($sqlid_sottosezione, $sqlanno, $sqlid_tessera,'A', '" .
    	                               $sqltessera_dal . "', '" .$sqltessera_al . "', $sqlNewTessera, $sqlid_socio, '" . $conn->real_escape_string($current_user) . "')";

           if($debug)
               echo "$fname: SQL Inserimento in tesseramento = $sql<br>";

    	    if(!$conn->query($sql)) { // Inserimento KO
  	            $msg .= ". Socio NON inserito in tesseramento ERR = " . mysqli_error($conn);
               $okCommit=false;
               } 
          else {
        	    $p = $conn->insert_id;
             $msg .= ". Socio inserito in tesseramento";
            }
         }
         
        $sql = "DELETE FROM costi_detail WHERE id_parent = $p";

        if($debug)
            echo "$fname: SQL cancellazione preventiva dei costi = $sql<br>";
        
        $conn->query($sql);
        for($i=0; $i < count($sqlcosto_tessera) && $okCommit; $i++) {
    	        $sql = "INSERT INTO costi_detail (id_parent, id_costo, qta, descrizione, costo, principale, tessera, id_riduzione, utente)
    	                    VALUES ($p, $sqlid_costo_parent[$i], $sqlqta_tessera[$i], '" . $conn->real_escape_string($sqltext_tessera[$i]) .
    	                                   "',$sqlcosto_tessera[$i],$sqlp_tessera[$i],1,0, '" . 
    	                                   $conn->real_escape_string($current_user) . "')";

               if($debug)
                    echo "$fname: SQL Inserisco i costi Attivit&agrave; tesseramento = $sql<br>";
    	         if(!$conn->query($sql)) { // Inserimento KO
  	                $msg .= ". Socio NON inserito in tesseramento ERR = " . mysqli_error($conn);
                   $okCommit=false;
                  } 
             } // End for
               
          }
      }
      
    if($okCommit)
        $conn->query("commit");

    if($okCommit && $emetti==1) { // emetto ricevuta
        $retPage .= "&id_sottosezione=" . $sqlid_sottosezione .
                            "&id_attpell=" . $sqlid_attpell .
                            "&anno=" . $sqlanno .
                            "&msg=" . $msg .". Emessa ricevuta.";
        echo "<form id='ric' action='" .$print_target . "' method='POST' target='_blank'>";
        echo "<input type='hidden' name='reloadTarget' value='" . $retPage . "'>";
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
        echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
        echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
        echo "<input type='hidden' name='id-hidden' value=" . $sqlid_socio . ">";
        echo "<input type='hidden' name='id_pagamento' value=" . $sqlid_pagamento . ">";
        echo "<input type='hidden' name='causale' value='" . $causale . "'>";
        echo "<input type='hidden' name='importo' value=" . $importo . ">";
        echo "<input type='hidden' name='tessera' value=" . $sqlAddTessera . ">";
        echo "<input type='hidden' name='new_t' value=$sqlNewTessera>";
        echo "<input type='hidden' name='msg' value='" . $msg . "'>";
        echo "</form>"; 
        if(!$debug)
            echo "<script>document.getElementById('ric').submit();</script>";

        }
        
    if($debug)    
        echo $msg;
    else {
    	     echo "<form id='ok' name='ok' action='../php/gestione_soci_attivita.php?tipo=" . $sqltipo . "' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
          }
  $conn->close();
?>
</body>
</html>

