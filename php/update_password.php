<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <LINK href="../css/chiamate.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  
</head>
<body>

<?php
/****************************************************************************************************
*
*  Aggiorna la password dell'utente corrente nel DB
*
*  @file update_password.php
*  @abstract Aggiorna la password 
*  @author Luca Romano
*  @version 1.1
*  @time 2016-07-23
*  @history solo bug fixing
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer All
*  @where Las Palmas de Gran Canaria
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$debug=false;
$fname=basename(__FILE__); 

$update=false;
$doupdate=true;

$tablename = 'utenti';
$retPage = '../php/gestione_password.php';

$titolo='';
$index=0;

$sqlID=0;
$sqloldpwd='';
$sqlpwd='';
$sqlpwd_c='';
$sql='';
$sqlnome='';
$sqlcognome='';

if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();
$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Prendo ID utente
if ($_POST) { // se post chiamata correttamente
      $update = true;
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                    switch($index) {
      		           case 0: // User ID
      					        $sqlID = $value;
      					        break;

      		           case 1: // Old password
      					        $sqloldpwd = $value;
      					        break;

      		           case 2: // New password
      					        $sqlpwd = $value;
      					        break;

      		           case 3: // Conferma new password
      					        $sqlpwd_c = $value;
      					        break;
                    }
                    $index++;
                  }
       }

if ($sqlID < 1 || $sqlID==null) { // chiamata sbagliata
     echo $fname . "<H1>Chiamata NON riuscita</H1>";
     echo "</body>";
     echo "</html>";
     return;
       }

$retPage=$retPage . "?id=" . $sqlID;

$sql = "SELECT nome, cognome FROM " . $tablename . " WHERE id = " . $sqlID . " AND pwd = PASSWORD('" . $sqloldpwd . "')";

$result = $conn->query($sql);

if($result->num_rows == 0) { // Vecchia password errata
      echo '<script type="text/javascript">avviso("Password attuale ERRATA!","' . $retPage .'");</script>';
      $doupdate=false; 
       }

if($sqlpwd != $sqlpwd_c) { // Le password NON coincidono
      echo '<script type="text/javascript">avviso("Le password NON coincidono!","' . $retPage .'");</script>';
      $doupdate=false; 
       }

if($debug && $doupdate) 
     echo "DATI corretti, procedo con l'aggiornamento";

if($doupdate) { // OK, procedo
    $sql = "UPDATE " . $tablename . " SET pwd = PASSWORD('" . $sqlpwd . "') WHERE id = " . $sqlID;
    if($conn->query($sql)) {
      echo '<script type="text/javascript">avviso("Dato aggiornato correttamente","' . $retPage .'");</script>';       
      }    
   else {
      echo '<script type="text/javascript">avviso("Dato NON aggiornato!","' . $retPage .'");</script>'; 
    }
}
$conn->close();

 ?>
  </body>
</html>