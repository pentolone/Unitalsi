<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Men&ugrave; principale</title>
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
</head>
<body>
<?php
/****************************************************************************************************
*
*  Disegna i due frame dell'applicativo. NO BODY TAG!
*
*  @frame_set.php
*  @abstract Disegna i frame a seconda del numero di priorità configurate
*  @author Luca Romano
*  @version 1.0
*  @time 2016-07-28
*  @history first release
*  
*  @first 1.0
*  @since 2016-07-28
*  @CompatibleAppVer > 2.6
*  @where Las Palmas de Gran Canaria
*
*  Il frame menu' almeno di 130px che contiene fino a 6 priorita'
*
*
****************************************************************************************************/
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$tablename = 'utenti';

$debug=false;
$update=false;
$fname=basename(__FILE__); 

$frameMenuSize='130px';
$nPriStd=6;
$nPriDB=6;

$index=0;
$sqlID=0;
$sqluser='';
$sqlpwd='';
$sqlnome='';
$sqlcognome='';
$sqlcellulare='';
$sqlmail='';
$sqlsendmail=0;

config_timezone();
$current_user = ritorna_utente();
$idSoc = ritorna_societa_id();

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
echo <<<EOF
<html>
<head>
<meta charset="ISO-8859-1">
<meta name="author" content="Luca Romano" >

<title>Unitalsi Monza</title>
</head>

EOF;
disegna_menu();
echo "<div style='border: 0; position: relative; top: 100px; width: 50%; align: auto; text-align: center;'></div>";
//echo '<frameset rows="' . $frameMenuSize . ',*">';
//echo '<frame name="banner" src="../php/menu.php" frameborder="0"  border="no"></frame>';
//echo '<frame name="nav" src="../php/home_bottom.php"  frameborder="0"  border="no">';
//echo '</frameset>';
$conn->close();

?>
</body>
</html>
