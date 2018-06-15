<?php
/****************************************************************************************************
*
*  Ritorna ID della società corrente, in caso di multisocietà
*
*  @file allega_file,php
*  @abstract allega il file alla tabella interessata
*  @input id riga tabella, nome tabella, utente, pdf, tipo (default=pdf)
*  @author Luca Romano
*  @version 1.0
*  @time 2016-07-22
*  @history first release
*  
*  @first 1.0
*  @since 2016-07-22
*  @CompatibleAppVer All
*  @where Las Palmas de Gran Canaria
*
*
****************************************************************************************************/
require_once("DB_connect.php");

function ritorna_societa_id() {
	$sql = "SELECT id_sottosezione FROM utenti WHERE utenti.id = 1";
	$socID = 1; // Default value in case not in session
	
	$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
 }
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

   if(isset($_SESSION["societa_id"])) { // Get Company ID from session (if present)
      $socID = $_SESSION["societa_id"];
     }
   return($socID); 
}
?>