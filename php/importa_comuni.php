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
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 


$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

	    $keyFile    = file_get_contents("listacomuni.txt");
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
                 if( preg_match("/^#/", preg_quote($rows[$index])))  {// Comment
                      if($debug)
                          echo "Comment = " . $rows[$index];
                      $index++;
                      continue;
                    }

                 $row_data = explode(";", $data); 
                 $nome   = $row_data[1];
                 $sigla      = $row_data[2];
                 $cap      = $row_data[5];
                 $cat = $row_data[6];
                 
                 $sql = "SELECT id FROM province WHERE sigla = '" . $sigla ."'";
                 $result = $conn->query($sql);
                 $row = $result->fetch_assoc();
                 
                 $sql = "INSERT INTO comuni VALUES (0," . $row["id"] . ", '" .
                                                                             htmlspecialchars($nome, $defCharsetFlags, $defCharset) . "','" .
                                                                             $cap ."','" . $cat ."')";
                            
                 if($debug) {
                     echo " Nome = " .$nome  .  " Sigla " . $sigla . " CAP " . $cap . " CAT " . $cat . "<br>";
                    }
                 if(!$conn->query($sql)) { // Inserimento KO
                    $conn->query("rollback");
                    $errore=true;
                    exit;
                    }

                $index++;
                }
          if(!$errore)
                    $conn->query("commit");

?>