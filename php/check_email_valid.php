<?php
/****************************************************************************************************
*
*  Controlla se l'indirizzo mail e' valido
*
*  @file check_email_valid.php
*  @abstract Controlla se l'indirizzo mail e' valido
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-17
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-17
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
function validateEMAIL($EMAIL) {
    $v = "/[a-zA-Z0-9_-.+]+@[a-zA-Z0-9-]+.[a-zA-Z]+/";
return (bool)filter_var($EMAIL, FILTER_VALIDATE_EMAIL);

    //return (bool)preg_match($v, $EMAIL);
}
?>