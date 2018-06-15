<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Utenti applicativo</title>
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
*  Gestione utenti dell'applicativo
*
*  @file gestione_utenti.php
*  @abstract Gestisce le tipologie degli impianti
*  @author Luca Romano
*  @version 1.1
*  @time 2016-07-23
*  @history (1.1) solo bug fixing
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer All
*  @where Las Palmas de Gran Canaria
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
$table_name="utenti";
$redirect="../php/gestione_utenti.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlnote='';

$desc_sottosezione='';

$sqlselect_utenti = "SELECT 0, id,  CONCAT(cognome, ' ', nome) username
                                  FROM   utenti
                                  WHERE id > 1
                                  AND id_sottosezione = $sqlid_sottosezione";
                                  
if($multisottosezione) {
	$sqlselect_utenti .= " UNION
	                                  SELECT 1, utenti.id,
	                                               CONCAT(utenti.cognome, ' ', utenti.nome, ' (Sottosezione di ' , sottosezione.nome, ')') username
                                  FROM   utenti,
                                              sottosezione
                                  WHERE utenti.id_sottosezione != $sqlid_sottosezione
                                  AND     utenti.id_sottosezione = sottosezione.id"; 
 }
$sqlselect_utenti.= " ORDER BY 1, 3";

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
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione); 

  if ($_POST) { // se post allora fase di modifica o cambio sottosezione
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
                    }
                   $index++;
                  }  // End Foreach               
         } // End POST
         
   if ($update) { // Aggiorno la riga
      $sql = "SELECT username, pwd, id_livello, id_sottosezione, multisottosezione,nome, cognome, 
                                 cellulare, email, sendmail,
                                 DATE_FORMAT((data), '" .$date_format . "') data, utente
                    FROM ". $table_name . " WHERE id = " . $sqlID;
       $result = $conn->query($sql);
       $row = $result->fetch_assoc();
       $sqluser= $row["username"];
       $sqlpwd= $row["pwd"];
       $sqlid_livello= $row["id_livello"];
       $sqlid_sottosezione= $row["id_sottosezione"];
       $sqlnome= $row["nome"];
       $sqlmultisottosezione= $row["multisottosezione"];
       $sqlcognome= $row["cognome"];
       $sqlcellulare= $row["cellulare"];
       $sqlmail= $row["email"];
       $sqlsendmail= $row["sendmail"];
       $sqltimestamp = $row["data"];
       $sqlutente = $row["utente"];
     }

$sqlselect_livello = "SELECT 0, '\"\"' id, '--- Seleziona livello ---' descrizione FROM DUAL
                                  UNION
                                  SELECT 1,
                                               id, descrizione
                                  FROM    livello_utente
                                  WHERE  id_sottosezione = $sqlid_sottosezione
                                  ORDER BY 1,3";

  disegna_menu();
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
  echo "<td colspan='2' class='titolo'>Gestione Utenti applicativo</td>";
  echo "</tr>";

// Campo per la ricerca del dato
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Seleziona utente</p></td>";
  echo "<td><select class='search'  name='id-hidden'  onChange='this.form.submit();'>";
  echo "<option value=0>--- Seleziona la voce da modificare ---</option>";
  $result = $conn->query($sqlselect_utenti);
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["username"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
  echo "<tr><td><p class='required'>Sottosezione di competenza</td></p>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" . htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><p class='required'><select name='id_sottosezione' class='required' required  onChange='this.form.submit();'>";
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

  if(!$multisottosezione) {
      echo "<input type='hidden' name='multisottosezione' value='0'>";
     }
  else { 
      echo "<tr>";
      echo "<td><p>Abilitato a gestire multiple sottosezioni</p></td>";
      echo "<td> <p>";
      echo "<input type='hidden' name='multisottosezione' value='0'>";
      echo "<input type='checkbox' name='multisottosezione' value='1'";
      if($sqlmultisottosezione)
          echo " checked='true'>";
      else 
          echo ">";
      echo "</p></td>";
      echo "</tr>";
     }

  echo "<tr>";

  if(!$update) {   // Username e password solo in caso di inserimento
      echo "<tr>";
      echo "<td><p class='required'>Username</p></td>";
      echo "<td> <p class='required'>";
      echo "<input class='required' size='15' maxlength='10' type='value' name='username' value='' required/></p></td>";
      echo "</tr>";

      echo "<tr>";
      echo '<td><p class="required">Password</td></p><td><p class="required">';
      echo '<input class="required" size="20" type="password" name="pwd" required/></p></td>';
      echo '</tr>';
    }
  else {
      echo "<td><p class='required'>Username (non modificabile)</p></td>";
      echo "<td><p class='required'>";
      echo htmlentities($sqluser, $defCharsetFlags, $defCharset) . "</p></td>";
      echo "</tr>";
    }

  echo "<tr>";
  echo "<td><p class='required'>Livello</p></td>";
  echo "<td><p class='required'><select name='id_livello' class='required' required>";
  $result = $conn->query($sqlselect_livello);
  while($row = $result->fetch_assoc()) {
         	  echo "<option value=" . $row["id"];
            if($row["id"] == $sqlid_livello)  {
   	    	      echo " selected";
               } 	
       	echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></p></td></tr>'; 

  echo "<tr>";
  echo "<td><p class='required'>Nome</p></td>";
  echo "<td><p class='required'>";
  echo "<input class='required' size='65' maxlength='60' type='value' name='nome' value='". htmlentities($sqlnome, $defCharsetFlags, $defCharset) . "' required/></p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p class='required'>Cognome</p></td>";
  echo "<td><p class='required'>";
  echo "<input class='required' size='65' maxlength='60' type='value' name='cognome' value='" . htmlentities($sqlcognome, $defCharsetFlags, $defCharset) . "' required/></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Cellulare</p></td>";
  echo "<td> <p class='required'>";
  echo "<input class='required' size='25' maxlength='20' type='value' name='cellulare' value='" . htmlentities($sqlcellulare, $defCharsetFlags, $defCharset) . "' required/></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Email</p></td>";
  echo "<td><p>";
  echo "<input  size='45' maxlength='40' type='value' name='email' value='". $sqlmail . "'/></p></td>";
  echo "</tr>";


  echo "<tr>";
  echo "<td><p>Invia email</p></td>";
  echo "<td><p>";
  echo "<input type='hidden' name='sendmail' value='0'>";
  echo "<input type='checkbox' name='sendmail' value='1'";
  if($sqlsendmail)
     echo " checked='true'>";
  else 
     echo ">";

  echo "&nbsp;(Inserire un indirizzo email valido)</p></td>";
  
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  if($update) {
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
         echo "<tr>";
         echo  "<td colspan='2' class='button'><input class='in_btn' id='btn' type='submit' value='Inserisci'></td>";
         echo "</tr></form>";
        }
     }
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
