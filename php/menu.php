<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <LINK href="../css/unitalsi_menu.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <!-- <meta http-equiv="refresh" content="30"> -->
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  
</head>
<body>

<?php
/****************************************************************************************************
*
* REMEMBER REFRESH!
* <meta http-equiv="refresh" content="30">
*
*  Menu' principale
*
*  @file menu.php
*  @abstract Menu' principale
*  @author Luca Romano
*  @version 1.0
*  @time 2016-04-22
*  @history first release
*  
*  @first 1.0
*  @since 2016-04-21
*  @CompatibleAppVer All
*  @where Las Palmas de Gran Canaria
*
*
****************************************************************************************************/
require_once('../php/ritorna_db_usage.php');
$debug=false;
$index=0;
$insertStm = " ";
$userid=0;
$vociMenu=0;
$dateExpire='';
$livello_utente=10;
$sottosezione=0;

$dateExpire = ritorna_scadenza_key();
$userid = $_SESSION["userid"];
$livello_utente = $_SESSION["livello_utente"];

$conn = DB_connect();
$tablename='voci_menu';
$current_user=ritorna_utente();

$sqlTotMenu = "SELECT COUNT(*) tot FROM voci_menu
                           WHERE id IN(SELECT id_menu
                                                FROM   voci_sottomenu WHERE livello >= " . $livello_utente . ")";

$sqlMenu = "SELECT id, sequenza, label, pagina, target FROM voci_menu
                      WHERE  id IN(SELECT id_menu
                                           FROM   voci_sottomenu WHERE livello >= " . $livello_utente . ")
                      ORDER BY 1,2";

$sqlSottoMenu = "SELECT label, pagina, target FROM voci_sottomenu 
                             WHERE livello >= " . $livello_utente ." AND id_menu = ? ORDER BY sequenza, label";
$sqlImap = '';
  
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $result = $conn->query($sqlTotMenu);
  $row = $result->fetch_assoc();
  $vociMenu = $row["tot"];
  
  $sql = "SELECT sezione.id id, sezione.nome soc, sezione.sede, 
                            sezione.indirizzo, sezione.cap, sezione.citta, province.nome, sigla, sezione.sito_web, sezione.cf_piva
               FROM   sezione, sottosezione, province
               WHERE province.id = sezione.id_provincia
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id IN
                           (SELECT id_sottosezione
                            FROM   utenti
                            WHERE id_sottosezione = " . $_SESSION["sottosezione_appartenenza"] .")";

  $result = $conn->query($sql);
  $row = $result->fetch_assoc();

  if($result->num_rows == 0) {  // Prima installazione, richiama inserimento dati societari
      echo '<script type="text/javascript">alert("Dati societari assenti! Prima installazione, premere OK per inserire i dati.");'; 
      echo 'window.open("../php/gestione_sezione.php", "_parent");</script>';
  }
  $vociMenu++;
  $_SESSION["societa_id"] = $row["id"]; // Aggiungo ID societa' in sessione
  
  $sqlsottosezione = "SELECT sottosezione.id id, sottosezione.nome sot, sede, 
                                                 indirizzo, cap, citta, province.nome, sigla, sito_web, cf_piva,
                                                 sottosezione.telefono, sottosezione.fax
                                    FROM   sottosezione,province
                                    WHERE  province.id = sottosezione.id_provincia AND
                                                 sottosezione.id_sezione = " . $_SESSION["societa_id"] . 
                                   " AND sottosezione.id = " . $_SESSION["sottosezione_appartenenza"];
  $result = $conn->query($sqlsottosezione);
  $rowsz = $result->fetch_assoc();
?>
<table class="menu" width="100%">
<tr>
<td class="societa">
     <table>
     <tr>
     <td colspan='<?php echo $vociMenu  ?>'><?php echo "<a href='../php/gestione_sezione.php' target='nav'>" . $row["soc"] . ' - Sottosezione di ' . $rowsz["sot"];?></a></td>
     <td colspan='<?php echo ($vociMenu-($vociMenu-2))  ?>'>Tel/Fax<?php echo " - " .  $rowsz["telefono"] . '/' .  $rowsz["fax"]?></td>     
     </tr>

      <tr>
     <td colspan='<?php echo ($vociMenu-2)  ?>'><?php echo $rowsz["indirizzo"];?></td>
     <td colspan='<?php echo ($vociMenu-($vociMenu-2)) ?>'><?php echo $rowsz["cap"]; echo " - " . $rowsz["citta"] .   " (" . $rowsz["sigla"] . ")";?></td>
     </tr>
     <tr><td><ul>
     <?php
       $result = $conn->query($sqlMenu);
       while($row = $result->fetch_assoc()) { // Ciclo per le voci del menù principale
                 //echo "<div>";
                 echo "<li><a href='" . $row["pagina"] ."' target='" . $row["target"] . "'>" . $row["label"];
                 $ix=0;
                 $stmt = $conn->prepare($sqlSottoMenu);
                 $stmt->bind_param('i', $row["id"]);
                 $stmt->execute();
                 $stmt->store_result();
                 $stmt->bind_result($labSm, $pagSm, $targSm);
                 while($stmt->fetch()) { // Ciclo per le voci del sottomenù (se presente)
                           if($ix == 0) {
                           	  echo " &#9662;</a>";
                              echo "<ul class='dropdown'>";
       		              }
       		              $ix++;
                           echo "<li><a href='" . $pagSm ."' target='" . $targSm . "'>" . $labSm . "</a></li>";
       		             }
                 $stmt->close();
                 if($ix == 0)
                     echo "</a></li>";
                 else
                 	  echo "</ul></li>";
                
       		             
       		}
      echo "</ul></div></td>";
      echo '<td class="user" colspan="2">&nbsp;<a href="../php/search.php" target="nav"><img src="../images/magnifier.png" title="Cerca"/></a></td>';

      // Verifico se abillitato pacchetto IMAP
      $sql = "SELECT imap FROM mailparam
                   WHERE length(imap) > 10";  
      $result = $conn->query($sql);

      if($result->num_rows > 0 && function_exists('imap_open'))
          echo '<td class="user">&nbsp;<a href="../php/ricevi_mail.php?user_request=1" target="nav"><img src="../images/mail.png" title="Aggiorna allegati automatici"/></a></td>';
      else
          echo '<td>&nbsp;</td>';
     ?>
    </tr>
     </table>
    </td>
    <td class='user'>
    <table class='call' align='left' valign='top'>
          <?php
            if($dateExpire != '') { // Licenza temporanea
                echo "<tr>";
                echo "<th class='call' colspan='3'>La licenza scade il " . $dateExpire . "</th>";
               }
           ?>
          </tr><tr>
          <th class='call' colspan='2'>Versione: <?php echo ritorna_versione(); echo '</th>';
          
      // Visualizzo utilizzo DB
     $parr = ritorna_db_usage();
     $p = $parr['%'];
     switch($p) {
     	          case $p >= 98:
     	                         $img = '../images/db_full.png';
     	                         break;

     	          case $p > 85:
     	                         $img = '../images/db_almost_full.png';
     	                         break;

     	          case $p >= 61 && $p <= 85:
     	                         $img = '../images/db_75.png';
     	                         break;

     	          case $p >= 41 && $p < 61:
     	                         $img = '../images/db_50.png';
     	                         break;

     	          case  $p >= 20 && $p < 41:
     	                         $img = '../images/db_25.png';
     	                         break;

     	          case $p >= 5:
     	                         $img = '../images/db_almost_empty.png';
     	                         break;

     	          default:
     	                         $img = '../images/db_empty.png';
     	                         break;
     	}
       echo '<th class="user">&nbsp;<img src="' . $img . '" width="70%" height="80%" 
                title="Utilizzo dello spazio disponibile (' . $p .') Usato (' . $parr['U'] . ') Libero (' . $parr['F'] .')"/></th>';
?>
           </tr>
          <tr>
          <td class='call'><?php echo $current_user?></td>
          <form action='../php/gestione_password.php' method='post' target='nav'>
          <input type='hidden' name='id' value='<?php echo $userid?>'>
          <td class='user'><input class='login' type='submit' value='Cambia password'></form></td>
          <form action='../php/destroy_session.php' method='post' target='_parent'>
          <td class='user'><input class='login' type='submit' value='Logout' onClick="{return conferma('Confermi logout?');}"></form></td>
           </tr>
           
           <tr>
          </tr>
           
     </table></td>
     
<?php  $conn->close(); ?>
</tr>
</table>

