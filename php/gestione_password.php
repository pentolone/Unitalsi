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
*  Gestione la modifica della password
*
*  @file gestione_password.php
*  @abstract Gestione la modifica della password
*  @author Luca Romano
*  @version 1.1
*  @time 2016-07-23
*  @history solo bug fixing
*  
*  @first 1.0
*  @since 2016-04-22
*  @CompatibleAppVer All
*  @where Las Palmas de Gran Canaria
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$debug=false;
$fname=basename(__FILE__); 

$update=false;

$tablename = 'utenti';
$titolo='';
$index=0;

$sqlID=0;
$sqloldpw='';
$sql='';
$sqlnome='';
$sqlcognome='';

if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();
$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Prendo ID utente
if ($_POST) { // se post chiamata correttamente
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
       }
else { // Controllo query string
	$hrefString = $_SERVER['QUERY_STRING'];

// Prendo id dell'utente
   parse_str($hrefString, $dest);
   $sqlID = $dest["id"];

}
if ($sqlID < 1 || $sqlID==null) { // chiamata sbagliata
     echo "<H1>Chiamata NON riuscita</H1>";
     echo "</body>";
     echo "</html>";
     return;
       }


$sql = "SELECT nome, cognome FROM " . $tablename . " WHERE id = " . $sqlID;

$result = $conn->query($sql);
$row = $result->fetch_assoc();
$titolo = $row["nome"] . " " . $row["cognome"];
$conn->close(); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";

 ?>
    <form action="../php/update_password.php" method='post'>
   <input type='hidden' name='id' value="<?php echo $sqlID ?>">
   <table>
   <tr>
   <td colspan='2' class='titolo'>Cambio password per (<?php echo htmlspecialchars($titolo, $defCharsetFlags, $defCharset) ?>)</td>
   </td>
   </tr>

   <tr>   
   <td><p class='required'>Password attuale</p></td>
   <td><p class='required'><input class='required' id='nome' size='40' maxlength='30' type='password' name='oldpwd' value='' required autofocus/></p></td>
   </tr>

   <tr>   
   <td><p class='required'>Nuova password</p></td>
   <td><p class='required'><input class='required' id='nome' size='40' maxlength='30' type='password' name='pwd' value='' required/></p></td>
   </tr>

   <tr>   
   <td><p class='required'>Conferma password</p></td>
   <td><p class='required'><input class='required' id='nome' size='40' maxlength='30' type='password' name='pwd_c' value='' required/></p></td>
   </tr>

   <tr>   
    <td  class='button' colspan='2'><p><input class='md_btn' id='btn' type='submit' value='Aggiorna'></p></td>
   </tr>
  </table>
  </form>
  </body>
</html>
