<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Anagrafica gruppo aziendale</title>

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
*  Gestione dei gruppi aziendali
*
*  @file gestione_gruppo.php
*  @abstract Gestisce l'anagrafica dei gruppi aziendali
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-26
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-01-26
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
$table_name="gruppo";
$redirect="../php/gestione_gruppo.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlnote='';

$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_gruppo = "SELECT id,  descrizione
                                    FROM   gruppo";

if(!$multisottosezione) {
   $sqlselect_gruppo .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_gruppo .= " ORDER BY 2";

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

  if ($_POST) { // se post allora fase di modifica
      $update = true;
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                    switch($index) {
      		           case 0: // Table ID
      					        $sqlID = $value;
      					        break;
                    }
                   $index++;
                  }
                  
       $sql = "SELECT descrizione, id_sottosezione, note,
                                 DATE_FORMAT((data), '" .$date_format . "') data,
                                 utente FROM gruppo WHERE id = " . $sqlID;
                                 
       if($debug)
           echo "SQL = $sql";
       $result = $conn->query($sql);
       $row = $result->fetch_assoc();
       $sqldescrizione = $row["descrizione"];
       $sqlid_sottosezione = $row["id_sottosezione"];
       $sqlnote = $row["note"];
       $sqltimestamp= $row["data"];
       $sqlutente= $row["utente"];
     }
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Gruppi aziendali</td>";
  echo "</tr>";

// Campo per la ricerca del comune
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' id='id-hidden' name='id-hidden' value=" . $sqlID . ">"; 
  echo "<tr>";
  echo "<td><p class='search'>Digita Gruppo</td></p></td>";
  echo "<td><p class='search'><input class='search' id='searchTxt' list='groups' name='searchTxt' maxlength='100' size='110' type='value'  onKeyPress='return disableEnterKey(event)'/></p></td>";
  $result = $conn->query($sqlselect_gruppo);
  echo "<datalist id='groups'>";
  while($row = $result->fetch_assoc()) {
            echo "<option data-value=\"" . $row["id"] . "\">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End datalist
  echo "</datalist>";
  echo "</tr>";
  echo "</form>";

  if($update) {
      echo "<form action='../php/update_sql.php' method='POST'>";
      echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
     }
  else { 
      echo "<form action='../php/insert_sql.php' method='POST'>";
     }
  echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
  echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</td></p></td>";
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'><input disabled class='required' id='descrizione' maxlength='100' size='110' type='value' value='" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."' required/></p></td>";
     }
  else { 
      echo "<td><p class='required'><select class='required' name='id_sottosezione' required>" ;
      echo "<option value=''>--- Seleziona la sottosezione ---</option>";
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></p></td>'; 
     }
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Nome Gruppo</td></p></td>";
  echo "<td><p class='required'><input class='required' id='descrizione' maxlength='100' size='110' type='value' name='descrizione' value='" .  htmlentities($sqldescrizione, $defCharsetFlags, $defCharset) ."' required/></p></td>";
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
     echo  "<td class='button'><p><input class='md_btn' id='btn' type='submit' value='Aggiorna'></p></td>";
     echo "</form>";

     echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
     echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
     echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
     echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
     echo "<td class='elementi_lista'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldescrizione . " ?") ."');}\"></form></td>";
   	  }  
   else {
     echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'></p></form></td>";
     }
  echo "<tr>";
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
