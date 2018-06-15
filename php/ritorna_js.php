<?php
/******************************************
*
*	Ritorna la stringa adattata per JavaScript
*
*	History: prima versione Aprile 2016
*
*******************************************/
function ritorna_js($in_str) {
	$f = array("'", "\"");			// Caratteri da trasformare
	$t = array("\'", "&quot;"); // Caratteri trasformati
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

   $out_str = str_replace($f, $t, $in_str);
   return($out_str); 
}
?>