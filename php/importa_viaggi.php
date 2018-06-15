<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  
</head>
<body>

<?php

$debug=true;
$errore=false;
$index=0;
$skipped=0;
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

	    $keyFile    = file_get_contents("../source_data/viaggi.csv");
	    $rows = explode("\n", $keyFile);
	    $totRows=count($rows);
	    $totRows--;
	    if($debug)
	       echo "Totale righe file = " . $totRows . "<br>";
	    $conn->query("begin");

	    foreach($rows as $row => $data) {
	          
		          if($index >= $totRows)
		              break;
                //get row data
                 if( preg_match("/^#/", preg_quote($rows[$index])) || $index==0)  {// Comment
                      if($debug)
                          echo "Comment = " . $rows[$index];
                      $index++;
                      continue;
                    }

                 $row_data = explode(",", $data); 
                 $anno  = trim($row_data[0]); // Anno pellegrinaggio
                 $cod_pel      = trim($row_data[1]); // Codice pellegrinaggio
                 $cod_socio   = trim($row_data[2]); // ID anagrafica
                 $n_settimane   = trim($row_data[3]); // Settimane '1' o 'E'
                 $codice_tra = trim($row_data[4]); // Codice trasporto
                 $costo_tra = trim($row_data[5]); // Costo trasporto
                 $tipo_per = trim($row_data[6]); // Tipo pernotto
                 $costo_per = trim($row_data[7]); // Costo pernotto
                 $codice_rid = trim($row_data[8]); // Codice riduzione
                 $costo_rid = trim($row_data[9]); // Costo riduzione
                 $codice_altri = trim($row_data[10]); // Codice altri costi
                 $costo_alt = trim($row_data[11]); // Costo altro
                 $cod_serv = trim($row_data[12]); // Codice servizio
                 
                 $sql = "SELECT id FROM pellegrinaggi
                              WHERE  YEAR(dal) = " . $anno .
                              " AND      id_attpell IN(SELECT id
                                                                                    FROM descrizione_pellegrinaggio
                                                                                    WHERE descrizione IN(
                                                                                       SELECT des FROM temporary_pell
                                                                                       WHERE anno = " . $anno . " AND
                                                                                       id_ori = '" . $cod_pel . "'))";
                                                                                  
                                                                                  echo $sql;
                 $result = $conn->query($sql);
                 $row = $result->fetch_assoc();
                 
                 if(!$row["id"]) {
                 	echo "ASSIGN ORIGINAL VALUE";
                 	$skipped++;
                 	//continue;
                 }
                 else 
                 $cod_pel = $row["id"];

                 $sql = "SELECT id FROM riduzione
                                                                                  WHERE cd_ori = '" . $codice_rid. "'";

                 $result = $conn->query($sql);
                 $row = $result->fetch_assoc();
                 
                 if(!$row["id"]) {
                 	echo "DISASTRO!";
                 	$skipped++;
                 	continue;
                 }
                 else 
                 $codice_rid = $row["id"];

                 if($costo_alt == '"')
                     $costo_alt = 0;
                    
                 $sql = "INSERT INTO viaggi VALUES (0,17," . $anno. ", " . $cod_pel. ", " .
                                                                            $cod_socio . ", '" . $conn->real_escape_string($codice_tra) . "', " .
                                                                            $costo_tra . ", '" . $tipo_per . "', ".
                                                                            $costo_per . ", " . $codice_rid . ", " .
                                                                            $costo_rid . ", '" . $conn->real_escape_string($codice_altri) . "', '" .
                                                                            $conn->real_escape_string($costo_alt) . "', '" . $conn->real_escape_string($cod_serv) .
                                                                            "', now(), 'Admin L')";
                            
                 if($debug) {
                     echo "$sql<br>";
                    }
                 if(!$conn->query($sql)) { // Inserimento KO
         	   echo mysqli_error($conn);
                    $conn->query("rollback");
                    $errore=true;
                    exit;
                    }

                $index++;
                }
          if(!$errore) {
          	echo "<br><hr>Totale elaborati = $index<br>";
          	echo "<br><hr>Totale NON elaborati  = $skipped<br>";
                    $conn->query("commit");
                }

?>