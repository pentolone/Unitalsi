<?php
/****************************************************************************************************
*
*  Funzione che ritorna il nome dell'utente connesso all'applicativo
*
*  @file ritorna_utente.php
*  @abstract Ritorna il nome dell'utente connesso all'applicativo
*  @author Luca Romano
*  @version 1.1
*  @since 2016-03-21
*  @where Monza
*
*
****************************************************************************************************/
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
function ritorna_utente() {
   $retString="Nessuno";
   $loginpage="../php/index.php";
   
   if(session_status() == PHP_SESSION_NONE)   
      session_start();  

   if(!isset($_SESSION["userid"])) {
       echo '<script type="text/javascript">alert("Sessione scaduta! Torna al login");'; 
       echo "window.open('" . $loginpage . "', '_parent');</script>";
       return;
      }
   if(isset($_SESSION["nomeuser"])) {
   	   return $_SESSION["nomeuser"];
   	   }
   $userid = $_SESSION["userid"];
   $conn = DB_connect();

   // Check connection
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
      }
   $sql = "SELECT CONCAT(nome,' ', cognome) nome FROM utenti WHERE ID = " . $userid;
 	$result = $conn->query($sql);
   $row = $result->fetch_assoc();
   $retString = $row["nome"];
   $_SESSION["nomeuser"] = $retString;
   return $retString; 
}
?>
