<?php
/****************************************************************************************************
*
*  Funzione che ritorna l'eventuale possibilita' di gestire multiple sottosezioni
*
*  @file ritorna_multisottosezione.php
*  @abstract Ritorna il flag multisottosezione
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
function ritorna_multisottosezione() {
   $s_multi = false; 
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
                   
   if(isset($_SESSION["multi"])) 
      $s_multi = $_SESSION["multi"];
   return($s_multi); 
}
?>