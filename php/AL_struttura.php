<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Strutture sottosezione</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
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
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
  
</head>
<body>

<?php
/****************************************************************************************************
*
*  Gestione dei dati delle strutture a disposizione della Sottosezione
*
*  @file AL_struttura.php
*  @abstract Gestisce la tabella delle strutture a disposiziine della sottosezione
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-08
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-03-08
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$debug=false;
$update=false;
$fname=basename(__FILE__);
$table_name="AL_struttura";
$redirect="../php/AL_struttura.php";

$sqlID=0;
$sqlid_sottosezione=0;
$sqlnome=null;
$sqlsede=null;
$sqlindirizzo=null;
$sqlcf_piva=null;
$sqlid_provincia=0;
$sqlcap=null;
$sqlcitta=null;
$sqltelefono=null;
$sqlfax=null;
$sqlcellulare=null;
$sqlsito_web=null;
$sqlemail=null;
$sqliban=null;

if(($userid = session_check()) == 0)
    return;

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_struttura = "SELECT 0, id, nome
                                       FROM   AL_struttura
                                       WHERE id_sottosezione = " . $sott_app; 
                                       
  if($multisottosezione) {
     $sqlselect_struttura .= " UNION
                                            SELECT 1, AL_struttura.id,
                                                         CONCAT(AL_struttura.nome,' - Sottosezione di ',sottosezione.nome) nome
                                            FROM    AL_struttura,
                                                          sottosezione
                                            WHERE  sottosezione.id = AL_struttura.id_sottosezione
                                            AND       AL_struttura.id_sottosezione != " . $sott_app; 
   }

$sqlselect_struttura .= " ORDER BY 1, 3";
config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();
$sqlid_sottosezione=$sott_app;

$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sqlselect_provincia = "SELECT id, CONCAT(nome,' (', sigla,')') nome
                                       FROM   province
                                       ORDER BY nome";

  if ($_POST) { // se post allora fase di modifica
      $update = true;
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
        $sql = "SELECT id, nome, sede, indirizzo, cap, citta, telefono, 
                    fax, cellulare, id_sottosezione, id_provincia, sito_web, email, cf_piva, iban,
                    DATE_FORMAT((data), '" .$date_format . "') data, utente
                    FROM " . $table_name . " WHERE id = " . $sqlID;

        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
    
        $sqlID = $row["id"];
        $sqlid_sottosezione = $row["id_sottosezione"];
        $sqlnome = $row["nome"];
        $sqlsede = $row["sede"];
        $sqlindirizzo = $row["indirizzo"];
        $sqlcf_piva = $row["cf_piva"];
        $sqlcap = $row["cap"];
        $sqlcitta = $row["citta"];
        $sqlid_provincia = $row["id_provincia"];
        $sqltelefono = $row["telefono"];
        $sqlfax = $row["fax"];
        $sqlcellulare = $row["cellulare"];
        $sqlsito_web = $row["sito_web"];
        $sqlemail = $row["email"];
        $sqliban = $row["iban"];
        $sqltimestamp = $row["data"];
        $sqlutente = $row["utente"];   
	   }
	}

  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Struttura a disposizione</td>";
  echo "</tr>";

// Campo per la ricerca della sottosezione
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' id='id-hidden' name='id-hidden' value=" . $sqlID . ">"; 
  echo "<tr>";
  echo "<td><p class='search'>Selezione Struttura</td></p></td>";
  echo "<td><p class='search'><select class='search' name='id-hidden' onChange='this.form.submit();'>";
  if($debug)
     echo "$fname SQL $sqlselect_struttura<br>";
  $result = $conn->query($sqlselect_struttura);

  echo "<option value=>--- Seleziona struttura per modificarla ---</option>";
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End datalist
  echo "</select></p></td>";
  echo "</tr>";
  echo "</form>";

  if($update) {
      echo "<form action='../php/update_sql.php' method='POST'>";
      echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
     }
  else { 
      echo "<form action='../php/insert_sql.php' method='POST'>";
      $sql = "SELECT id
                   FROM   sezione";

      if($debug)
         echo $sql;
      $result = $conn->query($sql); // Solo una riga
      $row = $result->fetch_assoc();

      $sqlid_sezione = $row["id"];                         
     }

  echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
  echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</td></p></td>";
  echo "<form name='changeSottosezione' action='" . $redirect . "' method='POST'>";
  echo "<td><p class='required'><select class='required' name='id_sottosezione'>" ;
  $result = $conn->query($sqlselect_sottosezione);
  while($row = $result->fetch_assoc()) {
           echo "<option value=" . $row["id"];
           if($row["id"] == $sqlid_sottosezione)  {
   	    	     echo " selected";
              } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
  echo '</select></p></td>'; 
  echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Nome/Ragione Sociale</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='100' maxlength='110' type='value' name='nome' value='" . htmlentities($sqlnome, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Sede</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='50' maxlength='40' type='value' name='sede' value='" . htmlentities($sqlsede, $defCharsetFlags, $defCharset) . "' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Indirizzo</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='55' maxlength='50' type='value' name='indirizzo' value='" . htmlentities($sqlindirizzo, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>C.F./Partita IVA</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='25' maxlength='20' type='value' name='cf_piva' value='" . htmlentities($sqlcf_piva, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>CAP</p></td>";
echo "<td><p class='required'><input class='required' id='nome' onkeypress='return event.charCode >= 48 && event.charCode <= 57' size='5' maxlength='5' type='value' name='cap' value='" . $sqlcap ."' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Citt&agrave</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='50' type='value' name='citta' value='" . htmlentities($sqlcitta, $defCharsetFlags, $defCharset) . "' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Provincia</p></td>";
echo "<td><p class='required'><select class='required' name='id_provincia' required>";
echo "<option value=''>--- Seleziona la provincia ---</option>";
$result = $conn->query($sqlselect_provincia);

while($row = $result->fetch_assoc()) {
   	    echo "<option value=" . $row["id"];
   	    if($row["id"] == $sqlid_provincia)
   	        echo " selected";
   	    echo ">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
   	
} 
//$conn->close();
echo "</select>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Telefono</p></td>";
echo "<td><p><input  id='nome' size='25' maxlength='20' type='value' name='telefono' value='" . htmlentities($sqltelefono, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Fax</p></td>";
echo "<td><p><input id='nome' size='25' maxlength='20' type='value' name='fax' value='" . htmlentities($sqlfax, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Cellulare</p></td>";
echo "<td><p><input id='nome' size='25' maxlength='20' type='value' name='cellulare' value='" . htmlentities($sqlcellulare, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Sito WEB</p></td>";
echo "<td><p><input id='nome' size='55' maxlength='50' type='value' name='sito_web' value='" . htmlentities($sqlsito_web, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>E-mail</p></td>";
echo "<td><p><input id='nome' size='30' type='value' name='email' value='" . htmlentities($sqlemail, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>IBAN di appoggio</p></td>";
echo "<td><p><input style='text-transform:uppercase' size='30' type='value' name='iban' value='" .htmlentities($sqliban, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan='2'><hr></td>";
echo "</tr>";
   
echo "<tr>";  
  if($update) {
     echo "<input type='hidden' name='utente' value='" . $current_user ."'>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     echo "<tr>";
     echo  "<td class='button'><p><input class='md_btn' id='btn' type='submit' value='Aggiorna'></p></td>";
     echo "</form>";
     
     
     echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
     echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
     echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
     echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
     echo "<td class='elementi_lista'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqlnome . " ?") ."');}\"></form></td>";

     }
else {
     echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'></p></form></td>";
     }
echo "</tr>";
echo "</table>";

?>
</div>
</body>
</html>
