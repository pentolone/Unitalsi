<?php
/******************************************
*
*	Ritorna il default charset
*	Ritorna i flags per HTML
*
*	History: prima versione Marzo 2016
*
*******************************************/
function ritorna_charset() {
	$charSet = 'ISO-8859-1'; // Western European, Latin-1
//	$charSet = 'UTF-8'; // Western European, Latin-1
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

   return($charSet); 
}

function ritorna_default_flags() {
	$charSetFlags = ENT_QUOTES; // Convert Both double and single quote
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

   return($charSetFlags); 
}
?>