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
<!-- Controllo il database e permetto l'inserimento solo se la tabella societa è vuota -->
<?php
/****************************************************************************************************
*
*  Gestione dei dati della Societa'
*
*  @file gestione_societa.php
*  @abstract Gestisce la tabella della societa UNITALSI
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-23
*  @history 1.0 prima versione
*  
*  @first 1.0
*  @since 2017-01-23
*  @CompatibleAppVer >= 1.0
*  @where Monza
*
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$update=false;

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

config_timezone();
$current_user = ritorna_utente();
$date_format=ritorna_data_locale();

$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$totResult = $conn->query("SELECT COUNT(*) AS tot FROM societa");

$sqlProv = "SELECT id, sigla, nome FROM province ORDER BY nome";

$totRows = $totResult->fetch_assoc();

if($totRows["tot"] == 0) { // Tabella vuota, possiamo permettere l'inserimento

	echo "<form action='../php/insert_sql.php' method='POST'>";
	echo "<input type='hidden' name='redirect' value='../php/index.php'>";
	echo "<input type='hidden' name='table_name' value='societa'>";

}
else { // Dati societari esistenti
    $update=true;
    $sql = "SELECT id, nome, sede, indirizzo, cap, citta, telefono, 
                fax, cellulare, id_provincia, sito_web, email, cf_piva, iban,
                DATE_FORMAT((data), '" .$date_format . "') data, utente
                FROM societa";

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $sqlid = $row["id"];
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
   
	 echo "<form action='../php/update_sql.php' method='POST'>";
    echo "<input type='hidden' name='redirect' value='gestione_societa.php'>";
    echo "<input type='hidden' name='table_name' value='societa'>";
    echo "<input type='hidden' name='id' value='" . $row["id"] . "'>";
	}
 

echo "<table>";
echo "<tr>";
echo "<td colspan='2' class='titolo'>Gestione Societ&agrave;</td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Nome/Ragione Sociale</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='100' maxlength='110' type='value' name='nome' value='" . htmlspecialchars($sqlnome, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Sede</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='50' maxlength='40' type='value' name='sede' value='" . htmlspecialchars($sqlsede, $defCharsetFlags, $defCharset) . "' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Indirizzo</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='50' maxlength='40' type='value' name='indirizzo' value='" . htmlspecialchars($sqlindirizzo, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>C.F./Partita IVA</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='25' maxlength='20' type='value' name='cf_piva' value='" . htmlspecialchars($sqlcf_piva, $defCharsetFlags, $defCharset) . "'  required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>CAP</p></td>";
echo "<td><p class='required'><input class='required' id='nome' onkeypress='return event.charCode >= 48 && event.charCode <= 57' size='5' maxlength='5' type='value' name='cap' value='" . $sqlcap ."' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Citt&agrave</p></td>";
echo "<td><p class='required'><input class='required' id='nome' size='50' type='value' name='citta' value='" . htmlspecialchars($sqlcitta, $defCharsetFlags, $defCharset) . "' required/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p class='required'>Provincia</p></td>";
echo "<td><p class='required'><select class='required' name='id_provincia'>";
$result = $conn->query($sqlProv);

while($row = $result->fetch_assoc()) {
   	    echo "<option value='" . $row["id"] . "'";
   	    if($row["id"] == $sqlid_provincia)
   	        echo " selected";
   	    echo ">" . htmlspecialchars($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
   	
} 
$conn->close();
echo "</select>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Telefono</p></td>";
echo "<td><p><input  id='nome' size='25' maxlength='20' type='value' name='telefono' value='" . htmlspecialchars($sqltelefono, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Fax</p></td>";
echo "<td><p><input id='nome' size='25' maxlength='20' type='value' name='fax' value='" . htmlspecialchars($sqlfax, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Cellulare</p></td>";
echo "<td><p><input id='nome' size='25' maxlength='20' type='value' name='cellulare' value='" . htmlspecialchars($sqlcellulare, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>Sito WEB</p></td>";
echo "<td><p><input id='nome' size='35' maxlength='30' type='value' name='sito_web' value='" . htmlspecialchars($sqlsito_web, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>E-mail</p></td>";
echo "<td><p><input id='nome' size='30' type='value' name='email' value='" . htmlspecialchars($sqlemail, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";

echo "<tr>";   
echo "<td><p>IBAN</p></td>";
echo "<td><p><input style='text-transform:uppercase' size='30' type='value' name='iban' value='" . htmlspecialchars($sqliban, $defCharsetFlags, $defCharset) . "'/></p></td>";
echo "</tr>";
   
echo "<tr>";  
  if($update) {
     echo "<input type='hidden' name='utente' value='" . $current_user ."'>";
     echo  "<td colspan='2' class='button'><p><input class='md_btn' id='btn' type='submit' value='Aggiorna'></p></td>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     }
else {
     echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'></p></td>";
     }
echo "</tr>";
echo "</table>";

?>
</body>
</html>
