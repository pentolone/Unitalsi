<?php
/****************************************************************************************************
*
*  Funzione che ritorna il numero di ricevuta da emettere
*
*  @file ritorna_numero_ricevuta.php
*  @abstract Ritorna il numero di ricevuta da emettere
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-19
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-19
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ritorna_numero_ricevuta($conn, $sqlid_sottosezione, $sqltipo_doc = "RF") {
   $n_ricevuta = 0; 
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
   $sqlanno = date('Y');
   $sql = "SELECT id, progressivo
                FROM   numerazione_ricevute
                WHERE id_sottosezione = " . $sqlid_sottosezione .
              " AND     anno = " . $sqlanno .
              " AND     codice_doc = '" . $sqltipo_doc . "'";
 
   $result = $conn->query($sql);
   
   if($result->num_rows == 0) {  // Progressivo inesistente (cambio anno o sottosezione che non ha ancora emesso documento)                           
       $sqlRic = "SELECT MAX(n_ricevuta) ric
                         FROM   ricevute
                         WHERE id_sottosezione = " . $sqlid_sottosezione .
                       " AND     YEAR(data_ricevuta) = " . $sqlanno .
                       " AND     tipo_doc = '" . $sqltipo_doc . "'"; 

       $result = $conn->query($sqlRic);
       $row = $result->fetch_assoc();
       
       if(!$row["ric"])
           $n_ricevuta = 1;
       else 
            $n_ricevuta = $row["ric"] + 1;
            
       $sql = "INSERT INTO numerazione_ricevute VALUES(NULL," .
                                                                            $sqlid_sottosezione . ",'" .
                                                                            $sqltipo_doc . "'," .
                                                                            $sqlanno . "," .
                                                                            $n_ricevuta . ", NULL, '" .
                                                                            $_SESSION["nomeuser"] . "')";
                                                                            
       if(!($conn->query($sql))) {
         	   echo mysqli_error($conn);
         	   $n_ricevuta = -1;
            }                                                                   
      }
   else { // Progressivo trovato! 
       $row = $result->fetch_assoc();
       $n_ricevuta = $row["progressivo"] + 1;
       $sql = "UPDATE numerazione_ricevute SET progressivo = " . $n_ricevuta . ",
                                                                           utente = '" . $conn->real_escape_string($_SESSION["nomeuser"]) . "'
                  WHERE id = " . $row["id"];

       if(!($conn->query($sql))) {
         	   echo mysqli_error($conn);
         	   $n_ricevuta = -1;
            }                                                                   
       }
       
   return($n_ricevuta);
}
?>