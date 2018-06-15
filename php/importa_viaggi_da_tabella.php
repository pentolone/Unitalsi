<?php
require_once('../php/unitalsi_include_common.php');
?>
<html>
<head>
   <title>Importa viaggi/pellegrinaggi da tabella viaggi</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  
</head>
<body>

<?php
$id_old=0; //OLD pellegrinaggio
$id_viaggi=0;
$id_p=0;
$sqlid_riduzione=0;
$ctrViaggi=0;
$ctrRighe=0;

$okCommit=true;
$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

 /*IMPORTATO 2/3/2017 15:08
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'LOURDE%'
          ORDER BY pellegrinaggi.id";
   */       
/* IMPORTATO 2/3/2017 15:15

$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'BANNE%%'
          ORDER BY pellegrinaggi.id";
 */ 

/*IMPORTATO 2/3/2017 15:16
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'ASSISI%%'
          ORDER BY pellegrinaggi.id";
*/

/* IMPORTATO 2/3/2017 16:22
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'BORGHETTO%TURNO%'
          ORDER BY pellegrinaggi.id";
*/

/* IMPORTATO 2/3/2017 16:25
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'LORETO%'
          ORDER BY pellegrinaggi.id";
*/

/* IMPORTATO 2/3/2017 16:28
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'FATIMA%'
          ORDER BY pellegrinaggi.id";
*/
/* IMPORTATO 2/3/2017 16:37
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'MONZA%TURNO%'
          ORDER BY pellegrinaggi.id";
*/

/* IMPORTATO 2/3/2017 16:34
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'BORGHETTO%MONZA%'
          ORDER BY pellegrinaggi.id";
*/
/* IMPORTATO 2/3/2017 16:47
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    descrizione_pellegrinaggio.descrizione LIKE 'BORGHETTO%SARONNO%'
          ORDER BY pellegrinaggi.id";
*/
$sql="SELECT * FROM viaggi, descrizione_pellegrinaggio, pellegrinaggi
          WHERE viaggi.id_pellegrinaggio = pellegrinaggi.id
          AND    pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
          AND    pellegrinaggi.id in(1000316, 1000323)
          ORDER BY pellegrinaggi.id";

 $result = $conn->query($sql);
 
  $conn->query('begin');
  while($okCommit && ($row = $result->fetch_array())) {
  	   $ctrRighe++;
  	
  	   if($id_old != $row[3]) { // Nuovo o primo viaggio
  	      $ctrViaggi++;
  	   //echo var_dump($row);
  	       $id_viaggi = $row[0];
  	       $id_old = $row[3];
  	       echo "Elaboro viaggio $row[19]<br>";
// Aggiorno data dal          
          $sql = "UPDATE pellegrinaggi set dal = '" . $row[30] . "' WHERE id = " . $id_old;
          echo "$sql<br>";
          
          if(!$conn->query($sql)) {
          	echo mysqli_error($conn);
             $okCommit=false;
          }

// Inserisco anagrafica costi
          $sql = "INSERT INTO costi (id_attpell, id_parent, tipo, descrizione, costo)
                       VALUES (" . $id_old . ", 0 , 'V', 'Albergo',1)";
          echo "$sql<br>";
          
          if(!$conn->query($sql)) {
          	echo mysqli_error($conn);
             $okCommit=false;
          }

          $sql = "INSERT INTO costi (id_attpell, id_parent, tipo, descrizione, costo)
                   VALUES (" . $id_old . ", 0 , 'V', 'Viaggio',1)";
          echo "$sql<br>";
          
          if(!$conn->query($sql)) {
          	echo mysqli_error($conn);
             $okCommit=false;
          }

          $sql = "INSERT INTO costi (id_attpell, id_parent, tipo, descrizione, costo)
                       VALUES (" . $id_old . ", 0 , 'V', 'Ass.',1)";
          echo "$sql<br>";
          
          if(!$conn->query($sql)) {
          	echo mysqli_error($conn);
             $okCommit=false;
          }
       }

      $sql = "INSERT INTO attivita_detail (id_sottosezione, anno, id_attpell, tipo, id_socio)
                  VALUES(" . $row[1] . ", " . $row[2] . ", " . $id_old . ", 'V', " . $row[4] . ")";
      echo "$sql<br>";
          
       if(!$conn->query($sql)) {
        	echo mysqli_error($conn);
          $okCommit=false;
        }
        else 
           $id_p = $conn->insert_id;
      
// Inserisco i costi
      $sql = "INSERT INTO costi_detail (id_parent, qta, descrizione, costo, id_riduzione, valore)
                  VALUES(" . $id_p . ", 1, 'Albergo', " . $row[8] . ", " . $row[9] . ", " . $row[10] .")";
      echo "$sql<br>";
          
       if(!$conn->query($sql)) {
        	echo mysqli_error($conn);
          $okCommit=false;
        }

      $sql = "INSERT INTO costi_detail (id_parent, qta, descrizione, costo, id_riduzione, valore)
                  VALUES(" . $id_p . ", 1, 'Viaggio', " . $row[6] . ", 0, 0.00)";
      echo "$sql<br>";
          
       if(!$conn->query($sql)) {
        	echo mysqli_error($conn);
          $okCommit=false;
        }

      $sql = "INSERT INTO costi_detail (id_parent, qta, descrizione, costo, id_riduzione, valore)
                  VALUES(" . $id_p . ", 1, 'Ass.', " . $row[12] . ", 0, 0.00)";
      echo "$sql<br>";
          
       if(!$conn->query($sql)) {
        	echo mysqli_error($conn);
          $okCommit=false;
        }
        
     if($okCommit) {
     	  // Elimino riga
         $sql = "DELETE FROM viaggi WHERE id = " . $row[0];
         echo "$sql<br>";
          
         if(!$conn->query($sql)) {
        	   echo mysqli_error($conn);
            $okCommit=false;
           }     	  
        }
  }

echo "<hr><br>";
if($okCommit) { // Tuto OK!
   $conn->query('commit');
   echo "Totale Viaggi elaborati = $ctrViaggi<br>";
   echo "Totale Righe elaborate = $ctrRighe<br>";
  }
else {
   $conn->query('rollback');
   echo "Failed<br>";
  }


?>
