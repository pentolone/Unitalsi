<?php
/******************************************
*
*	Ritorna il numero di record da visualiizzare per pagina
*
*	History: prima versione Luglio 2016
*               (dalla versione > 2.0)
*              Made in Las Palmas
*
*******************************************/
function ritorna_nrec() {
   $nrec = 30; // must be greater than 0
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

   if($nrec <= 0)
      $nrec = 1;

   return($nrec); 
}
?>