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
*  Gestione inserimento/modifica dei costi attivita' / pellegrinaggio 
*
*  @file insert_attivita_costi.php
*  @abstract Gestisce gli inserimenti dei costi attivita / pellegrinaggio
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-24
*  @history first release
*  
*  @first 1.0
*  @since 2017-02-24
*  @CompatibleAppVer All
*  @where Monza
*
*
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$index=0;
$indexA=0;
$debug=false;
$fname=basename(__FILE__);

$insertStm='';
$updateStm='';
$deleteStm='';

$okCommit=false;
$valueList='(';

$sqlcosti=array();
$id_riduzione=0;
$sqltipo='A';
$sql='';
$sqltxt='';
$sqlIDParent=0;
$sqltessera=false;
$sqlID=0;
$result=false;
$arrayAlive=array(); // Array per eliminare eventuali voci costo eliminate
$iArrayAlive=0;

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

      				      case "table_name": // table_name
      					            $tableName = $value;
                               $insertStm = "INSERT INTO " . $value . " (";
                               $updateStm = "UPDATE " . $tableName . " SET ";
      					            break;
      	
      				      case "id-hidden": // ID riga att/pell (UPDATE)
          					      $sqlID = $value;
      					            break;
      	
      				      case "costiArray": // array dei costi
          					      $sqlcosti = unserialize($value);
      					            break;

      				      case "tipo": // tipo ('A' = attivita' 'V' = viaggio/pellegrinaggio)
          					      $sqltipo = $value;
          					      break;

      				      case "id_riduzione": // id_riduzione
          					      $sqlid_riduzione = $value;
          					      

      		            default: // Column values
      		                      $insertStm .= $key . ",";
      		                      $updateStm .= $key . " = ";
      		                      if($key == 'pwd') { // PASSWORD COLUMN
                 	                $valueList .= " PASSWORD('" . $conn->real_escape_string($value) . "'), ";
                 	                $updateStm .= " PASSWORD('" . $conn->real_escape_string($value) . "'), ";
      		          	            }
      		                      else {
      		                      		if(!strlen($value)) { // Se vuoto inserisco NULL
                 	                   $valueList .= "NULL, ";
                 	                   $updateStm .= "NULL, ";
      		          	               }
                 	                else {
                 	                	if(TOUPPER) {// Trasformo in maiuscolo se richiesto
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
    $fQuery = $insertStm . " data, utente) VALUES " . $valueList . " now() , '" . $current_user . "')";
    $updateStm .= "utente = '" . $conn->real_escape_string($current_user). "'";
    $updateStm .= " WHERE id = " .$sqlID;

    if($sqlID > 0) { // Update
       $fQuery = $updateStm;
       $update=true;
      	}  

    if($debug) {
       echo "$fname: SQL Exec = $fQuery<br>";
       echo "$fname: SQL Update = $updateStm<br>";
      	}  

    $conn->query("begin");         

    if($conn->query($fQuery)) { // Inserimento/aggiornamento OK
       $okCommit = true;
       
       if($sqlID == 0)
          $sqlID = $conn->insert_id; // Prendo id della riga per caricare i costi
       $msg = "Dato gestito correttamente";
 
    }
   else {
  	    $msg = "Dato NON inserito/aggiornato ERR = " . mysqli_error($conn);
       $okCommit=false;
    } 
    if($okCommit) { // Inserisco i costi in array se presenti
        if($debug)
           echo "$fname verifico eventuali costi principali associati<br>";

        for($index = 0; $okCommit && $index < count($sqlcosti) ;$index++) {
        	
        	     if($sqltipo == 'V')
        	         $sqltessera = 0;
        	     else 
        	         $sqltessera =  $sqlcosti[$index][3];
        	     $arrayAlive[$iArrayAlive] = $sqlcosti[$index][0];
        	     $sqltxt = $sqlcosti[$index][1];
        	     
        	     if(TOUPPER) {
        	     	  $sqltxt = strtoupper($sqltxt);
                 }
                 
               if($sqlcosti[$index][0] == 0) {    // ID = 0 inserisco          
        	         $sql="INSERT INTO costi(id_attpell, id_parent, tipo, descrizione, costo, tessera, utente)
        	                    VALUES(" . $sqlID . ", 0 , '" . $sqltipo . "', '" . $conn->real_escape_string($sqltxt) . "', " .
        	                    $sqlcosti[$index][2] .", " . $sqltessera .  ",'" . $conn->real_escape_string($current_user) . "')";

                   if($debug)
                        echo "$fname SQL costi = $sql<br>";
   
                   if($conn->query($sql)) { // Inserimento OK
                       $sqlIDParent = $conn->insert_id; // Prendo id della riga per caricare i costi
                       $arrayAlive[$iArrayAlive] = $sqlIDParent;
                       $msg = "Dato inserito correttamente";
                      }
                   else {
  	                    $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
                       $okCommit=false;
                      } 

        	          if($debug)
        	              echo "$fname COSTI PRINCIPALI SQL = $sql<br><br>";
                }
                else { // Aggiorno i dati
                   $sqlIDParent = $sqlcosti[$index][0];
        	         $sql="UPDATE costi SET descrizione = '" . $conn->real_escape_string($sqltxt) . "', " .
        	                   "costo = " . $sqlcosti[$index][2] . ", tessera = " . $sqltessera .
        	                   " WHERE costi.id = " . $sqlIDParent;
                   if($debug)
                        echo "$fname SQL costi = $sql<br>";
   
                   if($conn->query($sql)) { // Aggiornamento OK
                       $msg = "Dato aggiornato correttamente";
                      }
                   else {
  	                    $msg = "Dato NON aggiornato ERR = " . mysqli_error($conn);
                       $okCommit=false;
                      } 
                }

               if($debug)
                   echo "$fname verifico eventuali costi secondari associati<br>";
        	     $iArrayAlive++;
        	        
        	     for($indexA = 4; $okCommit && $indexA  < (count($sqlcosti[$index])); $indexA++) {
        	           $arrayAlive[$iArrayAlive] = $sqlcosti[$index][$indexA][0];

        	     	    $sqltxt = $sqlcosti[$index][$indexA][1];
        	           if(TOUPPER) {
        	     	        $sqltxt = strtoupper($sqltxt);
                       }
                    if($sqlcosti[$index][$indexA][0] == 0) {    // ID = 0 inserisco          
        	              $sql="INSERT INTO costi(id_attpell, id_parent, tipo,  descrizione, costo, tessera, utente)
        	                         VALUES(" . $sqlID . ", " . $sqlIDParent . ", '" . $sqltipo . "', '" . $conn->real_escape_string($sqltxt) . "', " .
        	                         $sqlcosti[$index][$indexA][2] . ",$sqltessera, '" . $conn->real_escape_string($current_user) . "')";

         	              if($debug)
        	                  echo "$fname COSTI SECONDARI SQL = $sql<br><br>";
  
                        if($conn->query($sql)) { // Inserimento OK
                           $arrayAlive[$iArrayAlive] = $conn->insert_id;
                           $msg = "Dato inserito correttamente";
 
                          }
                        else {
  	                        $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
                           $okCommit=false;
                         } 
                   }
                else { // Aggiorno i dati
                   $sqlIDParent = $sqlcosti[$index][0];
        	         $sql="UPDATE costi SET descrizione = '" . $conn->real_escape_string($sqltxt) . "', " .
        	                   "costo = " . $sqlcosti[$index][$indexA][2] . 
        	                   " WHERE costi.id = " . $sqlcosti[$index][$indexA][0];
                   if($debug)
                        echo "$fname COSTI SECONDARI SQL = $sql<br>";
   
                   if($conn->query($sql)) { // Aggiornamento OK
                       $msg = "Dato aggiornato correttamente";
                      }
                   else {
  	                    $msg = "Dato NON aggiornato ERR = " . mysqli_error($conn);
                       $okCommit=false;
                      } 
                }
                  
                  $iArrayAlive++;

               }
           }
        // Controllo se devo eliminare qualche voce di costo
        for($index=0; $index < $iArrayAlive;$index++) {
        	    if($arrayAlive[$index] == 0)
        	       continue;

        	    if($deleteStm == '')
        	        $deleteStm = "DELETE FROM costi WHERE id_attpell = " . $sqlID . " AND tipo = '" . $sqltipo ."'
        	                                AND id NOT IN(";
        	    
        	     $deleteStm .= $arrayAlive[$index] . ",";
             }
        if($index == 0) { //  Cancello eventuali voci di costo rimaste appese
            $deleteStm = "DELETE FROM costi WHERE id_attpell = " . $sqlID . " AND (tipo = '" . $sqltipo ."'";

           }
        if($deleteStm != '') {
        	   $deleteStm = rtrim($deleteStm, ',') . ")";
        	   if($debug)
                echo "$fname: SQL Delete = $deleteStm<br>";

             if(!$conn->query($deleteStm)) { // Cancellazione KO
  	             $msg = "Cancellazione fallita = " . mysqli_error($conn);
                $okCommit=false;
                $conn->query("rollback");  
                }
          }

     $conn->query("commit");  
        	
    }
    else   
        $conn->query("rollback");  

    if($okCommit) {
        $conn->query("commit");  
    }
    else   
        $conn->query("rollback");  
        
    if($debug) {
    	  var_dump($sqlcosti);
        echo $msg;
    }   
    else
        echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','" . $retPage . "');</script>"; 
  }
  $conn->close();
?>
</body>
</html>