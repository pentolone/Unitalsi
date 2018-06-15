<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Invia e-mail ai soci</title>
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
*  Invia mail ai soci
*
*  @file q_invia_mail.php
*  @abstract Invia mail ai soci
*  @author Luca Romano
*  @version 1.1
*  @time 2017-10-01
*  @history (1.2) aggiunto ulteriori campi di selezione
*  @history (1.1) aggiunto campi di selezione
*  
*  @first 1.0
*  @since 2017-03-17
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$fname=basename(__FILE__);
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();
$sqlid_sottosezione=$sott_app;
$multisottosezione = ritorna_multisottosezione();

$index=0;
$redirect="../php/q_invia_mail.php";
$mail_target="../php/spedisci.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlid_gruppo_par=0;
$sqlid_attivita=0;
$sqlid_pellegrinaggio=0;
$sql_effettivo=0;
$sqlid_categoria=0;
$sqlid_classificazione=0;
$sqlid_socio=0;
$sqltipo='N';

$desc_sottosezione='';

$sqlselect_socio = "SELECT id, CONCAT(cognome,' ', nome) nome,
                                             email
                                FROM    anagrafica 
                                WHERE  email IS NOT NULL
                                AND     LENGTH(email) > 5";

$sqlselect_gruppo_par = "SELECT id, descrizione
                                          FROM   gruppo_parrocchiale
                                          WHERE 1";

if(!$multisottosezione) {
   $sqlselect_gruppo_par .= " AND id_sottosezione = " . $sott_app; 
   $sqlselect_socio .= " AND id_sottosezione = " . $sott_app; 
 }

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

if(($userid = session_check()) == 0)
    return;

config_timezone();
$current_user = ritorna_utente();


$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  if ($_POST) { // se post allora ho modificato i valori di selezione

      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";

                     switch($key) {
      		                     case "id_old": // sottosezione precedente
      					                    $sqlid_old = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                      case "id_gruppo_par": // gruppo parrocchiale
      					                    $sqlid_gruppo_par = $value;
      					                    break; 

      		                      case "id_attivita": // ID attivita'
      					                    $sqlid_attivita = $value;
      					                    $sqlid_pellegrinaggio=0;
      					                    $sqltipo = 'A';
      					                    break;

      		                      case "id_pellegrinaggio": // ID attivita'
      					                    $sqlid_pellegrinaggio = $value;
      					                    $sqlid_attivita = 0;
       					                 $sqltipo = 'V';
     					                    break;

      		                      case "id_socio": // ID anagrafica
      					                    $sqlid_socio = $value;
      					                    break;
                    }
                  }
     }

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlid_gruppo_par=0;
  	  $sqlid_socio = 0;
  	  $sqlid_attivita = 0;
  	  $sqlid_pellegrinaggio = 0;
    }
    
  $sqlselect_gruppo_par .= " AND id_sottosezione = " . $sqlid_sottosezione;
  $sqlselect_socio .= " AND id_sottosezione = " . $sqlid_sottosezione;
    
  $condSel="1=1";  

  if($sqlid_gruppo_par > 0) {
  	  $condSel = " anagrafica.id_gruppo_par = $sqlid_gruppo_par";
     }

  $sqlselect_socio .= " AND $condSel";
  
  $idAtPe=0;
  
  if($sqlid_attivita > 0)
      $idAtPe = $sqlid_attivita;
  
  if($sqlid_pellegrinaggio > 0)
      $idAtPe = $sqlid_pellegrinaggio;

  if($idAtPe > 0) {
  	  $sqlselect_socio .=" AND anagrafica.id IN(SELECT id_socio
  	                                                                     FROM  attivita_detail
  	                                                                     WHERE tipo = '" . $sqltipo . "'
  	                                                                     AND    id_attpell = $idAtPe)";
     }      
  $sqlselect_gruppo_par .= " ORDER BY 2";
  $sqlselect_socio .= " ORDER BY 2";

// Version 1.2, altri parametri di selezione

  $sqlselect_attivita = "SELECT COUNT(*) ctr,
                                                  SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                  SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                  attivita_m.dal dal_order,
                                                  attivita_m.id id_prn, 
                                                  CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa
                                      FROM   attivita_detail,
                                                  attivita_m,
                                                  attivita,
                                                  anagrafica
                                      WHERE attivita.id =attivita_m.id_attivita
                                      AND     attivita_detail.id_attpell = attivita_m.id
                                      AND     attivita_detail.id_socio = anagrafica.id
                                      AND     attivita_m.id_sottosezione = $sqlid_sottosezione
                                      AND     $condSel
                                      GROUP BY 2, 3, 4, 5, 6
                                      ORDER BY dal_order DESC, 6"; 

  $sqlselect_pellegrinaggio = "SELECT COUNT(*) ctr,
                                                 SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                  SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                  pellegrinaggi.dal dal_order,
                                                  pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa
                                      FROM   descrizione_pellegrinaggio,
                                                  pellegrinaggi,
                                                  attivita_detail,
                                                  anagrafica
                                      WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                      AND     attivita_detail.tipo ='V'
                                      AND     attivita_detail.id_socio = anagrafica.id
                                      AND     attivita_detail.id_attpell = pellegrinaggi.id
                                      AND     pellegrinaggi.id_sottosezione = $sqlid_sottosezione
                                      AND     $condSel
                                      GROUP BY 2, 3, 4, 5, 6
                                      ORDER BY dal_order DESC, 5"; 

// Fine Version 1.2, altri parametri di selezione

  if($debug) {
     echo "$fname: SQL (Gruppo) = $sqlselect_gruppo_par<br>";
     echo "$fname: SQL (Socio) = $sqlselect_socio<br>";
     echo "$fname: SQL (Attivit&agrave;) = $sqlselect_attivita<br>";
     echo "$fname: SQL (Pellegrinaggio) = $sqlselect_pellegrinaggio<br>";
    }

  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Invia E-mail soci</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_gruppo_par' value='" . $sqlid_gruppo_par . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";

  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' onChange='this.form.submit();'>" ;
      echo "<option value=0>--- Sottosezione ---</option>";
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
  echo "</form>";
 
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p class='search'>Gruppo</p></td>";
  echo "<td><select class='search' name='id_gruppo_par' onChange='this.form.submit();'>" ;
  echo "<option value=0>--- Tutti ---</option>";

  $result = $conn->query($sqlselect_gruppo_par);
  while($row = $result->fetch_assoc()) {
       	    echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_gruppo_par)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";
  

// Version 1.2 aggiunto attivita' e pellegrinaggio

// Attivita'
 
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='id_gruppo_par' value='" . $sqlid_gruppo_par . "'>";
  echo "<tr>";
  echo "<td><p class='search'>Attivit&agrave;</p></td>";
  echo "<td><select name='id_attivita' class='search' onChange='this.form.submit();'>" ;
  echo "<option value=0>--- Seleziona eventuale attivit&agrave; ---</option>";
  $rAtt = $conn->query($sqlselect_attivita);
  
  while($rA = $rAtt->fetch_assoc()) {
       	   echo "<option value=" . $rA["id_prn"];
       	   if($rA["id_prn"] == $sqlid_attivita)
       	       echo " selected";
       	   echo ">" . htmlentities($rA["desa"],$defCharsetFlags, $defCharset) . " (#" . $rA["ctr"] .") &minus;&gt; (". $rA["dal"] . " - " . $rA["al"] . ")</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";
  $rAtt->close();

// Pellegrinaggio
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='id_gruppo_par' value='" . $sqlid_gruppo_par . "'>";
  echo "<tr>";
  echo "<td><p class='search'>Pellegrinaggio</p></td>";
  echo "<td><select name='id_pellegrinaggio' class='search' onChange='this.form.submit();'>" ;
  echo "<option value=0>--- Seleziona eventuale Pellegrinaggio ---</option>";
  $rPel = $conn->query($sqlselect_pellegrinaggio);
  
  while($rP = $rPel->fetch_assoc()) {
             echo "<option value=" . $rP["id_prn"];
       	   if($rP["id_prn"] == $sqlid_pellegrinaggio)
       	       echo " selected";
       	   echo ">" . htmlentities($rP["desa"],$defCharsetFlags, $defCharset) . " (#" . $rP["ctr"] .") &minus;&gt; (". $rP["dal"] . " - " . $rP["al"] . ")</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";
  $rPel->close();

// End Version 1.2 aggiunto attivita' e pellegrinaggio

  echo "<tr>";
  echo "<td><p class='search'>Effettivo</p></td>";
  echo "<td><input name='effettivo' id='cb01' type='checkbox' value=1></td>";
  echo "</tr>";

// Categoria  
  echo "<tr>";
  echo "<td><p class='search'>Categoria</p></td>";
  echo "<td><select name='id_categoria' class='search'>";
  $rCat = $conn->query("SELECT id, descrizione FROM categoria ORDER BY 2");
  echo "<option value=0>--- Seleziona eventuale categoria ---</option>";
  
  while($rC = $rCat->fetch_assoc()) {
  	         echo "<option value=" . $rC["id"] . ">" . htmlentities($rC["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";

// Classificazione
  echo "<tr>";
  echo "<td><p class='search'>Classificazione</p></td>";
  echo "<td><select name='id_classificazione' class='search'>";
  $rCla = $conn->query("SELECT id, descrizione FROM classificazione ORDER BY 2");
  echo "<option value=0>--- Seleziona eventuale classificazione ---</option>";
  
  while($rC = $rCla->fetch_assoc()) {
  	         echo "<option value=" . $rC["id"] . ">" . htmlentities($rC["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";

  echo "<form action='" . $mail_target . "' method='POST' enctype='multipart/form-data'>";
  echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  echo "<input type='hidden' name='id_gruppo_par' value=$sqlid_gruppo_par>";
  echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
  
  if($sqlid_attivita > 0)
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attivita>";
  
  if($sqlid_pellegrinaggio > 0)
      echo "<input type='hidden' name='id_attpell' value=$sqlid_pellegrinaggio>";

  echo "<tr>";
  echo "<td><p class='search'>Socio</p></td>";
  echo "<td><select class='search' name='id_socio''>" ;
  echo "<option value=0>--- Tutti quelli con indirizzo e-mail valorizzato ---</option>";

  $result = $conn->query($sqlselect_socio);
   while($row = $result->fetch_assoc()) {
       	    echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_socio)  {
   	    	    echo " selected";
             } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "  &minus;&gt; " . $row["email"] . "</option>";
       	}
       	
  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Oggetto</p></td>";
  echo "<td><input class='required' name='sub' size=80 maxlength=100 required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Testo</p></td>";
  echo "<td><textarea class='required' name='corpo' maxlength='300' required/></textarea></td>";
  echo "</tr>";

  echo "<tr>";
  echo '<td colspan=2><input class="upload" type="file" name="upload" value="Allegato" id="file">';
  echo 'Dimensione massima 16MB&nbsp;</td>';
  echo "</tr>";
 
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Invia' onClick=\"{return conferma('Confermi invio e-mail?');}\"></p></form></td>";
  echo "</tr>";
  echo "</table>";
  echo "</form>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
