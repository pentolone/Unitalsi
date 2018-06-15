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
*  Gestione inserimento dei dettagli mezzo/pellegrinaggio
*
*  @file insert_mezzi_detail.php
*  @abstract Gestisce gli inserimenti dei mezzi/soci
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-06
*  @history first release
*  
*  @first 1.0
*  @since 2017-03-06
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
$sqlid_sottosezione=0;
$sqlid_mezzo=0;
$sqlid_socio=0;
$sqltipo_viaggio=0;
$sqldata_viaggioA=null;
$sqldata_viaggioR=null;
$sqln_posti=1; // For future use
// Fine campi input da POST

$retPage = "../php/gestione_soci_mezzo.php";

$annoViaggio=null;
$okCommit=true;
$sqlText=array();
$sql='';
$sqltxt='';

if(($userid = session_check()) == 0)
     return;

config_timezone();

$current_user = ritorna_utente();
$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
                                
  $insertStm = "INSERT INTO mezzi_detail
                                               (id_sottosezione,
                                               data_viaggio,
                                               id_attpell,
                                               id_mezzo,
                                               id_socio,
                                               n_posti,
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
                      case 'id_sottosezione':
                              $sqlid_sottosezione=$value;
                              break;

                      case 'id_attpell':
                              $sqlid_attpell=$value;
                              break;

                      case 'id_mezzo':
                              $sqlid_mezzo=$value;
                              break;

                      case 'id_socio_viaggio': // In input ID socio e tipo viaggio separato da ';'
                              $pieces = explode(";", $value);
                                           
                              $sqlid_socio=$pieces[0];
                              $sqltipo_viaggio=$pieces[1];
                              break;

                      case 'data_viaggioA': // Data viaggio di andata
                              $sqldata_viaggioA=$value;
                              $annoViaggio=substr($sqldata_viaggioA, 0, 4);
                              break;

                      case 'data_viaggioR': // Data viaggio di ritorno
                              $sqldata_viaggioR=$value;
                              break;
      		               }  
      		    $index++;	  
         }
     
    $conn->query('begin');
    
    // Elimino il socio dal/dai mezzi, evitando cosi' problemi di concorrenza sulla piattaforma
    
    $sql = "DELETE FROM mezzi_detail
                 WHERE  id_sottosezione = $sqlid_sottosezione
                 AND      id_socio             = $sqlid_socio
                 AND      id_attpell           = $sqlid_attpell
                 AND      data_viaggio IN('" . $sqldata_viaggioA . "','" . $sqldata_viaggioR . "')";
 
    if($debug)
        echo "$fname SQL (REMOVE): $sql<br>"; 

    if(!$conn->query($sql)) { // Cancellazione KO
  	     $msg = "Dato NON eliminato ERR = " . mysqli_error($conn);
        $okCommit=false;
       }
    else {
    	  if($sqltipo_viaggio == NONE)
    	     $msg="Socio rimosso dal viaggio";
        }


    if($okCommit && ($sqltipo_viaggio == TO || $sqltipo_viaggio == ROUNDTRIP)) {
    	    // Controllo se il mezzo ha ancora posti disponibili
        $sqlCk = "SELECT DISTINCT(mezzi_disponibili.capienza)-(IFNULL(SUM(mezzi_detail.n_posti),0)+" . $sqln_posti . ")
                                      rim
                         FROM   mezzi_detail,
                                      mezzi_disponibili
                         WHERE mezzi_detail.id_attpell = " . $sqlid_attpell .
                       " AND     mezzi_detail.id_mezzo = mezzi_disponibili.id
                         AND     mezzi_disponibili.id = " . $sqlid_mezzo .
                       " AND     mezzi_detail.data_viaggio = '" . $sqldata_viaggioA . "'" .
                       " AND     mezzi_disponibili.capienza > 0";

        if($debug)
            echo "$fname SQL $sqlCk<br>";                     
        $resCk = $conn->query($sqlCk);
        $rowCk = $resCk->fetch_assoc();
           
         if($resCk->num_rows > 0 && $rowCk["rim"] < 0) {
             echo "<script type='text/javascript'>avviso('Il mezzo NON ha abbastanza posti liberi!', '" . $retPage ."' );</script>";
             return;
         }

        // Inserisco socio viaggio di andata
        $sql = $insertStm . $sqlid_sottosezione . ", '" .
                                        $sqldata_viaggioA . "', " .
                                        $sqlid_attpell .", " .
                                        $sqlid_mezzo . ", " .
                                        $sqlid_socio . ", 1, '" .
                                        $conn->real_escape_string($current_user) . "')";
        if($debug)
            echo "$fname: SQL (Insert Andata)= $sql<br>";

        if($conn->query($sql)) { // Inserimento OK
           $okCommit=true;
           $msg = "Viaggio di andata OK"; 
          }
       else {
  	        $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
           $okCommit=false;
        } 
      }

    if($okCommit && ($sqltipo_viaggio == FROM || $sqltipo_viaggio == ROUNDTRIP)) {
    	    // Controllo se il mezzo ha ancora posti disponibili
        $sqlCk = "SELECT DISTINCT(mezzi_disponibili.capienza)-(IFNULL(SUM(mezzi_detail.n_posti),0)+" . $sqln_posti . ")
                                      rim
                         FROM   mezzi_detail,
                                      mezzi_disponibili
                         WHERE mezzi_detail.id_attpell = " . $sqlid_attpell .
                       " AND     mezzi_detail.id_mezzo = mezzi_disponibili.id
                         AND     mezzi_disponibili.id = " . $sqlid_mezzo .
                       " AND     mezzi_detail.data_viaggio = '" . $sqldata_viaggioR . "'" .
                       " AND     mezzi_disponibili.capienza > 0";

        if($debug)
            echo "$fname SQL $sqlCk<br>";                     
        $resCk = $conn->query($sqlCk);
        $rowCk = $resCk->fetch_assoc();
            
         if($resCk->num_rows > 0 && $rowCk["rim"] < 0) {
             echo "<script type='text/javascript'>avviso('Il mezzo NON ha abbastanza posti liberi!', '" . $retPage ."' );</script>";
             return;
         }

        // Inserisco socio viaggio di ritorno
        $sql = $insertStm . $sqlid_sottosezione . ", '" .
                                        $sqldata_viaggioR . "', " .
                                        $sqlid_attpell .", " .
                                        $sqlid_mezzo . ", " .
                                        $sqlid_socio . ", 1, '" .
                                        $conn->real_escape_string($current_user) . "')";
        if($debug)
            echo "$fname: SQL (Insert Ritorno)= $sql<br>";

        if($conn->query($sql)) { // Inserimento OK
           $okCommit=true;
           $msg .= " Viaggio di ritorno OK"; 
          }
       else {
  	        $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
           $okCommit=false;
        } 
      }
 
       if($okCommit)    
           $conn->query("commit"); 
       else 
           $conn->query("rollback"); 
       if($debug)    
           echo "$fname MSG = $msg<br>";
        else {
    	     echo "<form id='ok' name='ok' action='../php/gestione_soci_mezzo.php' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $annoViaggio . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "<input type='hidden' name='id_mezzo' value=" . $sqlid_mezzo . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
         }
}
$conn->close();
?>
</body>
</html>

