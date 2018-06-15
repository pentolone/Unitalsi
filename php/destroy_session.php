<?php
/******************************************
*
*	Distrugge la sessione
*  Rimanda alla pagina di login
*
*	History: prima versione Marzo 2016
*
*******************************************/
	$loginpage="../php/index.php";
	 if(session_status() == PHP_SESSION_NONE)      
       session_start();

	if(session_status() != PHP_SESSION_NONE) {
	    session_unset();
	    session_destroy();
		} 

   echo '<script type="text/javascript">'; 
   echo 'window.location="' . $loginpage . '";</script>';
?>
