<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione mezzi di trasporto disponibili</title>
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
*  Gestione dei mezzi disponibili alla sottosezione
*
*  @file gestione_mezzi_disponibili.php
*  @abstract Gestisce i mezzi disponibili alla sottosezione
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-06
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-06
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
$table_name="mezzi_disponibili";
$redirect="../php/gestione_mezzi_disponibili.php";

$sqlID=0;
$sqlid_sottosezione=$sott_app;
$sqlid_mezzo=0;
$sqlcapienza=0;
$sqltarga=null;
$sqldescrizione='';
$sqlnote='';

$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";


$sqlselect_mezzi = "SELECT id, descrizione
                                 FROM   mezzi_trasporto
                                 ORDER BY 2";

$sqlselect_disponibili = "SELECT 0, id,
                                                      descrizione
                                         FROM    mezzi_disponibili
                                         WHERE  id_sottosezione = " . $sott_app;

if($multisottosezione) {
    $sqlselect_disponibili .= " UNION
                                               SELECT 1, mezzi_disponibili.id,
                                               CONCAT(descrizione,' (Sottosezione di ' , sottosezione.nome,')') descrizione
                                               FROM    mezzi_disponibili,
                                                             sottosezione
                                               WHERE  mezzi_disponibili.id_sottosezione = sottosezione.id
                                               AND      id_sottosezione != " . $sott_app; 
}

$sqlselect_disponibili .= " ORDER BY 1,3";

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
                  
       $sql = "SELECT id_sottosezione,
                                 id_mezzo,
                                 capienza,
                                 targa,
                                 descrizione,
                                 note,
                                 DATE_FORMAT((data), '" .$date_format . "') data,
                                 utente FROM " . $table_name . " WHERE id = " . $sqlID;
                                 
       if($debug)
           echo "$fname SQL = $sql<br>";
       $result = $conn->query($sql);
       $row = $result->fetch_assoc();
       $sqlid_sottosezione = $row["id_sottosezione"];
       $sqlid_mezzo = $row["id_mezzo"];
       $sqlcapienza = $row["capienza"];
       $sqltarga = $row["targa"];
       $sqldescrizione = $row["descrizione"];
       $sqlnote = $row["note"];
       $sqltimestamp= $row["data"];
       $sqlutente= $row["utente"];
     }
  disegna_menu();
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione); 
  echo "<div class='background' style='position: absolute; top: 100px;'>";
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body>";
     echo "</html>";
     return;
     }

  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione mezzi di trasporto disponibili</td>";
  echo "</tr>";

// Campo per la ricerca della descrizione
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Seleziona Mezzo di trasporto</p></td>";
  echo "<td><p class='search'><select  class='search' name='id-hidden' onChange='this.form.submit();'/>";
  echo "<option value=>--- Seleziona mezzo per modificarlo ---</option>";
  $result = $conn->query($sqlselect_disponibili);
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select></p></td>";
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
  echo "<td><p class='required'>Sottosezione</p></td>";

  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' required>" ;
      echo "<option value=''>--- Seleziona la sottosezione ---</option>";
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></td>'; 
     }
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Tipo mezzo</p></td>";
  echo "<td><select class='required' name='id_mezzo' required>" ;
  echo "<option value=''>--- Seleziona tipo di mezzo ---</option>";
  $result = $conn->query($sqlselect_mezzi);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id"];
             if($row["id"] == $sqlid_mezzo)  {
   	    	       echo " selected";
                } 	
       	    echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . "</option>";
       	   }
  echo '</select></td>'; 
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Descrizione</p></td>";
  echo "<td><input class='required' id='descrizione' maxlength='100' size='110' type='value' name='descrizione' value='" .  htmlentities($sqldescrizione, $defCharsetFlags, $defCharset) ."' required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Capienza</p></td>";
  echo "<td><p class='required'><input class='numeror' type='number' id='descrizione' maxlength='4' size='4' type='value' min='0' max='9999' step='1' name='capienza' value=" .  $sqlcapienza  ." required/>";
  echo "&nbsp;Selezionare zero per non controllare la capienza</p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Targa</p></td>";
  echo "<td><p><input id='targa' maxlength='10' size='10' name='targa' value='" .  htmlentities($sqltarga, $defCharsetFlags, $defCharset) ."'/></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Note</p></td>";
  echo "<td><p><textarea name='note' maxlength='300'>" .  htmlentities($sqlnote, $defCharsetFlags, $defCharset) . "</textarea></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

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
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldescrizione . " ?") ."');}\"></td>";
         echo "</form>";
   	    }  
     echo "</tr></table></td></tr>";
  	  }  
   else {
     if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
        echo  "<tr><td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'></td>";
        echo "</form>";
        echo "</tr>";
      }
     }
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
