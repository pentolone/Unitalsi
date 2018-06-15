<?php
require_once('../php/unitalsi_include_common.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$old_id=array();
$new_id=array();
$index=0;
$okCommit=true;
$debug=true;
$ctrImported=0;
$sqlid_attpell=0;
$sqldescrizione_aggiuntiva=null;
$sqldal=null;
$sqlal=null;

$conn = DB_connect();

$kv = array();
 foreach ($_POST as $key => $value) {
               switch($key) {
      		                 case "cb_source": // source
      				                    $index=0;
                                    foreach($value as $thing ) {
                                                  $old_id[$index] = $thing;
                                                  $index++;
                                                 }
      				                     break;

      		                 case "cb_target": // target (uno solo) (Descrizione attivita' o descrizione pellegrinaggio)
      				                    $index=0;
                                    foreach($value as $thing ) {
                                                  $new_id[$index] = $thing;
                                                  $index++;
                                                 }
      				                     break;

      		                 case "tipo": // tipo ('A' = Attivita 'V' = Viaggio)
      				                    $tipo = $value;
      				                     break;
                  }
         }
         

if($debug)
    var_dump($old_id);

if($tipo == 'A') { // Attivita'
    $rcf = $conn->query("SELECT id id_attivita, descrizione FROM attivita
                                        WHERE  id_sottosezione = 17
                                        AND      id = " . $new_id[0]);
    $rcfr = $rcf->fetch_assoc();
    $sqlid_attpell = $rcfr["id_attivita"];
    $sqldescrizione_aggiuntiva = $rcfr["descrizione"];              
   }

if($tipo == 'V') { // VIaggio/Pellegrinaggio
    $rcf = $conn->query("SELECT id id_viaggio, descrizione FROM descrizione_pellegrinaggio
                                        WHERE  id_sottosezione = 17
                                        AND      id = " . $new_id[0]);
    $rcfr = $rcf->fetch_assoc();
    $sqlid_attpell = $rcfr["id_viaggio"];
    $sqldescrizione_aggiuntiva = $rcfr["descrizione"];              
   }

$conn->query("begin");

$sql = "SELECT id, ANNO, ID_SOCIO, CDPELL, FLSETT, CDCTRA, QTCTRA, CDPER,
                         QTCPER, CDCRID, QTCRID, CDCALT, QTCALT, CDSERV
            FROM    BOVIAG
            WHERE  ANNO <= 2016 AND history = 0 AND
                         CDPELL IN('";
for($index = 0; $index < count($old_id); $index++) {
	   $sql .= $old_id[$index] . "','";
}

$sql = rtrim($sql, ",'") . "')";
$sql .= " ORDER BY CDPELL, ANNO";

if($debug)
    echo "SQL = $sql<br>";

$conn->query('begin');
$rs = $conn->query($sql);

$sqlcfid=0;
while($r = $rs->fetch_assoc()) {
	       // Verifico se esiste l'attivita per l'anno in corso
	       
          if($tipo == 'A') { // Attivita'
          	 $v = "SELECT id, id_attivita, descrizione, anno, dal, al FROM attivita_m
                                       WHERE  id_sottosezione = 17
                                       AND      anno = " . $r["ANNO"] . "
                                       AND      id_attivita = $sqlid_attpell";
                                       
              echo "SQL CHECK ATTIVITA = $v<br>";
              $rcf = $conn->query($v);
              
              if($rcf->num_rows == 0) {// Inserisco capofila Attivita'
              	  $sqlInsCf = "INSERT INTO attivita_m (id_sottosezione, id_attivita,
              	                                                              anno, dal, al, history)
              	                       VALUES(17, " . $sqlid_attpell . ", " . 
              	                                    $r["ANNO"] . ", '" .
              	                                    $r["ANNO"] . "-01-01' , '" .
              	                                    $r["ANNO"] . "-12-31', 1)";

	               if($debug)
	                   echo "INSERT CAPOFILA (Attivita) = $sqlInsCf<br>";
              	   if($conn->query($sqlInsCf)) {
                      $sqlcfid = $conn->insert_id;
                      $new_id[0] = $sqlcfid;             
              	      }
 	                else {
	       	          echo "FAILED " . mysqli_error($conn);
	       	          $okCommit = false;
	       	          break;
	                  }
                }
           else {// Capofila presente
              $rrcf = $rcf->fetch_assoc();
              $sqlcfid = $rrcf["id"];
           }
             } // Fine attivita'
          if($tipo == 'V') { // Viaggio/Pellegrinaggio
          	 $v = "SELECT id FROM pellegrinaggi
                                       WHERE  id_sottosezione = 17
                                       AND      anno = " . $r["ANNO"] . "
                                       AND      id_attpell = $sqlid_attpell";
              $rcf = $conn->query($v);
              
              if($rcf->num_rows == 0) {// Inserisco capofila Viaggio/Pellegrinaggio
              	  $sqlInsCf = "INSERT INTO pellegrinaggi (id_sottosezione, id_attpell,
              	                                                                   anno, dal, al, history)
              	                       VALUES(17, " . $sqlid_attpell . ", " . 
              	                                    $r["ANNO"] . ", '" .
              	                                    $r["ANNO"] . "-01-01' , '" .
              	                                    $r["ANNO"] . "-12-31', 1)";

	               if($debug)
	                   echo "INSERT CAPOFILA (VIaggio) = $sqlInsCf<br>";
              	   if($conn->query($sqlInsCf)) {
                      $sqlcfid = $conn->insert_id;
                      $new_id[0] = $sqlcfid;             
              	      }
 	                else {
	       	          echo "FAILED " . mysqli_error($conn);
	       	          $okCommit = false;
	       	          break;
	                  }
	           }
           else {// Capofila presente
              $rrcf = $rcf->fetch_assoc();
              $sqlcfid = $rrcf["id"];
           }
            } // Fine Viaggio/Pellegrinaggio
              
	       $insStm = "INSERT INTO attivita_detail (id_sottosezione,
	                                                                      anno,
	                                                                      id_attpell,
	                                                                      tipo,
	                                                                      dal,
	                                                                      al,
	                                                                      id_socio,
	                                                                      history)
	                                                         VALUES(17," .
	                                                                      $r["ANNO"] . ", " .
	                                                                      $sqlcfid . ", '" .
	                                                                      $tipo . "', '".
	                                                                      $r["ANNO"] . "-01-01' , '" .
              	                                                         $r["ANNO"] . "-12-31'," .                                                                           
                                                                          $r["ID_SOCIO"] . ", 1)";

	       if($debug)
	          echo "INSERT STM = $insStm<br>";

	       if($conn->query($insStm)) {
	       	 if($conn->query("UPDATE BOVIAG SET history = 1 WHERE id = " . $r["id"])) {
	       	     $ctrImported++;
	       	     $okCommit = true;
	       	    }
	          else {
	       	    echo "FAILED mysqli_error($conn)";
	       	    $okCommit = false;
	       	    break;
	            }
	          }
	       else {
	       	echo "FAILED mysqli_error($conn)";
	       	$okCommit = false;
	       	break;
	         }
          }

if($okCommit) {
	echo "IMPORT SUCCESSFULL! Totale rows = $ctrImported<br>";
	$conn->query('commit');
//	$conn->query('rollback');
}
else {
	echo "IMPORT FAILED!<br>";
	$conn->query('rollback');
}
$conn->close();
?>