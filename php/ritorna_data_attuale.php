<?php
/******************************************
*
*	Ritorna la data attuale
*
*	History: prima versione Marzo 2016
*
*******************************************/
function ritorna_data_attuale() {
	$tz = 'Europe/Rome';
	$format='d/m/Y H:i:s'; // Formato Italiano
   $retData='';
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
	date_default_timezone_set($tz);
	$retData = date($format);
	return($retData);
}
?>