<?php
/****************************************************************************************************
*
*  Ritorna se l'utente &egrave; alla funzionalit&agrave; richiesta
*
*  @file ritorna_user_allowed.php
*  @abstract Controllo accesso
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-03
*  @history prima versione
*  @input path alla funzione
*  @output true/false
*  
*  @first 1.0
*  @since 2017-02-03
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ritorna_user_allowed($path_to_function) {
  require_once('../php/unitalsi_include_common.php');
  
  $id_livello = $_SESSION["livello_utente"];
     
  $conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT pagina
               FROM   voci_sottomenu
               WHERE pagina = '" . $path_to_function .
               "' AND  livello >= " . $id_livello;
 
  $result = $conn->query($sql);
  if($result->num_rows == 0)
       return(false);
  else
       return(true);
 $conn->close();
 } 
?>