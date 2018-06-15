<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
/****************************************************************************************************
*
*  Gestione inserimento
*
*  @file insert_sql.php
*  @abstract Gestisce gli inserimenti delle righe nelle tabelle
*  @author Luca Romano
*  @version 1.2
*  @time 2016-07-26
*  @history add the upload file capability during insert
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer > 2.6
*  @where Las Palmas de Gran Canaria
*
*
*  N.B. questa funzione richiede in input gli elementi della tabella nell'ordine sequenziale (eccetto l'ID)
*  La tabella deve contenere, come ultimi due campi, la data e il campo utente, che vengono valorizzati qui.
*  Se un campo in tabella ha il nome 'pwd' viene inserito con l'opzione PASSWORD
*  Se un campo si chiama "cf" verifico che non sia gia' inserito e blocco il suo inserimento
*
*  Se no non funziona no no!
*
****************************************************************************************************/
require_once('../php/allega_file.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$index=0;
$debug=false;
$fname=basename(__FILE__);

$up_type="no upload";
$tmpFile='';
$maxSize=16777215; // In bytes (MEDIUMBLOB column in MySQL)
$size=0;
$insertStm='';
$okCommit=false;
$valueList='(';

$checkCF=null; // Controllo Codice Fiscale
$printAfterInsert=false; // Dopo l'inserimento posso richiedere la stampa della riga inserita

if(($userid = session_check()) == 0)
     return;

config_timezone();

$idSoc = ritorna_societa_id();
$current_user = ritorna_utente();
$conn = DB_connect();

echo "<script type='text/javascript' src='../js/messaggi.js'></script>";
// Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
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

      		            case "up_type": // se presente e' possibile ci sia un allegato
      					            $up_type = $value;
      					             break;

      				      case "table_name": // table_name
      					            $tableName = $value;
                               $insertStm = "INSERT INTO " . $tableName . " (";
      					            break;

      				      case "printRow": // Richiesta di stampa
      					            $printAfterInsert = true;
      					            break;
      	
      		            default: // Column values
      		                      $insertStm .= $key . ",";
      		                      if($key == 'cf') { // Codice Fiscale
      		                         $checkCF = $value;
      		                         }
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
         
     // Vediamo se esiste un file da caricare
    if(isset($_FILES['upload'])) {
       $tmpFile = $_FILES['upload']['tmp_name'];
       $fileType = $_FILES['upload']['type'];
       $size = $_FILES['upload']['size'];
      }

     if($tmpFile != '' && $size > 0) { // FILE to upload!
         $finfo = finfo_open(FILEINFO_MIME_TYPE);
         $mime = finfo_file($finfo, $tmpFile);
         $content = file_get_contents($tmpFile);
         finfo_close($finfo);

         if($debug) {
             echo $fname . ': File on disk ' . $tmpFile . '<br>';
             echo $fname . ': Type ' . $fileType . '<br>';
             echo $fname . ': Header ' . $mime . '<br>';
             echo $fname . ': Size ' . $size . '<br>';
             echo $fname . ': MimeType ' . $mime . '<br>';
           }
      }
      if($size > $maxSize) { // OPS!
         $msg = "Dimensione file = '" . $size ."' bytes > max = '" . $maxSize . "'. Impossibile caricare il file!";
          echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
          return(false);
    }
// Fine upload file

    if($checkCF) { // Controllo codice fiscale
        if($debug) {
        	  echo "$fname: Check CF = $checkCF<br>";
           }
        
        $sql = "SELECT cf FROM anagrafica WHERE LOWER(cf) = LOWER('$checkCF')";
        $r = $conn->query($sql);
        
       if($r->num_rows > 0) { // Dato gia' presente nel DB
           if($debug) {
        	      echo "$fname: CF = $checkCF gi&agrave; presente nel DB<br>";
               }
           $msg = "ATTENZIONE! Codice fiscale $checkCF gia presente nel DB\\nDato NON inserito!";
           echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
           return;
           } 
       }

    $fQuery = $insertStm . " data, utente) VALUES " . $valueList . " now() , '" . $current_user . "')";

    if($debug)
       echo "$fname: SQL= $fQuery<br>";

    $conn->query("begin");         

    if($conn->query($fQuery)) { // Inserimento OK
       $okCommit = true;
       $sqlID = $conn->insert_id; // Prendo id della riga
       $msg = "Dato inserito correttamente";
 
    	 if($size > 0) { // Inserisco anche il file
    	     if(allega_file($sqlID, $tableName, $current_user, $content, $up_type, $mime)) {
               $msg .= "\\nDocumento '" . $up_type .  "' allegato correttamente!";
               
    	 	    }
       else {
  	        $msg = "Allegato NON inserito ERR = " . mysqli_error($conn);
           $okCommit=false;
    	 	}
    	}
    }
   else {
  	    $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
       $okCommit=false;
    } 
    if($okCommit)   
        $conn->query("commit");  
    else   
        $conn->query("rollback");  
        
   $conn->close();
    if($debug)    
        echo $msg;
    else {
        
        // Se devo stampare stampo la riga appena inserita
        if($printAfterInsert) { 
            echo "<script>window.opener.location.reload(true);</script>";
        	  echo "<form name='printLast' action='../php/stampa_riga_inserita.php' method='POST'>";
        	  echo "<input type='hidden' name='tablename' value='" . $tableName . "'>";
        	  echo "<input type='hidden' name='id' value=$sqlID>";
        	  echo "</form>";
        	  echo "<script>this.document.printLast.submit();</script>"; 
           }
        else {
           echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
          }
     }
  }
?>
