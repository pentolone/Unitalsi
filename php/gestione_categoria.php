<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione categoria</title>
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
*  Gestione della descrizione delle categorie
*
*  @file gestione_categoria.php
*  @abstract Gestisce la descrizione della categoria
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-23
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-01-23
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
$table_name="categoria";
$redirect="../php/gestione_categoria.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlnote='';

$desc_sottosezione='';

$sqlselect_categoria = "SELECT id,  descrizione
                                       FROM   categoria
                                       ORDER BY 2";

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
                  
       $sql = "SELECT descrizione, note,
                                 DATE_FORMAT((data), '" .$date_format . "') data,
                                 utente FROM categoria WHERE id = " . $sqlID;
                                 
       if($debug)
           echo "SQL = $sql";
       $result = $conn->query($sql);
       $row = $result->fetch_assoc();
       $sqldescrizione = $row["descrizione"];
       $sqlnote = $row["note"];
       $sqltimestamp= $row["data"];
       $sqlutente= $row["utente"];
     }
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     return;
     }

  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Categoria</td>";
  echo "</tr>";

// Campo per la ricerca del comune
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Seleziona Categoria</p></td>";
  echo "<td><select class='search' id='searchTxt' name='id-hidden'  onChange='this.form.submit();'>";
  $result = $conn->query($sqlselect_categoria);
  echo "<option value=>--- Seleziona la voce da modificare ---</option>";
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select></td>";
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
  echo "<td><p class='required'>Descrizione categoria</p></td>";
  echo "<td><input class='required' id='descrizione' maxlength='100' size='110' type='value' name='descrizione' value='" .  htmlentities($sqldescrizione, $defCharsetFlags, $defCharset) ."' required/></td>";
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
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldescrizione . " ?") ."');}\"></form></td>";
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

  echo "</div>";

$conn->close();

?>
</body>
</html>
