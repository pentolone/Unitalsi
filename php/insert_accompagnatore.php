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
*  Gestione inserimento degli accompagnatori
*
*  @file insert_accompagnatore.php
*  @abstract Gestisce gli inserimenti degli accompagnatori/accompagnati
*  @author Luca Romano
*  @version 1.0
*  @time 2017-08-06
*  @history first release
*  
*  @first 1.0
*  @since 2017-08-06
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
$debug=false;
$fname=basename(__FILE__);

// Campi di input da post
$sqlid_accompagnatore=0;
$sqlid_accompagnato=array();
$sqlid_socio=0;
$sqldata_dal=array();
$sqldata_al=array();
$sqlidDelete=0;
// Fine campi input da POST

$retPage = "../php/gestione_accompagnatori.php";

$okCommit=true;

if(($userid = session_check()) == 0)
     return;

config_timezone();

$current_user = ritorna_utente();
$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
                                
  $insertStm = "INSERT INTO accompagnatori
                                               (id_attpell,
                                               id_accompagnatore,
                                               id_accompagnato,
                                               dal,
                                               al,
                                               utente) VALUES (";
 
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
                      case 'id_attpell':
                              $sqlid_attpell=$value;
                              break;

                      case 'id_accompagnatore':
                              $sqlid_accompagnatore=$value;
                              break;

                      case 'id_accompagnato': // In input ID accompagnato (selezione multipla)
                          	  $i=0;
                               while(isset($value[$i])) {
                                         $sqlid_accompagnato[$i] = $value[$i];
                                         if($debug)
                                             echo $fname . " LIST ACCOMPAGNATI = " . $sqlid_accompagnato[$i] . '<br>';
                                         $i++;
                                        }     
                              break;

                      case 'data_accD': // Data presa in carico
                              $sqldal=$value;
                              $annoViaggio=substr($sqldata_viaggioA, 0, 4);
                              break;

                      case 'data_accA': // Data fine incarico
                              $sqlal=$value;
                              break;

                      case 'idDelete': // Richiesta cancellazione
                              $sqlidDelete=$value;
                              break;
      		               }  
      		    $index++;	  
         }
     

    if($sqlidDelete > 0) { // Richiesta cancellazione
        $sql = "DELETE FROM accompagnatori
                     WHERE  id = $sqlidDelete";

  	     $msg = "Assegnamento eliminato correttamente";
        if(!$conn->query($sql)) { // Cancellazione KO
  	        $msg = "Dato NON eliminato ERR = " . mysqli_error($conn);
  	       }
    	  echo "<form id='ok' name='ok' action='../php/gestione_accompagnatori.php' method='post'>";
    	  echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	  echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	  echo "<input type='hidden' name='id_accompagnatore' value=" . $sqlid_accompagnatore . ">";
    	  echo "</form>";
        echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
        return;
       }
    $conn->query('begin');
    
    
    // Elimino il socio dalla tabella accompagnatori evitando cosi' problemi di concorrenza sulla piattaforma
    
    $sql = "DELETE FROM accompagnatori
                 WHERE  id_attpell                 = $sqlid_attpell
                 AND      id_accompagnatore = $sqlid_accompagnatore
                 AND      id_accompagnato IN(";

    for($i=0; $i < count($sqlid_accompagnato);$i++) {
    	    $sql .= $sqlid_accompagnato[$i] . ", ";
          }
    $sql = rtrim($sql, ", ") . ")";
    if($debug)
        echo "$fname SQL (REMOVE): $sql<br>"; 

    if(!$conn->query($sql)) { // Cancellazione KO
  	     $msg = "Dato NON eliminato ERR = " . mysqli_error($conn);
        $okCommit=false;
       }

   // Inserisco in tabella accompagnatore
    for($i=0; $okCommit && $i < count($sqlid_accompagnato); $i++) {
 
        // Inserisco dati accompagnatore/accompagnato
        $sql = $insertStm . $sqlid_attpell . ", " .
                                        $sqlid_accompagnatore . ", " .
                                        $sqlid_accompagnato[$i] .", '" .
                                        $sqldal . "', '" .
                                        $sqlal . "', '" .
                                        $conn->real_escape_string($current_user) . "')";
        if($debug)
            echo "$fname: SQL (Insert Axxompagnatori)= $sql<br>";

        if($conn->query($sql)) { // Inserimento OK
           $okCommit=true;
           $msg .= " Associazione effettuata con successo"; 
          }
       else {
  	        $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
           $okCommit=false;
        } 
      } // Fine ciclo FOR
 
       if($okCommit)    
           $conn->query("commit"); 
       else 
           $conn->query("rollback"); 
       if($debug)    
           echo "$fname MSG = $msg<br>";
        else {
    	     echo "<form id='ok' name='ok' action='../php/gestione_accompagnatori.php' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "<input type='hidden' name='id_accompagnatore' value=" . $sqlid_accompagnatore . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
         }
}
$conn->close();
?>
</body>
</html>

