<?php
/******************************************
*
*	Ritorna la provincia di default
*
*	History: prima versione Marzo 2016
*
*******************************************/
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

function default_provincia() {
   $retProvincia=0;
   $userid = session_check();
      
   	if(session_status() == PHP_SESSION_NONE)      
       session_start();
       
   if(isset($_SESSION["dfProv"])) { // Provincia di default in sessione, non accedo al DB
       return($_SESSION["dfProv"]);
      }
      
    // Accedo al DB
   $conn = DB_connect();

   // Check connection
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
      }
   $sql = "SELECT id_provincia FROM societa";
 	$result = $conn->query($sql);
   $row = $result->fetch_assoc();
   $retProvincia = $row["id_provincia"];

   // Metto in sessione la provincia per evitare accessi al DB
   $_SESSION["dfProv"] = $retProvincia;

   return $retProvincia; 
   //return "Luca";
}
?>