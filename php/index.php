<?php
include '../php/unitalsi_include_common.php' ;
 $vers = ritorna_versione(); 
 ?>
<!DOCTYPE HTML PUBLIC€œ-//W3C//DTD HTML 4.01 Frameset//IT €œhttp://www.w3.org/TR/html4/frameset.dtd>
<html>
<head>
<title>Unitalsi Monza - Login</title>
<meta charset="ISO-8859-1">
<meta name="author" content="Luca Romano" >
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
</head>
<body>

<form action="../php/login.php" method="post">
<table class="login">
<th class="login" colspan="2">Unitalsi Monza</th>
<tr>
<td colspan='2' class="login">Versione <?php echo $vers ?></td>
</tr>

<tr>
<p class='required'><td class="login">Utente</td><td><input class='required' name="username" autocomplete="off" autofocus required/></td></p>
</tr>

<tr>
<td class="login">Password</td><td><input class='required' type="password" name="pwd" required/></td>
</tr>
<tr>
<td class='login' colspan="2"><input class='login_bt' type="submit" value="Login"></td></tr>

<tr>
<td class='login' colspan="2">Running on (<?php echo gethostname(); ?>)</td></tr>
</table>
</form>

<div style='position: fixed; bottom: 5px;'>
<table align="right" class='info'>

<tr>
<td  colspan="2" align='right' style='font-weight: normal; font-family: Arial; font-style: italic; font-size: 10px;'>
<a href='mailto:luke.romano@gmail.com'>Luca Romano</a> - 2016-2018. Tutti i diritti riservati</td>
</tr>
</table>
</div>
</body>
</html>