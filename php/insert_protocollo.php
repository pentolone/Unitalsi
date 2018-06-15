<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <LINK href="../css/chiamate.css" rel="stylesheet" type="text/css">
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
*  Gestione inserimento protocollo
*
*  @file insert_protocollo.php
*  @abstract Gestisce gli inserimenti delle righe del protocollo nelle tabelle
*  @author Luca Romano
*  @version 1.0
*  @time 2017-07-18
*  @history add the upload file capability during insert
*  
*  @first 1.0
*  @since 2017-07-18
*  @CompatibleAppVer All
*  @where Monza
*

*
****************************************************************************************************/
require_once('../php/allega_file.php');
require_once('../php/ritorna_numero_protocollo.php');
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

if(($userid = session_check()) == 0)
     return;

config_timezone();

$idSoc = ritorna_societa_id();
$current_user = ritorna_utente();
$conn = DB_connect();
$sqlid_sottosezione;
$sqltipo;
$sqlcodice;
$prog;

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

      				      case "id_sottosezione": // ID sottosezione
      					            $sqlid_sottosezione = $value;

      				      case "tipo": // tipo protocollo
      					            $sqltipo = $value;
     	
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

// Compongo codice protocollo
    $prog = ritorna_numero_protocollo($conn, $sqlid_sottosezione, $sqltipo);
    $sqlcodice = sprintf('%04d', $prog) . "-" . date('Y'); 
      					            
    $fQuery = $insertStm . " codice, data, utente) VALUES " . $valueList . "'" . $sqlcodice . "', now() , '" . $current_user . "')";

    if($debug)
       echo "$fname: SQL= $fQuery<br>";

    $conn->query("begin");         

    if($conn->query($fQuery)) { // Inserimento OK
       $okCommit = true;
       $sqlID = $conn->insert_id; // Prendo id della riga
       $msg = "Dato inserito correttamente codice protocollo = " . $sqlcodice;
 
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
        
    if($debug)    
        echo $msg;
    else
        echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
  }
  $conn->close();
?>
</body>
</html>

