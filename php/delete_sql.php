<?php
require_once('../php/unitalsi_include_common.php');

if(!check_key())
   return;
?>
<html>
<head>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

<script type="text/javascript" src="../js/messaggi.js"></script>

</head>

<?php
/****************************************************************************************************
*
*  Elimina la riga selezionata dalla tabella e le righe di allegati associati ad essa
*  (a meno di constraint che ne impedirebbero la cancellazione)
*
*  @file delete_sql.php
*  @abstract Elimina la riga selezionata
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-24
*  @history first release
*  
*  @first 1.0
*  @since 2017-01-24
*  @CompatibleAppVer >= 1.0
*  @where Monza
*
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__);

$index=0;
$deleteStm='';
$deleteAtt='';
$sqlForeign_id=0;
$sqlForeign_table='';

$retPage= " ";
$userid=0;
$idSoc=ritorna_societa_id();
$delOK=0;

$tableName=null;
$current_user = ritorna_utente();

if(($userid = session_check()) == 0)
    return(false);

config_timezone();

$conn = DB_connect();
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  if($_POST) {
    $kv = array();
    foreach ($_POST as $key => $value) {
                  $kv[] = "$key=$value";
                  switch($index) {
      		                    case 0: // redirect page
      				                         $retPage = $value;
      				                         break;

      		                     case 1: // table_name
      		                                $tableName = $value;
		                                   $deleteStm = "DELETE FROM " . $value . ' WHERE id= ';
		                                   $sqlForeign_table = $value;
      				                          break;
      	
      		                     case 2: // Table ROW ID
      		                                $deleteStm = $deleteStm .  $value ;
		                                   $sqlForeign_id = $value;
		                                   $sqlToDelete = $value;
      		                                break;
      
      		}  
      $index++;
    }
   	}
   else {
		echo $fname . ': Something is WRONG! NO POST REQUEST';
		return;   	
   	}
   	
   	// Statement SQL per eliminare gli allegati che potrebbero essere associati alla tabella
   $deleteAtt = "DELETE FROM attachments
                          WHERE  foreign_table = '" . $sqlForeign_table . "'
                          AND      id_societa      = ". $idSoc .
                         " AND     foreign_id      = " . $sqlForeign_id; 
   if($debug) {
       echo $fname . ': DELETE ATTACHMENTS = ' . $deleteAtt. '<br>';;
       echo $fname . ': DELETE ROW = ' . $deleteStm. '<br>';
//       return;
       }

    $conn->query('begin');
    $delOK += $conn->query($deleteAtt);
    
    if($delOK) {
         switch($tableName) {
         	
         	           case 'ricevute': // Inserisco in ricevute_eliminate
	            	           $sql = "INSERT INTO   ricevute_eliminate (id_sottosezione,
	                                                            tipo_doc,
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
	                                                            data_viaggio,
	                                                            causale,
	                                                            importo,
	                                                            tessera,
	                                                            utente)
	                    SELECT                            id_sottosezione,
	                                                            tipo_doc,
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
	                                                            data_viaggio,
	                                                            causale,
	                                                            importo,
	                                                            tessera,
	                                                            '" . $conn->real_escape_string($current_user) . "'
	                   FROM $tableName  WHERE id = $sqlToDelete";
			            $conn->query($sql);
			            
			            break;
         	          } // end switch
        if($conn->query($deleteStm)) { // 
            $conn->query('commit');
            echo '<script type="text/javascript">avviso("Dato eliminato correttamente","' . $retPage .'");</script>';       
           }
        else {
  	         $msg = "Impossibile eliminare il dato ERR = " . mysqli_error($conn);
            $conn->query('rollback');
             if($debug)
  	             echo $fname . ' ERROR = ' . $msg . '<br>';
             echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
            }  
         }  
        else {
  	         $msg = "Impossibile eliminare il dato ERR = " . mysqli_error($conn);
            $conn->query('rollback');
             if($debug)
  	             echo $fname . ' ERROR = ' . $msg. '<br>';
             echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
            }  
          
    $conn->close();
?>
</html>
