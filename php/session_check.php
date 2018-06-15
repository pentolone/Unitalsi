<?php
/****************************************************************************************************
*
*  Controlla la sessione attiva e, se attiva, aggiorna EXPIRE TIME
*
*  @file session_check.php
*  @abstract Controlla la sessione attiva e, se attiva, aggiorna EXPIRE TIME
*  @author Luca Romano
*  @version 1.0
*  @time 2016-04-21
*  @history firs release
*  
*  @first 1.0
*  @since 2016-04-21
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
function session_check() {
	require_once("../php/ritorna_session_timeout.php");
	$debug=false;
	$loginpage="../php/index.php";
   $userid=0;
   $now = time();
   $session_seconds=ritorna_session_timeout();
   
   if(session_status() == PHP_SESSION_NONE)      
       session_start();
      
   if(isset($_SESSION["userid"])) { // Session OK
       if($debug) {
       	 echo "Session active";
       	 echo "session_check() -> NOW = ". $now . "\r\n";
       	 echo "session_check() -> EXPIRE = ". $_SESSION["expire"];
       	}
       if($now > $_SESSION["expire"]) {
           session_unset();
	        session_destroy();
	        echo '<script type="text/javascript">alert("Sessione scaduta! Torna al login");'; 
           echo "window.open('" . $loginpage . "', '_parent');</script>";
         }
       else {  
           $_SESSION["expire"] = $now + $session_seconds;
           $userid =$_SESSION["userid"];
           if($debug) 
       	      echo "session_check() -> NEW EXPIRE = ". $_SESSION["expire"];
         }
      } 
   else { // Session KO go to login page
           echo '<script type="text/javascript">avviso("Sessione inesistente o scaduta! Torna al login");'; 
           echo "window.open('" . $loginpage . "', '_parent');</script>";

     }
     
  return $userid;
}
?>
