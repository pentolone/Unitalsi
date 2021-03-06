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
*  Gestione inserimento delle prenotazione nella struttura
*
*  @file insert_prenotazione.php
*  @abstract Gestisce gli inserimenti delle prenotazioni
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-18
*  @history first release
*  
*  @first 1.0
*  @since 2017-03-18
*  @CompatibleAppVer All
*  @where Monza
*
*
*
****************************************************************************************************/
require_once('./mail/invia_mail.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
$date_format = ritorna_data_locale();
error_reporting(E_ALL ^ E_NOTICE);

$index=0;
$debug=false;
$fname=basename(__FILE__);

$insertStm='';
$okCommit=false;
$valueList='(';

$sql='';
$sqltxt='';
$sqlIDParent=0;
$sqlMail=null;
$sqlNome=null;
$sqldal=null;
$sqlal=null;
$sqlfull=0;
// Campi per aggiornare documento socio
$sqlid_doc=0;

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

      		            case "id_sottosezione": // Aggiorno documento socio
      					            $sqlid_sottosezione = $value;
      					            break;

      		            case "anno": // Anno di competenza
      					            $sqlanno = $value;
      					            break;

      		            case "spedisci-mail": // Invia mail di conferma
      					            $sqlMail = $value;
      					            break;

      		            case "id_socio": // ID socio
      					            $sqlid_socio = $value;
      					            break;

      		            case "id_attpell": // ID viaggio
      					            $sqlid_attpell = $value;
      					            break;

      		            case "id_struttura": // ID struttura
      					            $sqlid_struttura = $value;
      					            break;

      		            case "id_camera": // ID camera
      					            $sqlid_camera = $value;
      					            break;

      		            case "dal": // Arrivo (per eventuali cambi di programma, se no il periodo del socio)
      					            $sqldal = $value;
      					            break;

      		            case "al": // Partenza (per eventuali cambi di programma, se no il periodo del socio)
      					            $sqlal = $value;
      					            break;

      		            case "full": // Forzo camera non disponibile
      					            $sqlfull = $value;
      					            break;

      		            case "x": // Nothing to do
      					           break;

      		            case "y": // Nothing to do
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
      					         }
     		  } // End foreach

// Controllo se non gia' inserito da altro utente
    $sql = "SELECT COUNT(*) c
                 FROM   AL_occupazione
                              anagrafica
                 WHERE id_attpell = " . $sqlid_attpell .
               " AND     id_socio  =  " . $sqlid_socio;

    if($debug)
       echo "$fname: SQL check(1) = $sql<br>";

    $rs = $conn->query($sql);
    $rw = $rs->fetch_assoc();
    if($rw["c"] > 0) { // Socio gia' associato
           echo "<script>avviso_no('Socio gia\' prenotato per la struttura');</script>";
    	     echo "<form id='ok' name='ok' action='../php/gestione_prenotazione.php' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "<input type='hidden' name='id_struttura' value=" . $sqlid_struttura . ">";
    	     echo "<input type='hidden' name='id_camera' value=" . $sqlid_camera . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
           return;
       }         
    // Selezione periodo permanenza del socio
    $sql = "SELECT attivita_detail.dal,
                              attivita_detail.al
                 FROM    attivita_detail
                 WHERE  tipo = 'V'
                 AND      id_attpell = $sqlid_attpell
                 AND      id_socio   = $sqlid_socio";
                 
     $r = $conn->query($sql);
     $r1 = $r->fetch_assoc();
     
     if(!$sqldal)
          $sqldal = $r1["dal"];
         
     
     if(!$sqlal)
          $sqlal = $r1["al"];

// Controllo se la camera ha ancora posti disponibili
    $sql = "SELECT AL_occupazione.full f,
                             (AL_camere.n_posti-COUNT(AL_occupazione.id_camera)) c
                 FROM   AL_occupazione,
                             AL_camere
                 WHERE AL_occupazione.id_camera  =  " . $sqlid_camera .
               " AND     AL_camere.id = AL_occupazione.id_camera
                 AND     (dal BETWEEN '" . $sqldal . "' AND DATE_SUB('" . $sqlal . "', INTERVAL 1 DAY)
                 OR       al BETWEEN  DATE_ADD('" . $sqldal . "', INTERVAL 1 DAY)  AND '" . $sqlal ."')
                 GROUP BY 1 ORDER BY 1 DESC, 2";

    if($debug)
       echo "$fname: SQL check(2) = $sql<br>";

    $rs = $conn->query($sql);
    $rw = $rs->fetch_assoc();
    
    if($rs->num_rows > 0 && ($rw["f"] > 0 || $rw["c"] <= 0)) { // Camera senza posti disponibili
           echo "<script>avviso_no('La camera NON ha piu\' posti disponibili');</script>";
    	     echo "<form id='ok' name='ok' action='../php/gestione_prenotazione.php' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "<input type='hidden' name='id_struttura' value=" . $sqlid_struttura . ">";
    	     echo "<input type='hidden' name='id_camera' value=" . $sqlid_camera . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
           return;
       }
       
   $insertStm = "INSERT INTO AL_occupazione (id_attpell,
                                                                           id_camera,
                                                                           dal,
                                                                           al,
                                                                           id_socio,
                                                                           full,
                                                                           utente)
                                                            VALUES (". $sqlid_attpell . ", ".
                                                                           $sqlid_camera . ", '" .
                                                                           $sqldal . "', " . "'" .
                                                                           $sqlal . "', " .
                                                                           $sqlid_socio . ", " .
                                                                           $sqlfull .  ", '" .
                                                                           $current_user . "')";
   if($debug)
       echo "$fname: SQL insert = $insertStm<br>";
   if($conn->query($insertStm)) {
   	   $msg = "Assegnazione inserita correttamente";
   	   $sql = "SELECT CONCAT(nome,' ',cognome) nome
   	                FROM anagrafica
   	                WHERE id = " . $sqlid_socio;
   	                
   	    $r = $conn->query($sql);
   	    $rs = $r->fetch_assoc();

   	       $sqlNome = $rs["nome"];

   	   if($sqlMail) { // Invio mail di conferma
   	      $txt = "Gentile " . $sqlNome . ",<br><br>Le confermiamo la prenotazione dal " . substr($sqldal,8,2) . "/" .
   	                                                                                                                            substr($sqldal,5,2) . "/" .
   	                                                                                                                            substr($sqldal,0,4) . " al " .
   	                                                                                                                            substr($sqldal,8,2) . "/" .
   	                                                                                                                            substr($sqldal,5,2) . "/" .
   	                                                                                                                            substr($sqldal,0,4) .
   	                 "<br><br>Cogliamo l'occasione per porgerle i nostri piu' cordiali saluti.";
   	      if(invia_mail($sqlMail, null, "Conferma prenotazione", $txt, $sqlNome))
   	          $msg .= ". Inviata e-mail di conferma";
   	      else   
   	          $msg .= ". Errore invio mail";
   	     }
   	   if($debug)    
          echo $msg;
      else {
    	     echo "<form id='ok' name='ok' action='../php/gestione_prenotazione.php' method='post'>";
    	     echo "<input type='hidden' name='msg' value='" . $msg . "'>";
    	     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
    	     echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
    	     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
    	     echo "<input type='hidden' name='id_struttura' value=" . $sqlid_struttura . ">";
    	     echo "<input type='hidden' name='id_camera' value=" . $sqlid_camera . ">";
    	     echo "</form>";
           echo "<script type='text/javascript'>document.forms['ok'].submit();</script>"; 
        }
   }
 else {
    $msg = "Dato NON inserito ERR = " . mysqli_error($conn);
    if($debug)    
      echo $msg;
    else
        echo "<script type='text/javascript'>avviso('" . ritorna_js($msg) . "','../php/gestione_prenotazione.php');</script>";   
   }

} // End post
