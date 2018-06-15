<?php
/****************************************************************************************************
*
*  Ritorna le abilitazioni dell'utente per la funzione in utilizzo
*
*  @file ritorna_abilitazioni.php
*  @abstract Controllo accesso al DB (R/I/U/D)
*  @author Luca Romano
*  @version 1.0
*  @time 2017-10-08
*  @history prima versione
*  @input connessione al DB, path della pagina (opzionale)
*  @output array abilitazioni
*  
*  @first 1.0
*  @since 2017-10-08
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ritorna_abilitazioni($conn, $path_to_function = null) {
  $authMask = array("query" => 0, "insert" => 0, "update" => 0, "delete" => 0);
  
  $id_livello = $_SESSION["livello_utente"]; // Livello utente
  
  if(!$path_to_function) {
      $path_to_function = "../php/" . basename($_SERVER['REQUEST_URI']);
      
   }

 // Seleziono i permessi per la pagina in uso
 $sql = "SELECT q, i, u, d
              FROM   voci_sottomenu,
                          abilitazione_livello
              WHERE id_livello = $id_livello
              AND     id_pagina = voci_sottomenu.id
              AND     '" . $path_to_function . "' LIKE CONCAT(pagina,'%')" ;
              
$rA = $conn->query($sql);

if($rA->num_rows > 0) { // Trovato
    $rs = $rA->fetch_assoc();
    $authMask["query"] = $rs["q"];
    $authMask["insert"] = $rs["i"];
    $authMask["update"] = $rs["u"];
    $authMask["delete"] = $rs["d"];
   }
$rA->close();   

if($_SESSION["userid"] == 1) { // Admin
    $authMask["query"] = 1;
    $authMask["insert"] = 1;
    $authMask["update"] = 1;
    $authMask["delete"] = 1;
}

return($authMask);

 } 
?>