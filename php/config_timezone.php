<?php
/******************************************
*
*	Configura il timezone dell'installazione
*
*	History: prima versione Marzo 2016
*
*******************************************/
function config_timezone() {
	$tz = 'Europe/Rome';
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
	date_default_timezone_set($tz);
	return;
}
?>