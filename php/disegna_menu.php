<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return; 

/****************************************************************************************************
*
*  Funzione che disegna il menù principale
*
*  @file disegna_menu.php
*  @abstract Menu' principale
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-13
*  @history first release
*  
*  @first 1.0
*  @since 2017-02-13
*  @CompatibleAppVer All
*  @where Monza
*
*
****************************************************************************************************/
function disegna_menu() {
require_once('../php/ritorna_db_usage.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

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

$sqlTotMenu = "SELECT COUNT(*) tot
                           FROM  voci_menu
                           WHERE id IN(SELECT id_menu
                                                FROM   voci_sottomenu,
                                                             abilitazione_livello 
                                                WHERE  id_livello = $livello_utente
                                                AND     abilitazione_livello.id_pagina = voci_sottomenu.id)
                           AND 1 < $userid
                           OR 1 = $userid";

$sqlMenu = "SELECT id, sequenza, label, pagina, target
                      FROM voci_menu
                      WHERE id IN(SELECT id_menu
                                           FROM  voci_sottomenu,
                                                       abilitazione_livello 
                                           WHERE  id_livello = $livello_utente
                                           AND     abilitazione_livello.id_pagina = voci_sottomenu.id)  
                           AND 1 < $userid
                      OR 1 = $userid ORDER BY 2";

if($userid > 1)
    $sqlSottoMenu = "SELECT label, pagina, target 
                                  FROM   voci_sottomenu,
                                              abilitazione_livello
                                  WHERE abilitazione_livello.id_livello = $livello_utente
                                  AND     abilitazione_livello.id_pagina = voci_sottomenu.id
                                  AND     id_menu = ? 
                                  ORDER BY sequenza, label";
else // Admin (visualizzo tutto e di piu)

   $sqlSottoMenu = "SELECT label, pagina, target 
                                  FROM   voci_sottomenu
                                  WHERE id_menu = ? 
                                  ORDER BY sequenza, label";
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

/*----------------------------------
		Inizio output html
----------------------------------*/
  echo "<LINK href='../css/unitalsi_menu.css' rel='stylesheet' type='text/css'>";

// Copyright
  echo "<div id='main-container' style='position: fixed; bottom: 5px;'>";
  echo "<table align='right' class='info'>";

  echo "<tr>";
  echo "<td  colspan='2' align='right' style='font-weight: normal; font-family: Arial; font-style: italic; font-size: 10px;'>";
  echo "<a href='mailto:luke.romano@gmail.com'>Luca Romano</a> - 2016-2018. Tutti i diritti riservati</td>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";

  echo "<div id='fixed1' class='fixed1'>";
  echo "<table class='menu' width='100%'>";
   
  echo "<tr>";
  echo "<td colspan='" . ($vociMenu+2) . "'>";

  if($livello_utente == 1)
      echo "<a href='../php/gestione_sezione.php'>" . $row["soc"] . ' - Sottosezione di ' . $rowsz["sot"] . "</a>";
   else
      echo $row["soc"] . ' - Sottosezione di ' . $rowsz["sot"];
   echo "</td>";

// Dati licenza     
   echo "<td class='user' rowspan=2>";
   echo "<table class='license' align='left' valign='top'>";
      
   if($dateExpire != '') { // Licenza temporanea
       echo "<tr>";
       echo "<th class='license' colspan='3'>La licenza scade il " . $dateExpire . "</th>";
       echo "</tr>";
      }
   echo "<tr>";
   echo "<th class='license' colspan='2'>Versione: " . ritorna_versione() . '</th>';
          
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
     	} // End switch
   echo '<th class="user">&nbsp;<img src="' . $img . '" width="70%" height="80%" 
              title="Utilizzo dello spazio disponibile (' . $p .') Usato (' . $parr['U'] . ') Libero (' . $parr['F'] .')"/></th>';
   echo "</tr>";
     
   echo "<tr>";
   echo "<th class='license'>" . $current_user . "</th>";
   echo "<form action='../php/gestione_password.php' method='post'>";
   echo "<input type='hidden' name='id' value='".  $userid . "'>";
     
   echo "<td class='user'><input class='login' type='submit' value='Cambia password'></form></td>";
   echo "<form action='../php/destroy_session.php' method='post' target='_parent'>";
   echo "<td class='user'><input class='login' type='submit' value='Logout' onClick=\"{return conferma('Confermi logout?');}\"></form></td>";
   echo "</tr>";

   echo "</table>"; // Fine tabella licenza           
   echo "</td>"; 

   // Logo UNITALSI   
   echo "<td rowspan=2><a href='../php/frame_set.php'><img src='/images/Logo_UNITALSI64x64.png'></a></td>";
   echo "</tr>";

  echo "<tr>"; 
     $result = $conn->query($sqlMenu);
     while($row = $result->fetch_assoc()) { // Ciclo per le voci del menù principale
                 echo "<td nowrap><ul class='menu'>";
                 echo "<li><a href='" . $row["pagina"] ."' target='" . $row["target"] . "'>" . htmlentities($row["label"], $defCharsetFlags, $defCharset);
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
                           //echo "<li><a href='" . $pagSm ."' target='" . $targSm . "'>" . htmlentities($labSm, $defCharsetFlags, $defCharset) . "</a></li>";
                           echo "<li><a href='" . $pagSm ."'>" . htmlentities($labSm, $defCharsetFlags, $defCharset) . "</a></li>";
       		             }
                 $stmt->close();
                 if($ix == 0)
                     echo "</a></li>";
                 else
                 	  echo "</ul></li>";
                 echo "</td>";                       		             
       		}
     echo '<td class="user" style="vertical-align: top;">&nbsp;<a href="../php/search.php"><img src="../images/magnifier.png" title="Cerca"/></a></td>';

     // Verifico se abillitato pacchetto IMAP
     $sql = "SELECT imap FROM mailparam
                  WHERE length(imap) > 10";  
     $result = $conn->query($sql);

     if($result->num_rows > 0 && function_exists('imap_open') && $livello_utente < 3)
          echo '<td class="user" style="vertical-align: top;">&nbsp;<a href="../php/q_invia_mail.php"><img src="../images/mail.png" title="Aggiorna allegati automatici"/></a></td>';
      else
          echo '<td>&nbsp;</td>';
     echo "</tr>";
     echo "</table>";
     echo "</div>";
/*----------------------------------
		Fine output html
----------------------------------*/
   $conn->close();
}
