<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key()) {
	//echo "NO BUONO!";
   return;
   }
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
*  Gestione login all'applicativo
*
*  @file login.php
*  @abstract Gestisce l'accesso all'applicativo
*  @author Luca Romano
*  @version 1.1
*  @time 2016-07-23
*  @history bug fixing per PHP 7 e invio mail in caso di "furbacchioni" !
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer > 2.6
*  @where Las Palmas de Gran Canaria
*
*==================== DO NOT DISTRIBUTE SOURCE ======================
*
*  USE http://fopo.com.ar/ (per crypt)
*
*=============================================================
*
****************************************************************************************************/
include '../php/check_browser.php';
include '../php/mail/invia_mail.php';

$debug=false;
$fname=basename(__FILE__);
$supportedBrowser=array("Google Chrome", "Apple Safari");
$info=array();

$browserSupported=false;
$menupage = "../php/frame_set.php";
$loginpage = "../php/index.php";
$tablename = 'utenti';
$index=0;
$sqlID=0;
$sqluser='';
$sqlpwd='';
$sqlcellulare='';
$to='luke.romano@gmail.com';
$success=false;

// Leggo i browser supportati dal file di licenza
$activationFile=ritorna_path_to_key();

if($debug)
	 echo "$fname:<br>";

$keyFile  = file_get_contents($activationFile);
$rows = explode("\n", $keyFile);
$totRows=count($rows);
$totRows--;

if($debug)
	 echo "$fname: Totale righe file ($activationFile) = $totRows<br>";
 
foreach($rows as $row => $data) {
		        if($index >= $totRows)
		            break;

               //get row data
                if( preg_match("/^#/", preg_quote($rows[$index])))  { // Comment
                     if($debug)
                         echo "$fname: Comment (ignoring) = " . $rows[$index] . '<br>';
                     $index++;
                     continue;
                    }

                $rows[$index] = rtrim($rows[$index]);
                $row_data = explode("=", $data);
                
                $info[$row]['id']           = $row_data[0];
                $info[$row]['value']      = $row_data[1];
                 
                if($debug) {
                    echo "$fname: " . $activationFile . " ID = " .$info[$row]['id']  .  " VALUE = " . $info[$row]['value'] . "<br>";
                    }
                 if($info[$row]['id'] == 'supportedBrowser') { // found list supported browser
                     $sb = $info[$row]['value'];
                     $supportedBrowser = explode("," , $sb);
                    }

                $index++;
                    
             } // END foreach

$browser=getBrowser();
foreach($supportedBrowser as $key => $value) {
	$w = ltrim($browser['name']); // Suppress leading spaces
	$w = rtrim($w); // Suppress spaces
	if($debug)
	    echo "$fname: Supported Browser = \"$w\"<br>";
	 if($value == $w) {
        $browserSupported=true;
        break;
   }
}
if(!$browserSupported) { // Browser non supportato
    echo '<script type="text/javascript">avviso("Browser ' . $browser['name'] . ' NON supportato!","' . $loginpage .'");</script>'; 
    return($success);
}

if(session_status() == PHP_SESSION_NONE)      
   session_start();

// Unset all variables sotred in $_SESSION
session_unset();
$session_seconds=ritorna_session_timeout();

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  
  if ($login_data = $_POST) { // se post allora giusto!
      $sqluser = $login_data["username"];
      $sqlpwd = $login_data["pwd"];
      $sql = "SELECT id, id_livello, 
                               username,pwd, 
                               nome, cognome,
                               id_sottosezione, multisottosezione,
                               cellulare, email
                    FROM ". $tablename .
                  " WHERE username = \"" . $conn->real_escape_string($sqluser) . "\" 
		  AND pwd = CONCAT('*', UPPER(SHA1(UNHEX(SHA1('" . $conn->real_escape_string($sqlpwd) . "')))))";

      if($debug)
         echo $sql;
      $result = $conn->query($sql);

      if ($result->num_rows > 0) { // OK Avvio sessione
          $row = $result->fetch_assoc();
          $_SESSION["valid"] = true;
          $_SESSION["start"] = time();
          
          $_SESSION["expire"] = $_SESSION["start"] + $session_seconds;
          $_SESSION["userid"] = $row["id"];
          $_SESSION["livello_utente"] = $row["id_livello"];
          $_SESSION["sottosezione_appartenenza"] = $row["id_sottosezione"];
          $_SESSION["multi"] = $row["multisottosezione"];
          
          echo '<script type="text/javascript">'; 
          echo 'window.location="' . $menupage . '";</script>';
          $success=true;

      }
      else { // Autenticazione fallita
           echo '<script type="text/javascript">avviso("Nome utente o password errati","' . $loginpage .'");</script>'; 
           }
        }
  else  { // Qualche furbacchione???
       echo '<script type="text/javascript">avviso("Chiamata alla pagina NON corretta");</script>'; 
       invia_mail($to, 'Chiamata alla pagina di login senza POST!', 'Verifica MIO BADRONE BIANGO!');
      }
return($success);
?>
</body>
</html>
