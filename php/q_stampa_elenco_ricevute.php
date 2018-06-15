<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Stampa elenco ricevute</title>
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
*  Query per stampare l'elenco delle ricevute presenti nel DB
*
*  @file q_stampa_elenco_ricevute.php
*  @abstract Query per stampare l'elenco delle ricevute emesse
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-03
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-03
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/ricerca_socio.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$date_format=ritorna_data_locale();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="ricevute";
$redirect="../php/q_stampa_elenco_ricevute.php";
$print_target="../php/stampa_elenco_ricevute.php";

$sqlID_socio=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_pellegrinaggio=0;
$sqlid_attivita=0;
$socioDisplay=null;
$searchS=false;
$sqldal=date('Y-01-01');
$sqlal=date('Y-m-d'); // Valorizzo al = alla data corrente

$sqlanno=date('Y');
$sqlannostart=date('Y');
$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE " . $table_name - ".id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

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

  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
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
  if ($_POST) { // se post allora ho modificato i valori di selezione

      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
           
                    if($debug) {
                       echo $fname . ": KEY = " . $key . '<br>';
                       echo $fname . ": VALUE = " . $value . '<br>';
                       echo $fname . ": INDEX = " . $index . '<br><br>';                    	
                     }

                    switch($key) {
      		                      case "id_sottosezione": // sottosezione
      					                     $sqlid_sottosezione = $value;
      					                     break;

      		                      case "anno": // anno di competenza
      					                     $sqlanno = $value;
      					                     
      					                     if($sqlanno < date('Y')) { // Valorizzo dal/al
      					                        $sqldal = $sqlanno . "-01-01";
      					                        $sqlal = $sqlanno . "-12-31";
      					                        }
      					                     break;

      		                      case "id_pell": // pellegrinaggio valorizzato
      					                     $sqlid_pellegrinaggio = $value;
      					                     $sqlid_attivita = 0;
      					                     break;

      		                      case "id_att": // attivita' valorizzata
      					                     $sqlid_attivita = $value;
      					                     $sqlid_pellegrinaggio = 0;
      					                     break;

      		                      case "ricercaSocio": // Ricerca socio
      					                      $searchS=true;
      					                      break;

      		                      case "id-s": // ID socio
      					                     $sqlID_socio = $value;
      					                     $sqlid_pellegrinaggio = 0;
      					                     $sqlid_attivita = 0;
      					                     
      					                     $sql = "SELECT CONCAT(cognome, ' ', nome) nome
      					                                  FROM   anagrafica
      					                                  WHERE id = $sqlID_socio";
      					                                  
      					                      $rA = $conn->query($sql);
      					                      $rAA = $rA->fetch_assoc();
      					                      
      					                      $socioDisplay = $rAA["nome"];
      					                      $rA->close();
                              } // end switch
                  } // end foreach
            } // end if $POST

  if($searchS) { // Ricerca dati socio
      echo "<form id='searchTxt' action='" . $redirect . "' method='post'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
      echo "<input type='hidden' name='anno' value=$sqlanno>";
      risultati_ricercaS($conn, 'searchTxt', $value);
      echo "</form>";
   }
            
// Selezione del pellegrinaggio
$sqlselect_pellegrinaggio = "SELECT COUNT(*) ctr, SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
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
                                    AND     (pellegrinaggi.anno = $sqlanno OR
                                                 pellegrinaggi.anno = ($sqlanno - 1))
                                    GROUP BY 2,3,4,5 ORDER BY 4 DESC, 5";
  if($debug) {
  	   echo "$fname: SQL pellegrinaggio $sqlselect_pellegrinaggio<br>";
    }

// Selezione dell'attivita'
$sqlselect_attivita = "SELECT COUNT(DISTINCT attivita_detail.id_socio) ctr,
                                                SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                attivita_m.dal dal_order,
                                                attivita_m.id id_prn,
                                                CONCAT(attivita.descrizione, ' ' ,IFNULL(CONCAT(' (' , attivita_m.descrizione,')'),'')) desa
                                    FROM  attivita_detail,
                                                attivita_m,
                                                attivita
                                    WHERE attivita.id =attivita_m.id_attivita
                                    AND     attivita_detail.id_attpell = attivita_m.id
                                    AND     (attivita_m.anno = $sqlanno OR
                                                 attivita_m.anno = ($sqlanno - 1))
                                    AND     attivita_m.id_sottosezione = $sqlid_sottosezione
                                    GROUP BY 2,3,4,5 ORDER BY 4 DESC, 5";
  if($debug) {
  	   echo "$fname: SQL attivita $sqlselect_attivita<br>";
    }

// Seleziono anno minimo ricevuta

  $sql = "SELECT MIN(YEAR(data_ricevuta)) amin
                           FROM   ricevute 
               WHERE id_sottosezione = " . $sqlid_sottosezione;                                            
  $r = $conn->query($sql);
  $rs = $r->fetch_assoc();
  
  $sqlannostart = $rs["amin"];
  
  if(!$sqlannostart)
      $sqlannostart = date('Y');
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Stampa elenco ricevute</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</td></p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' onChange='this.form.submit();'>" ;
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
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";

  echo "<tr>";
  echo "<td><p class='required'>Anno di emissione ricevuta</p></td>";
  echo "<td><select class='required' name='anno' required onChange='this.form.submit();'>" ;
  $ctr=date('Y');
  while($ctr >= $sqlannostart) {
  	         echo "<option value=" . $ctr;
  	         if($ctr == $sqlanno)
  	             echo " selected";
  	          echo ">" . $ctr . "</option>";
  	         $ctr--;
             } 	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  // Seleziono pellegrinaggio (opzionale)
  
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  echo "<input type='hidden' name='anno' value=$sqlanno>";

// Viaggio/Pellegrinaggio
  echo "<tr>";
  echo "<td><p>Viaggio/Pellegrinaggio</p></td>";
  echo "<td><select class='search' name='id_pell' onChange='this.form.submit();'>" ;
  echo "<option value=0>--- Seleziona eventuale viaggio/pellegrinaggio ---</option>";
  
  $rP = $conn->query($sqlselect_pellegrinaggio);
  while($rrP = $rP->fetch_assoc()) {
  	         echo "<option value=" . $rrP["id_prn"];
  	         
  	         if($rrP["id_prn"] == $sqlid_pellegrinaggio) {
  	         	   echo " selected";
  	            }
       	   echo ">" . htmlentities($rrP["desa"],$defCharsetFlags, $defCharset) . " &minus;&gt; " .  $rrP["dal"] . "</option>";
  	        }
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";
  
  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  echo "<input type='hidden' name='anno' value=$sqlanno>";

// Attivita'
  echo "<tr>";
  echo "<td><p>Attivit&agrave;</p></td>";
  echo "<td><select class='search' name='id_att' onChange='this.form.submit();'>" ;
  echo "<option value=0>--- Seleziona eventuale attivit&agrave; ---</option>";
  
  $rA = $conn->query($sqlselect_attivita);
  while($rrA = $rA->fetch_assoc()) {
  	         echo "<option value=" . $rrA["id_prn"];
  	         
  	         if($rrA["id_prn"] == $sqlid_attivita) {
  	         	   echo " selected";
  	            }
       	   echo ">" . htmlentities($rrA["desa"],$defCharsetFlags, $defCharset) . " &minus;&gt; " .  $rrA["dal"] . "</option>";
  	        }
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

// Ricerca del socio
  echo "<form id='searchTxt' name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  echo "<input type='hidden' name='anno' value=$sqlanno>";
  echo "<tr>";
  echo "<td><p>Socio</p></td>";
  echo "<td><p>";
  ricerca_socio('searchTxt', $socioDisplay);
  echo "</p></td>";
  echo "</tr>";
  echo "</form>";

  // POST per dati di stampa
  echo "<form action='" . $print_target . "' target='_blank' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  echo "<input type='hidden' name='id_pell' value=$sqlid_pellegrinaggio>";
  echo "<input type='hidden' name='id_att' value=$sqlid_attivita>";
  echo "<input type='hidden' name='id_socio' value=$sqlID_socio>";

  echo "<tr>";
  echo "<td><p class='required'>Dal</p></td>";
  echo "<td><input class='required' name='dal' type='date' value='" .$sqldal ."' 
           min='" . $sqldal ."' max='" . $sqlal . "' required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Al</p></td>";
  echo "<td><input class='required' name='al' type='date' value='" .$sqlal ."'
           min='" . $sqldal ."' required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><input type='checkbox' name='effettivo' value=1>Effettivo</td>";
  echo "<td><select name='id_categoria' class='search'>";
  $rCat = $conn->query("SELECT id, descrizione FROM categoria ORDER BY 2");
  echo "<option value=0>--- Seleziona eventuale categoria ---</option>";
  
  while($rC = $rCat->fetch_assoc()) {
  	         echo "<option value=" . $rC["id"] . ">" . htmlentities($rC["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><input type='checkbox' name='tesseramento' value=1>Tesseramento</td>";
  echo "<td><select name='id_pagamento[]' multiple size=10 class='search'>";
  $rPag = $conn->query("SELECT id, descrizione FROM tipo_pagamento ORDER BY 2");
  echo "<option value=0 disabled>--- Seleziona eventuale tipo di pagamento (CTRL o CMD per selezioni multiple) ---</option>";
  while($rP = $rPag->fetch_assoc()) {
  	         echo "<option value=" . $rP["id"] . ">" . htmlentities($rP["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
  	      }
  echo "</select></td>";
  echo "</tr>";

// Ordinamento delle ricevute  
  echo "<tr>";
  echo "<td><p>Ordinamento</p></td>";
  echo "<td><p><input type='radio'  name='order' value='ASC' checked>Crescente<br>
                    <input type='radio'  name='order' value='DESC'>Decrescente</p></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td><p>Formato stampa</td>";
  echo "<td><input type='radio'  name='prn_format' value='P' checked>A4<br>
                    <input type='radio'  name='prn_format' value='L'>Landscape</p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
  
  if($authMask["query"]) {  
      echo "<tr>";

      echo  "<td class='button'><p><input type='image' src='../images/print.png'  wiidth=32 height=32 value=0></td>";
      echo  "<td class='button'><input type='image' src='../images/csv.png' name='CSV' type='submit' value=1></p></td>";

      echo "</tr>";
     }
  echo "</table>";
  echo "</form>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
