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
*  Gestione inserimento dei dettagli attivita' / pellegrinaggio 
*
*  @file insert_attivita_detail.php
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
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
error_reporting(E_ALL ^ E_NOTICE);

$index=0;
$indexA=0;
$debug=true;
$fname=basename(__FILE__);
$print_target="../php/inserisci_e_stampa.php";

$insertStm='';
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

// Campi per aggiornare documento socio
$sqlid_doc=0;

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
           $kv[] = "$key=$value";
                     
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
                               $insertStm = "INSERT INTO " . $value . " (";
      					            break;

      		            case "x": // Nothing to do
      					           break;

      		            case "y": // Nothing to do
      					           break;


      		            case "sqlArray": // Array costi
             	            	   $sqlcostiArray = unserialize($value);
             	            	   if($debug)
             	            	       var_dump($sqlcostiArray);
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
                               
      		             case "tessera_included": // Nel totale la tessera e' pagata
      		                       if($value > 0) {
      					                 $sqltessera = 1;
     					                // Verifico se socio gia' inserito in tesseramento
      					                 $sql = "SELECT attivita_detail.id
      					                              FROM   attivita_detail
      					                              WHERE attivita_detail.id_sottosezione = $sqlid_sottosezione
      					                              AND     attivita_detail.id_socio             = $sqlid_socio
      					                              AND     attivita_detail.id_attpell IN(SELECT attivita_m.id
      					                                                                                    FROM  attivita_m,
      					                                                                                               attivita,
      					                                                                                                costi
      					                                                                                     WHERE costi.tessera = 1
      					                                                                                     AND     costi.id_attpell = attivita.id
      					                                                                                     AND     costi.tipo = 'A'
      					                                                                                     AND     attivita.id = attivita_m.id_attivita
      					                                                                                     AND     attivita_m.anno = $sqlanno)";   
                                    $rs = $conn->query($sql);
                                    
                                    if($rs->num_rows > 0) { // gia' presente in tesseramento
                                        $sqltessera  = 2;
                                       }
                                    else {
     					                // Prendo id attivita' di tesseramento
      					                    $sql = "SELECT attivita_m.id,
      					                                              attivita.descrizione,
      					                                              attivita_m.dal,
      					                                              attivita_m.al,
      					                                              costi.costo
      					                                FROM    attivita_m,
      					                                              attivita,
      			                                                    costi
      	    				                             WHERE  attivita_m.anno = " . date('Y') .
      		    			                           " AND      costi.tipo = 'A'
      			    		                              AND     costi.tessera = 1
      				    	                              AND     costi.id_attpell = attivita.id
      					                                 AND     attivita_m.id_attivita = attivita.id
      					                                 AND     attivita_m.id_sottosezione = $sqlid_sottosezione
      					                                 AND     costi.id_parent = 0";

                                      $rs = $conn->query($sql);
                                      $rw = $rs->fetch_assoc();
                                
                                     $sqlid_tessera = $rw["id"];
                                     $sqlcosto_tessera = $rw["costo"];
                                     $sql_descrizionetessera = $rw["descrizione"];
                                    }
                                }
   
      		            default: // Column values
      		                      $insertStm .= $key . ",";
      		                      if($key == 'pwd') { // PASSWORD COLUMN
                 	                $valueList .= " PASSWORD('" . $conn->real_escape_string($value) . "'), ";
      		          	            }
      		                      else {
      		                      		if(!strlen($value)) // Se vuoto inserisco NULL
                 	                   $valueList .= "NULL, ";
                 	                else {
                 	                	if(TOUPPER) // Trasformo in maiuscolo se richiesto
                 	                      $valueList .= "UPPER('" . $conn->real_escape_string($value) . "'), ";
                 	                	else
                 	                      $valueList .= "'" . $conn->real_escape_string($value) . "', ";
                 	                  }
                 	               }
      		        	            break;
      		               }  
      		    $index++;	  
         }

    $sql = "SELECT COUNT(*) c
                 FROM   attivita_detail
                 WHERE id_attpell = " . $sqlid_attpell .
               " AND     id_socio  =  " . $sqlid_socio .
               " AND     tipo  =  '" . $sqltipo . "'";               

    if($debug)
       echo "$fname: SQL check = $sql<br>";

    $rs = $conn->query($sql);
    $rw = $rs->fetch_assoc();
    if($rw["c"] > 0) { // Socio gia' associato
       echo "<script>avviso_no('Socio gia\' inserito');</script>";
    	     echo "<form id='ok' name='ok' action='../php/gestione_soci_attivita.php?tipo=" . $sqltipo . "' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
           return;
       }         
    
    $fQuery = $insertStm . " utente) VALUES " . $valueList . " '" . $current_user . "')";

    if($debug)
       echo "$fname: SQL= $fQuery<br>";

    $conn->query("begin");         

    if($conn->query($fQuery)) { // Inserimento OK
       $okCommit = true;
       $sqlID = $conn->insert_id; // Prendo id della riga per caricare i costi
       $msg = "Socio inserito correttamente";
 
    }
   else {
  	    $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
       $okCommit=false;
    } 
    if($okCommit) { // Inserisco i costi in array se presenti
        if($debug)
           echo "$fname inserisco eventuali costi principali associati<br>";

        for($index = 0; $okCommit && $index < count($sqlQta) ;$index++) {
        	     if($sqlQta[$index] == 0)
        	         continue;

        	     $sqltxt = $sqlText[$index];
        	     if(TOUPPER) {
        	     	  $sqltxt = strtoupper($sqltxt);
                 }
                 
               if($sqlcostiArray[$index][0] == 'm' )
                   $sqlprincipale=1;
                 
               if($sqlcostiArray[$index][0] == 's' )
                   $sqlprincipale=0;
                   
        	     $sql="INSERT INTO costi_detail(id_parent, qta, descrizione, principale, id_riduzione, costo, valore, utente)
        	               VALUES(" . $sqlID . ", " . $sqlQta[$index] . ", '" . $conn->real_escape_string($sqltxt) . "', " . $sqlprincipale . ", " .
        	               $sqlid_riduzione . ", " . $sqlValue[$index] . ", " .$sqlValoreRid . ",'" . $conn->real_escape_string($current_user) . "')";

                if($debug)
                    echo "$fname SQL costi = $sql<br>";
                    
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
    }
    else   
        $conn->query("rollback");
        
    if($okCommit && ($sql_tipo == 'V') && ($sqlid_tessera == 1)) {
    	  // Verifico se devo inserire il socio in attività Tesseramento
    	  $sql = "INSERT INTO attivita_detail (id_sottosezione, anno, id_attpell, tipo, id_socio)
    	               VALUES ($sqlid_sottosezione, $sqlanno, $sqlid_tessera,'A', $sqlid_socio)";

    	  if(!$conn->query($sql)) { // Inserimento KO
  	          $msg .= ". Socio NON inserito in tesseramento ERR = " . mysqli_error($conn);
             $okCommit=false;
            } 
        else {
        	    $p = $conn->insert_id;
    	       $sql = "INSERT INTO costi_detail (id_parent, qta, descrizione, costo, principale, tessera, id_riduzione)
    	                    VALUES ($p, 1, '" . $conn->real_escape_string($sql_descrizionetessera) . "',$sqlcosto_tessera,1,1,0)";
    	       if(!$conn->query($sql)) { // Inserimento KO
  	              $msg .= ". Socio NON inserito in tesseramento ERR = " . mysqli_error($conn);
                 $okCommit=false;
                } 
        	   else
                 $msg .= ". Socio inserito in tesseramento";
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
 //       onSubmit='window.location('" . $retPage . "');return false;'>'";
        echo "<input type='hidden' name='reloadTarget' value='" . $retPage . "'>";
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
        echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
        echo "<input type='hidden' name='id-hidden' value=" . $sqlid_socio . ">";
        echo "<input type='hidden' name='id_pagamento' value=" . $sqlid_pagamento . ">";
        echo "<input type='hidden' name='causale' value='" . $causale . "'>";
        echo "<input type='hidden' name='importo' value=" . $importo . ">";
        echo "<input type='hidden' name='tessera' value=" . $sqltessera . ">";
        echo "<input type='hidden' name='msg' value='" . $msg . "'>";
        echo "</form>"; 
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
}
  $conn->close();
?>
</body>
</html>

