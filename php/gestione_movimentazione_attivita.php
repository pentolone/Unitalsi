<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione movimentazione attivit&agrave;</title>
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
</head>
<body>

<?php
/****************************************************************************************************
*
*  Gestione delle attivita' durante l'anno
*
*  @file gestione_movimentazione_attivita.php
*  @abstract Gestisce le attivita' e la loro durata
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-21
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-21
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="attivita_m";
$redirect="../php/gestione_movimentazione_attivita.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_attivita=0;
$sqlanno=date('Y');
$sqldal='';
$sqlal='';
$sqlnote='';

$arrayValues=array();
$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_attivita = "SELECT attivita.id,  attivita.descrizione, IFNULL(SUM(costi.costo),0) costo
                                   FROM   attivita 
                                  LEFT JOIN costi ON (attivita.id = costi.id_attpell
                                   AND tipo = 'A'
                                   AND id_parent = 0)";

$sqlselect_desa = "SELECT 0, attivita_m.id,  attivita.descrizione, anno
                                FROM   attivita_m,
                                            attivita
                                WHERE  attivita_m.id_sottosezione = " . $sott_app .
                                " AND  attivita.id = attivita_m.id_attivita
                                AND anno = $sqlanno"; 
 
 if($multisottosezione) {
        $sqlselect_desa .= " UNION
                                          SELECT 1, attivita_m.id,
                                          CONCAT(attivita.descrizione,' (Sottosezione di ' , sottosezione.nome,')') descrizione, anno
                                          FROM    attivita_m,
                                                        attivita,
                                                       sottosezione
                                          WHERE  attivita_m.id_sottosezione = sottosezione.id
                                          AND      attivita_m.id_sottosezione != " . $sott_app .
                                " AND  attivita.id = attivita_m.id_attivita
                                AND anno = $sqlanno"; 
 }
$sqlselect_desa.= " ORDER BY 1,3";

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
// Verifica abilitazioni utente
$authMask = ritorna_abilitazioni($conn);

$ctrAuth=0;
foreach ($authMask as $key => $value) {
               if($debug) { // Visualizzo autorizzazioni
                   echo "$fname Auth -> $key = $value<br>";
                  } // end foreach
               $ctrAuth += $value; // Controllo autorizzazioni
   }
// Fine verifica abilitazioni

  if ($_POST) { // se post allora fase di modifica o cambio sottosezione
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                   switch($key) {

      		           case "id-hidden": // Table ID
      					        $sqlID = $value;
      					        $update = true;
      					        break;

      		           case "id_sottosezione": // Cambio sottosezione
      					        $sqlid_sottosezione = $value;
      					        $update = false;
      					        break;
                    }
                   $index++;
                  }
                  
      if($update) {
	       $sql = "SELECT descrizione, id_sottosezione, anno, dal,al, note,
                                 DATE_FORMAT((data), '" .$date_format . "') data,
                                 id_attivita,
                                 utente FROM " . $table_name . " WHERE id = " . $sqlID;
                                 
         if($debug)
           echo "$fname SQL = $sql<br>";
         $result = $conn->query($sql);
         $row = $result->fetch_assoc();
         $sqldescrizione = $row["descrizione"];
         $sqlid_sottosezione = $row["id_sottosezione"];
         $sqlid_attivita = $row["id_attivita"];
         $sqlanno = $row["anno"];

         $sqldal = $row["dal"];
         $sqlal = $row["al"];
         $sqlnote = $row["note"];
         $sqltimestamp= $row["data"];
         $sqlutente= $row["utente"];
        }
     }

  $sqlq_attivita = $sqlselect_attivita . " WHERE  id_sottosezione = " . $sqlid_sottosezione . 
                             "                                 GROUP BY 1,2 ORDER BY 2"; 

  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione delle attivit&agrave;</td>";
  echo "</tr>";

// Campo per la ricerca della descrizione da modificare
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
//  echo "<input type='hidden' id='id-hidden' name='id-hidden' value=" . $sqlID . ">"; 
  echo "<tr>";
  echo "<td><p class='search'>Seleziona attivit&agrave;</td></p></td>";
  echo "<td><p class='search'><select class='search' name='id-hidden' onChange='this.form.submit();'>";
  echo "<option value=0>--- Selezione la voce da modificare ---</option>";
  
  if($debug)
      echo "$fname SQL datalist = $sqlselect_desa<br>";

  $result = $conn->query($sqlselect_desa);
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select>";
  echo "</td></tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<form name='changeSottosezione' action='" . $redirect . "' method='POST'>";
      echo "<td><p class='required'><select class='required' name='id_sottosezione' required onChange='this.form.submit();'>" ;
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></p></td>'; 
       echo '</form></p></td>'; 
     }
  echo "</tr>";

  if($update) {
      echo "<form action='../php/update_sql.php' method='POST'>";
      echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
     }
  else { 
      echo "<form action='../php/insert_sql.php' method='POST'>";
     }
  echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
  echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='descrizione' value='" . $sqldescrizione . "'>";

  echo "<tr>";
  echo "<td><p class='required'>Descrizione attivit&agrave;</p></td>";
  
  echo "<td><p><select class='required' id='id_attivita' name='id_attivita' required onChange=\"setHidden(this.options[this.selectedIndex].value);\">";
  echo "<option value=>--- Selezione descrizione attivita ---</option>";
 
   if($debug)
       echo "$fname SQL select = $sqlq_attivita<br>";
      
   $result = $conn->query($sqlq_attivita);
   while($row = $result->fetch_assoc()) {
       	    echo "<option value=" . $row["id"];
              if($row["id"] == $sqlid_attivita)  {
   	    	         echo " selected";
   	    	         $sqldesa = $row["descrizione"];
                 }
               else {
                   if($sqlid_attivita > 0)
                        echo " disabled";
                    }
          
       	echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . " &minus;&gt; (Costo delle voci principali &euro; " . $row["costo"] .")</option>";
       	}
  echo "</select></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Eventuale descrizione aggiuntiva</td></p></td>";
  echo "<td><p><input class='field' name='descrizione' maxlength=100 size=105 value='" .htmlentities($sqldescrizione,$defCharsetFlags, $defCharset) ."'/>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Anno di competenza</td></p></td>";
  echo "<td><p class='required'><input class='required' name='anno' type=number min='2000' max='9999' maxlength=4 value=" .$sqlanno ." required/>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Dal</td></p></td>";
  echo "<td><p class='required'><input class='required' name='dal' type='date' value='" .$sqldal ."' required/>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Al</td></p></td>";
  echo "<td><p class='required'><input class='required' name='al' type='date' value='" .$sqlal ."' required/>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Note</p></td>";
  echo "<td><p><textarea name='note' maxlength='300'>" .  htmlentities($sqlnote, $defCharsetFlags, $defCharset) . "</textarea></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  if($update) {
     echo "<input type='hidden' name='utente' value='" . $current_user ."'>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td colspan='2'><table style='width: 100%;'><tr>";

     if($authMask["update"]) {
         echo  "<td class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'></td>";
        }
     echo "</form>";

     if($authMask["delete"]) {
         echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
         echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
         echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
         echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldesa . " ?") ."');}\"></form></td>";
   	     }  
   	   echo "</tr></table></td></tr>";
   	    }  
   else {
     if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
         echo  "<td colspan='2' class='button'><input class='in_btn' id='btn' type='submit' value='Inserisci'></form></td>";
        }
     }
  echo "<tr>";
  echo "</table>";
//var_dump($arrayValues); 

  echo "</div>";
$conn->close();

?>
<script type="text/javascript">
// Set hidden value to complete insert without any error
function setHidden(key) {

    var jArray= <?php echo json_encode($arrayValues ); ?>;
    desc = document.getElementsByName("descrizione");
    desc[0].value=jArray[key][0];

    desc = document.getElementsByName("voce_costo1");
    desc[0].value=jArray[key][1];

    desc = document.getElementsByName("costo1");
    desc[0].value=jArray[key][2];

    desc = document.getElementsByName("voce_costo2");
    desc[0].value=jArray[key][3];

    desc = document.getElementsByName("costo2");
    desc[0].value=jArray[key][4];

    desc = document.getElementsByName("voce_costo3");
    desc[0].value=jArray[key][5];

    desc = document.getElementsByName("costo3");
    desc[0].value=jArray[key][6];
   
    //for(var i=0;i<6;i++){
        //alert(jArray[key][0]);
}

 </script>
</body>
</html>
