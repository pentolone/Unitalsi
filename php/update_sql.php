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
*  Update generic table
*
*  @file update_sql.php
*  @abstract Aggiorna la riga della tabella identificata dall'ID univoco
*  @author Luca Romano
*  @version 1.2
*  @time 2016-07-24
*  @history little bug fixing
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer > 2.6
*  @where Las Palmas de Gran Canaria
*
*
****************************************************************************************************/
require_once('../php/allega_file.php');
$debug=false;
$fname=basename(__FILE__);

$index=0;
$retPage = " ";
$sqlID=0;

$up_type="no upload";
$tmpFile='';
$maxSize=16777215; // In bytes (MEDIUMBLOB column in MySQL)
$size=0;

$updateStm = " ";
$okCommit=false;

if(($userid = session_check()) == 0)
     return;

config_timezone();
$current_user = ritorna_utente();

$conn = DB_connect();
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

      		                      case "utente": // ignoring
      				                        break;

      		                       case "up_type": // se presente e' possibile ci sia un allegato
      					                      $up_type = $value;
      					                       break;

      		                      case "table_name": // table_name
      					                    $tableName = $value;
		                                 $updateStm = "UPDATE " . $tableName . " SET ";
      				                        break;
      	
      		                      case "id": // Table ROW ID
      				                        $sqlID = $value;
      		                               break;

      		                       default: // Data to be update
      				                        $updateStm .= $key . " = ";
      		                               if($key == 'pwd') { // PASSWORD COLUMN
                 	                         $updateStm .= " PASSWORD('" . $conn->real_escape_string($value) . "'),";
      		          	                    }
      		                               else	       
      		                      		         if(!strlen($value)) // Se vuoto inserisco NULL
                 	                            $updateStm .= "NULL, ";
//                 	                         else
//                 	                             $updateStm .= "'" . $conn->real_escape_string($value) . "',";
                 	                else {
                 	                	if(TOUPPER) // Trasformo in maiuscolo se richiesto
                 	                      $updateStm .= "UPPER('" . $conn->real_escape_string($value) . "'),";
                 	                	else
                 	                      $updateStm .= "'" . $conn->real_escape_string($value) . "',";
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
    
    $fQuery = $updateStm .= "utente = '" . $conn->real_escape_string($current_user) .  "' WHERE ID = ". $sqlID;
    
    if($debug)
        echo $fname . ": SQL UPDATE = " . $fQuery . '<br>';
        
    if($conn->query($fQuery)) {
       $okCommit = true;
       $msg = "Dato aggiornato correttamente";
 
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
  	    $msg = "Dato NON aggiornato ERR = " . mysqli_error($conn);
       $okCommit=false;
    } 
    if($okCommit)   
        $conn->query("commit");  
    else   
        $conn->query("rollback");  
 
    if($debug)   
       echo $msg . $retPage;
    else   
       echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
  }
    $conn->close();
?>
</body>
</html>
