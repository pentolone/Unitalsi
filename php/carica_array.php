<?php
/****************************************************************************************************
*
*  Funzione che ritorna l'array dei costi
*
*  @file carica_array.php
*  @abstract Ritorna l'array valorizzato se dati presenti in tabella costi
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-24
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-24
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
/*
	Parametri:
	
	- Connessione al DB
	- ID attivita'/viaggio
	- Tipo (A/V) default = 'A'
	- Inserimento (true/false) default false
	
*/
	
function carica_array($conn, $id, $tipo = 'A', $isInsert=false) {
   $defCharset = ritorna_charset(); 
   $defCharsetFlags = ritorna_default_flags(); 
   $retArray = array(); 
   $debug=false;
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
   $index=0;
   $indexA=0;
   $sqlArrayID=0;
   
   $sql = "SELECT id, descrizione, costo, tessera
                FROM   costi
                WHERE id_attpell = " . $id .
              " AND     id_parent = 0
                AND     tipo = '" . $tipo . "'
                ORDER BY 1";  

   $result = $conn->query($sql);
   
   while($row = $result->fetch_assoc()) {
   	          $sqlArrayID = $row["id"];
   	          if($isInsert)
   	              $sqlArrayID=0;
             array_push($retArray, array($sqlArrayID, htmlentities($row["descrizione"], $defCharsetFlags,$defCharset)  , $row["costo"], $row["tessera"]));
             
             $sql = "SELECT id, descrizione, costo
                          FROM   costi
                          WHERE id_attpell = " . $id .
                        " AND     id_parent = " . $row["id"] .
                        " AND     tipo = '" . $tipo . "'
                          ORDER BY 1";  
             $resultO = $conn->query($sql);
             while($rowO = $resultO->fetch_assoc()) {
   	                    $sqlArrayID = $rowO["id"];
   	                    if($isInsert)
   	                        $sqlArrayID=0;
             	       array_push($retArray[$index],array($sqlArrayID, htmlentities($rowO["descrizione"], $defCharsetFlags, $defCharset) , $rowO["costo"]));
             	       //array_push($retArray[$index],array($rowO["descrizione"]  , $rowO["costo"]));

   	                    }
   	          $index++;
   	          }
   	if($debug) {
       echo "carica_array.php:";
       var_dump($retArray);
    }
   return($retArray);
}
?>