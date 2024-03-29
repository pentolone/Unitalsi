<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Abilitazione livelli utente</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
  <link rel="apple-touch-icon" sizes="57x57" href="/images/fava/apple-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/images/fava/apple-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/images/fava/apple-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/images/fava/apple-icon-76x76.png">
  <link rel="apple-touch-icon" sizes="114x114" href="/images/fava/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120" href="/images/fava/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144" href="/images/fava/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="/images/fava/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/images/fava/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"  href="/images/fava/android-icon-192x192.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/images/fava/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="96x96" href="/images/fava/favicon-96x96.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/images/fava/favicon-16x16.png">
  <link rel="manifest" href="/images/fava/manifest.json">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="/images/fava/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">
<script type="text/javascript" >   
// Funzione per selezionare/deselezionare tutti i checkbox delle abilitazioni
      function toggleCheckBoxes(source, i) {
      	    var checkboxes=[0 ,0, 0, 0];
      	    checkboxes[0]=document.getElementById('q['+i+']');
      	    checkboxes[1]=document.getElementById('i['+i+']');
      	    checkboxes[2]=document.getElementById('u['+i+']');
      	    checkboxes[3]=document.getElementById('d['+i+']');
//      	    alert(checkboxes[0]);
//     	    alert(checkboxes.checked);
      	    checkboxes.checked=true;
      	    
      	    for(var i=0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
              }
           enableButton();
      }
      function verifyAllSelected(i) {
      	    var allChecked=document.getElementById('sAll['+i+']');
      	    var checkboxes=[0 ,0, 0, 0];
      	    var ctr;
      	    checkboxes[0]=document.getElementById('q['+i+']');
      	    checkboxes[1]=document.getElementById('i['+i+']');
      	    checkboxes[2]=document.getElementById('u['+i+']');
      	    checkboxes[3]=document.getElementById('d['+i+']');

      	    for(var i=0, ctr=0; i < checkboxes.length; i++) {
      	    	    if(checkboxes[i].checked)
      	    	       ctr++;
      	    	   } // End for
      	     if(ctr == checkboxes.length)
      	        allChecked.checked = true;
      	     else
      	        allChecked.checked = false;
      	    
      	//alert('a');

      }

</script> 
</head>
<body>

<?php
/****************************************************************************************************
*
*  Gestione abilitazione livelli applicativo
*
*  @file gestione_abilitazioni.php
*  @abstract Gestisce le abilitazioni dei livelli delle utenze
*  @author Luca Romano
*  @version 1.0
*  @time 2017-10-08
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-10-08
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$update = false;
$table_name="abilitazione_livello";
$redirect="../php/gestione_abilitazioni.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$msgAlert=null;

$desc_sottosezione='';

// Seleziono i programmi abilitati per il livello in fase di aggiornamento
$sqlselect_abilitazioni = "SELECT voci_menu.id idm,
                                                       voci_menu.label dm,
                                                       voci_menu.sequenza sm,
                                                       voci_sottomenu.id ids,
                                                       voci_sottomenu.label ds,
                                                       voci_sottomenu.sequenza ss,
                                                       abilitazione_livello.q,
                                                       abilitazione_livello.i,
                                                       abilitazione_livello.u,
                                                       abilitazione_livello.d
                                          FROM    voci_menu,
                                                       voci_sottomenu,
                                                       abilitazione_livello,
                                                       utenti
                                          WHERE  voci_sottomenu.id = abilitazione_livello.id_pagina
                                          AND      voci_sottomenu.id_menu = voci_menu.id
                                          AND      abilitazione_livello.id_livello = ? ";
 
// Union per mio livello (visualizzo voci presenti nel mio e non in quello selezionato)
$sqlselect_abilitazioni .=" UNION
                                          SELECT voci_menu.id idm,
                                                       voci_menu.label dm,
                                                       voci_menu.sequenza sm,
                                                       voci_sottomenu.id ids,
                                                       voci_sottomenu.label ds,
                                                       voci_sottomenu.sequenza ss,
                                                       0 q,
                                                       0 i,
                                                       0 u,
                                                       0 d
                                          FROM    voci_menu,
                                                       voci_sottomenu,
                                                       abilitazione_livello,
                                                       utenti
                                          WHERE  voci_sottomenu.id = abilitazione_livello.id_pagina
                                          AND      voci_sottomenu.id_menu = voci_menu.id
                                          AND      abilitazione_livello.id_livello = " . $_SESSION['livello_utente'] .
                                        " AND      abilitazione_livello.id_pagina NOT IN(SELECT id_pagina
                                                                                                                 FROM   abilitazione_livello
                                                                                                                 WHERE  id_livello = ?)";
                                          
// Union per admin (visibilita su tutto)
$sqlselect_abilitazioni .=" UNION
                                          SELECT voci_menu.id idm,
                                                       voci_menu.label dm,
                                                       voci_menu.sequenza sm,
                                                       voci_sottomenu.id ids,
                                                       voci_sottomenu.label ds,
                                                       voci_sottomenu.sequenza ss,
                                                       0 q,
                                                       0 i,
                                                       0 u,
                                                       0 d
                                          FROM    voci_menu,
                                                       voci_sottomenu
                                          WHERE  voci_sottomenu.id_menu = voci_menu.id
                                          AND      1 = " . $_SESSION["userid"] .                                  
                                        " ORDER BY 3, 2, 6, 5";
                                        
if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

$index=0;
$sqlID=0;
$sqluser='';
$sqlpwd='';
$sqlid_tipo=0;
$sqlid_livello=0;
$sqlmultisottosezione=false;
$sqlnome='';
$sqlcognome='';
$sqlcellulare='';
$sqlmail='';
$sqlsendmail=0;

$sqlselect_sottosezione = "SELECT 0, '\"\"' id, '--- Seleziona sottosezione di competenza ---' nome FROM DUAL
                             UNION SELECT 1, id, nome FROM sottosezione ORDER BY 1,3";

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 

  if ($_POST) { // se post allora ho cambiato i parametri di selezione
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                    switch($key) {

      		           case "id-hidden": // Table ID
      					        $sqlID = $value;
                           $update = true;
      					        break;

      		           case "id_sottosezione": // ID sottosezione
      					        $sqlid_sottosezione = $value;
      					        break;

      		           case "id_livello": // ID livello
      					        $sqlid_livello = $value;
      					        break;

      		           case "msg": // ID livello
      					        $msgAlert = $value;
      					        break;
                    }
                   $index++;
                  }
            }

  $sqlselect_livello = "SELECT id,
                                           descrizione
                               FROM   livello_utente
                               WHERE id_sottosezione = $sqlid_sottosezione
                               ORDER BY 2";
 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

  echo "<form name='sottosezione' action='" . $redirect . "' method='POST'>";
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione abilitazione livelli</td>";
  echo "</tr>";
  echo "</tr>";
  echo "<tr><td><p class='required'>Sottosezione di competenza</td></p>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" . htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><p class='required'><select name='id_sottosezione' class='required' required onChange='this.form.submit();'>";
      $result = $conn->query($sqlselect_sottosezione);

      while($row = $result->fetch_assoc()) {
       	      echo "<option value=" . $row["id"];
                if($row["id"] == $sqlid_sottosezione)  {
   	    	          echo " selected";
                  } 	
       	      echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
        	    }
       echo '</select></p></td>'; 
     }
  echo "</tr>";
  echo "</form>";

// Campo per la ricerca del dato
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
  echo "<tr>";
  echo "<td><p class='required'>Seleziona livello</p></td>";
  echo "<td><select class='required'  name='id_livello'  onChange='this.form.submit();'>";
  echo "<option value=0>--- Seleziona la voce da modificare ---</option>";
  $result = $conn->query($sqlselect_livello);
  while($row = $result->fetch_assoc()) {
            echo "<option value=" . $row["id"]; 
            if($row["id"] == $sqlid_livello)  {
   	    	      echo " selected";
              }
            echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";
      
   if($msgAlert) {
      	echo "<tr><td colspan='2'><p class='alert'>" . htmlentities($msgAlert, $defCharsetFlags, $defCharset) . "</p></td></tr>";
     }

  if($sqlid_livello > 0) { // Carico i dati del menu
      echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
      echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";
      echo "<tr>";
      echo "<td colspan='2'><hr></td>";
      echo "</tr>";
      
      if($debug) {
      	   echo "$fname; SQL Menu = $sqlselect_abilitazioni<br>";
      	   echo "$fname: Bind param (id_livello) x 2 = $sqlid_livello<br>";
      	   //return;
         }
      $stmt = $conn->prepare($sqlselect_abilitazioni);
      $stmt->bind_param("ii", $sqlid_livello, $sqlid_livello);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($sqlid_m,
                                      $sqldes_m,
                                      $a,
                                      $sqlid_s,
                                      $sqldes_s,
                                      $a,
                                      $sql_q,
                                      $sql_i,
                                      $sql_u,
                                      $sql_d);
      
      $index = 0;
      $old_idmenu = 0;    
      while($stmt->fetch()) {
      	          if($index == 0) { // Apro la form e la tabella dei menu
      	              echo "<form id='insertAbilitazioni'' action='../php/insert_abilitazioni.php' method='POST'>";
       	           echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
       	           echo "<input type='hidden' name='id_livello' value=$sqlid_livello>";
      	             
                    echo "<tr>";
      	              echo "<td colspan=2>";
      	              
      	              echo "<table>";
      	             }
      	             
      	          if($old_idmenu != $sqlid_m) { // Intestazione menu
      	              if($old_idmenu != 0) {
      	              	  echo "<tr>";
      	              	  echo "<td colspan=5><hr></td>";
      	              	  echo "</tr>";
      	                 }
      	              echo "<tr>";
      	              echo "<th style='text-align: left;'>";
      	              echo "Men&ugrave;&nbsp;&minus;&gt;&nbsp" . htmlentities($sqldes_m, $defCharsetFlags, $defCharset);
      	              echo "</th>";
      	              
      	              echo "<th colspan=4>";
      	              echo "Autorizzazioni";
      	              echo "</th>";
      	              echo "</tr>";
      	              $old_idmenu = $sqlid_m;
      	             }
      	             
      	          echo "<input type='hidden' name='id_pagina[]' value=$sqlid_s>";
      	          echo "<tr>";
      	          echo "<td><p class='search'><input type='checkbox' id='sAll[$index]' onClick='toggleCheckBoxes(this, " . $index . ");'>&nbsp;" .htmlentities($sqldes_s, $defCharsetFlags, $defCharset) . "</p></td>";
      	          
                //Query
      	          echo "<input type='hidden' name='q[$index]' value=0>";
      	          echo "<td><input type='checkbox' id='q[$index]' name='q[$index]' value=1";
      	          
      	          if($sql_q)
      	              echo " checked";
      	          echo " onClick='verifyAllSelected(" . $index . ");'><label for='q' style='font-size: 13px;'>Lettura</label></td>";
      	          
                //Insert
      	          echo "<input type='hidden' name='i[$index]' value=0>";
      	          echo "<td><input type='checkbox' id='i[$index]' name='i[$index]' value=1";
      	          
      	          if($sql_i)
      	              echo " checked";
      	          echo " onClick='verifyAllSelected(" . $index . ");'><label for='i' style='font-size: 13px;'>Inserimento</label></td>";
      	          
                //Update
      	          echo "<input type='hidden' name='u[$index]' value=0>";
      	          echo "<td><input type='checkbox' id='u[$index]' name='u[$index]' value=1";
      	          
      	          if($sql_u)
      	              echo " checked";
      	          echo " onClick='verifyAllSelected(" . $index . ");'><label for='u' style='font-size: 13px;'>Aggiornamento</label></td>";
      	          
                //Delete
      	          echo "<input type='hidden' name='d[$index]' value=0>";
      	          echo "<td><input type='checkbox' id='d[$index]' name='d[$index]' value=1";
      	          
      	          if($sql_d)
      	              echo " checked";
      	          echo " onClick='verifyAllSelected(" . $index . ");'><label for='d' style='font-size: 13px;'>Cancellazione</label></td>";
      	          
      	          echo "</tr>";
      	          echo "<script>verifyAllSelected(" . $index . ");</script>";
      	          $index++;

               } // End while fetch
               
      $stmt->close();
               
      if($index > 0) { // Chiudo tabella menu e form
          echo "</table></td></tr>";
          echo "<tr>";
          echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Aggiorna'></p></form></td>";
          echo "</tr>";
          echo "</form>";
         }
     }

  echo "</table>";
  echo "</div>";

$conn->close();

?>
</body>
</html>
