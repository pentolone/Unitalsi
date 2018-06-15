<?php
/****************************************************************************************************
*
*  Funzione che ritorna la sottosezione di pertinenza
*
*  @file ritorna_sottosezione_pertinenza.php
*  @abstract Ritorna la sottosezione di pertinenza legata all'utente collegato
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-13
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-13
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ritorna_sottosezione_pertinenza() {
   $s_id = 1; // must be greater than 0
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
          
          
   if(isset($_SESSION["sottosezione_appartenenza"])) 
      $s_id = $_SESSION["sottosezione_appartenenza"];

   if($s_id <= 0)
      $s_id = 1;

   return($s_id); 
}

function ritorna_sottosezione_pertinenza_des($conn, $id) {
	$sott_desc='';
   $sql_sottosezione_app = "SELECT nome
                                              FROM   sottosezione
                                              WHERE  id = " . $id;

   $result = $conn->query($sql_sottosezione_app); // Solo una riga
   $row = $result->fetch_assoc();
   
   $sott_desc = $row["nome"];
   return($sott_desc);

}
?>