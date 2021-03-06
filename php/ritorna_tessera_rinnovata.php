<?php
/****************************************************************************************************
*
*  Funzione che ritorna se il socio ha rinnovato la tessera (true)
*
*  @file ritorna_tessera_rinnovata.php
*  @abstract Ritorna true se la tessera e' stata rinnovata; false se no
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-07
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-07
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
function ritorna_rinnovo($conn, $sqlid_socio, $sqlanno = null) {
   $rinnovata = false; 
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
   if(!isset($sqlanno)) // Assegno anno corrente se non passato come parametro
       $sqlanno = date('Y');

// Verifico se emessa ricevuta o socio con tessera a zero costi
   $sql = "SELECT id, SUM(importo) FROM ricevute
                WHERE tessera = 1
                AND     id_socio = $sqlid_socio
                AND     YEAR(data_ricevuta) = $sqlanno
                AND     ricevute.tipo = 'A'
                AND     ricevute.id_attpell IN(SELECT attivita_m.id
                                                             FROM   attivita_m
                                                             WHERE attivita_m.anno = $sqlanno)
                GROUP BY 1                
                UNION
                SELECT costi_detail.id_parent, SUM(costo)
                FROM   costi_detail,
                             attivita_detail
                WHERE  tessera = 1
                AND      costi_detail.id_parent = attivita_detail.id
                AND      attivita_detail.anno = $sqlanno
                AND      attivita_detail.id_socio = $sqlid_socio
                AND      attivita_detail.tipo = 'A'
                GROUP BY 1
                HAVING SUM(costi_detail.costo) = 0";
                
               // echo $sql;

   $ck = $conn->query($sql);
   if($ck->num_rows > 0) 
      $rinnovata = true;
   return($rinnovata); 
}
?>