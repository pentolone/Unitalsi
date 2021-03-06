<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Associazione socio/Mezzo</title>
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
 <script type="text/javascript">
  // Funzione per abilitare/disabilitare pulsante di inserimento se mezzo non selezionato
      function toggleButton(refA, refB, dataR, bttnID) {
   //   alert('Ecco');
      	     var inputFieldA =  document.getElementById(refA);
      	     var inputFieldB =  document.getElementById(refB);
      	     var inputFieldC =  document.getElementById(dataR);
           var btn = document.getElementById(bttnID);
           
         //  alert(inputFieldA.options[inputFieldA.selectedIndex].value);

      	     if(inputFieldA.options[inputFieldA.selectedIndex].value == 0 ||
      	        inputFieldB.options[inputFieldB.selectedIndex].value == 0 ||
      	        inputFieldC.value == '') { // Mezzo non selezionato
      	  //    alert('KO');
      	        btn.disabled=true;
              }
      	     else { // OK to proceed
      	     // alert('OK');
      	        btn.disabled=false;
              }
       }
  </script>  
</head>
<body>

<?php
/****************************************************************************************************
*
*  Associa i soci al mezzo di trasporto
*
*  @file gestione_soci_mezzo.php
*  @abstract Gestisce l'associazione dei soci al mezzo di trasporto
*  @author Luca Romano
*  @version 1.0
*  @time 2017-03-06
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-03-06
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/disegna_tabella_costi_socio.php');
require_once('../php/carica_array.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$date_format=ritorna_data_locale();

$index=0;
$update = false;
$debug=false;
$fname=basename(__FILE__);
$table_name="mezzi_detail";
$redirect="../php/gestione_soci_mezzo.php";
$print_target="../php/stampa_viaggio.php";
$titolo='Associazione socio/mezzo';
//$titoloSelect='--- Sconosciuto ---';

$sqlID=0;
$sqlExec='';
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_old=$sqlid_sottosezione;
$sqlanno=date('Y');
$sqlannostart=date('Y');
$sqlanno_min=0;
$sqlanno_selected=$sqlanno;
$sqlselect_soci_union='';
$desc_sottosezione='';
$sqlid_attpell=0;
$sqlid_socio=0;
$sqlid_mezzo=0;
$sqltipo='V';
$sqlcosti=array();
$showDoc=false;
$idDelete=array(0, 0);
$idMezzo=array(0, 0);
$sqlNposti=array(0, 0);

$msgAlert=null;

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselectanno_attivita = "SELECT MIN(anno) amin
                                            FROM ";                                                          
// SQL attivita'
$sqlselect_attivita = "SELECT SUBSTRING(DATE_FORMAT(attivita_m.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(attivita_m.al,'" . $date_format ."'),1,10) al, 
                                                attivita_m.dal dal_order,
                                                attivita_m.id id_prn, attivita.descrizione desa
                                    FROM  attivita,
                                                attivita_m
                                    WHERE attivita_m.id_attivita = attivita.id";

// SQL pellegrinaggi
$sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa
                                    FROM   descrizione_pellegrinaggio,
                                                pellegrinaggi
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                    AND     pellegrinaggi.id IN(SELECT id_attpell FROM attivita_detail
                                                                              WHERE tipo = 'V')";

$sqlselect_soci = "SELECT 0, id,  CONCAT(cognome,' ',nome) nome, data_exp
                              FROM   anagrafica
                              WHERE id_sottosezione = " . $sott_app;
                              
if($multisottosezione)  {
      $sqlselect_soci_union = " UNION
                                            SELECT 1, anagrafica.id,
                                            CONCAT(anagrafica.cognome,' ',anagrafica.nome,' (Sottosezione di ' , sottosezione.nome,')') descrizione, data_exp
                                            FROM    anagrafica,
                                                         sottosezione
                                             WHERE  anagrafica.id_sottosezione = sottosezione.id
                                             AND      id_sottosezione != " . $sott_app; 
}

// Seleziona mezzi di trasporto
$sqlselect_mezzi = "SELECT mezzi_disponibili.id id, descrizione, capienza
                                 FROM    mezzi_disponibili";

$sqlselect_mezzi1 = "SELECT MAX(mezzi_disponibili.id) id, descrizione, capienza, IFNULL(SUM(n_posti), 0) n_posti
                                 FROM    mezzi_disponibili
                                 LEFT JOIN mezzi_detail ON
                                 mezzi_detail.id_mezzo = mezzi_disponibili.id";

// Seleziona se il socio e' gia' associato
$sqlselect_mezzi_detail = "SELECT id
                                            FROM    mezzi_detail";
/*------------------------------------------------------------

       Tabella elenco documenti di identita'
       
------------------------------------------------------------*/
$sqlselect_tipo_doc = "SELECT id, descrizione
                                      FROM   tipo_documento
                                      ORDER BY 2";

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

$titoloSelect ='--- Seleziona il viaggio/pellegrinaggio ---';
$table_name = 'pellegrinaggi'; 
$sqlExec = $sqlselect_pellegrinaggio;
$sqlselectanno_attivita .= $table_name;

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

      		                      case "anno": // anno
      					                    $sqlanno = $value;
      					                    break;

      		                      case "id_attpell": // attivita/pellegrinaggio
      					                    $sqlid_attpell = $value;
      					                    break;

      		                      case "id_mezzo": // Mezzo
      					                    $sqlid_mezzo = $value;
      					                    break;

      		                      case "msg": // Messaggio
      					                    $msgAlert = $value;
      					                    break;
                    }
                  }
     }
     
  $sqlselect_mezzi .= " WHERE id_sottosezione = $sqlid_sottosezione
                                    ORDER BY 2";

  $sqlselectanno_attivita .= " WHERE anno > 0 AND id_sottosezione = " . $sqlid_sottosezione;     
  $sqlselect_mezzi_detail .= " WHERE id_socio = " . $sqlid_socio .
                                              " AND     id_attpell = " . $sqlid_attpell .
                                              " AND     id_mezzo =  " . $sqlid_mezzo .
                                              " AND     id_data_viaggio IN(SELECT dal, al FROM
                                                                                          pellegrinaggi
                                                                                          WHERE id = " . $sqlid_attpell .
                                               " GROUP BY 2,3";

  if($debug) {
      echo "$fname SQL (anno) = $sqlselectanno_attivita<br>";
      echo "$fname SQL (mezzi disponibili) = $sqlselect_mezzi<br>";
//      echo "$fname SQL = $sqlselect_mezzi_detail<br>";
   }

  $result = $conn->query($sqlselectanno_attivita);
  $row = $result->fetch_assoc();
  $sqlanno_min=$row["amin"];

  if($sqlid_sottosezione != $sqlid_old) {
  	  $sqlid_old = $sqlid_sottosezione;
  	  $sqlannostart=date('Y');
    }

  $sqlExec .= " AND " . $table_name . ".anno = " . $sqlanno;
  
  if(!$sqlanno_min)
       $sqlanno_min = $sqlannostart;
    
  if($sqlid_sottosezione > 0)  
     $sqlExec .= " AND " . $table_name . ".id_sottosezione = " . $sqlid_sottosezione;
    
  $sqlExec .= "  ORDER BY 3 DESC, 4";

  if($debug)
      echo "$fname SQL EXEC = $sqlExec<br>";
     
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     return;
     }
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>" . $titolo . "</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<input type='hidden' name='id_old' value='" . $sqlid_old . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
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
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Anno di riferimento</p></td>";
  echo "<td><select class='required' name='anno' required onChange='this.form.submit();'>" ;
  $ctr=$sqlannostart;
  while($ctr >= $sqlanno_min) {
  	         echo "<option value=" . $ctr;
  	         if($ctr == $sqlanno)
  	             echo " selected";
  	          echo ">" . $ctr . "</option>";
  	         $ctr--;
             } 	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  echo "<form action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
  echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Seleziona viaggio/Pellegrinaggio</p></td>";
  echo "<td><select class='required' name='id_attpell' onChange='this.form.submit();'>" ;
  echo "<option value=0>" . $titoloSelect . "</option>";
  $result = $conn->query($sqlExec);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id_prn"];
       	   if($row["id_prn"] == $sqlid_attpell)
       	       echo " selected";
       	   echo ">";
       	   echo htmlentities($row["desa"],$defCharsetFlags, $defCharset) . " -&gt; (". $row["dal"] . " - " . $row["al"] . ")</option>";
       	}
  echo '</select></td>'; 
  echo "</tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  if($sqlid_attpell > 0) { // OK, attivita' selezionata, carico i dati dei mezzi disponibili
      $sqlselect_soci .= " AND anagrafica.id IN(SELECT attivita_detail.id_socio
                                                                        FROM   pellegrinaggi,
                                                                                    attivita_detail
                                                                        WHERE attivita_detail.id_attpell = pellegrinaggi.id
                                                                        AND     attivita_detail.tipo = 'V'
                                                                        AND     pellegrinaggi.id = " . $sqlid_attpell . ")";                                                                       

   	   $sqlselect_soci .= $sqlselect_soci_union . " AND anagrafica.id IN(SELECT id_socio
                                                                        FROM   pellegrinaggi,
                                                                                    attivita_detail
                                                                        WHERE attivita_detail.id_attpell = pellegrinaggi.id
                                                                        AND     attivita_detail.tipo = 'V'
                                                                        AND     pellegrinaggi.id = " . $sqlid_attpell . ")                                                                      
                                                                        ORDER BY 1,3";
      if($debug)
          echo "$fname SQL SOCI $sqlselect_soci<br>"; 

      echo "<form action='" . $redirect . "' method='POST'>";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
//      echo "<input type='hidden' name='id_mezzo' value=" . $sqlid_mezzo . ">";
      
      if($msgAlert) {
      	   echo "<tr><td colspan='2'><p class='alert'>" . htmlentities($msgAlert, $defCharsetFlags, $defCharset) . "</p></td></tr>";
         }

      echo "<tr>";
      echo "<td><p class='required'>Seleziona mezzo</p></td>";
      echo "<td><select class='required' name='id_mezzo' onChange='this.form.submit();'>";
      $result = $conn->query($sqlselect_mezzi);
      echo "<option value=0>--- Seleziona mezzo ---</option>";
      while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                if($row["id"] == $sqlid_mezzo)
                   echo " selected";
                   
                echo ">";
                echo htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . " &minus;&gt; " . $row["capienza"];
                echo "</option>";
              } 	
       
      // End select
      echo "</select>";
      echo "</td></tr>";
      echo "</form>";
    	}

  if($sqlid_mezzo > 0) { // OK, mezzo selezionato, carico i dati dei soci che richiedono viaggio
     $sql = "SELECT dal, al
                  FROM   pellegrinaggi WHERE id = " . $sqlid_attpell;
                  
      if($debug)
         echo "$fname SQL = $sql<br>";

     $result = $conn->query($sql);
     $row = $result->fetch_assoc();
     $viaggioStart = $row["dal"];
     $viaggioEnd = $row["al"];

// Soci con richiesta di viaggio NON ancora a bordo
     $sql = "SELECT anagrafica.id, CONCAT(cognome,' ', nome) nome,
                               id_tipo_doc,
                               n_doc,
                               data_ril,
                               data_exp,
                               attivita_detail.tipo_viaggio,
                               SUBSTRING(DATE_FORMAT(data_nascita,'" . $date_format ."'),1,10) dnas,
                               IFNULL(disabilita.descrizione, ' ') desd
                   FROM   attivita_detail,
                               anagrafica
                   LEFT JOIN disabilita ON
                   anagrafica.id_disabilita = disabilita.id
                   WHERE anagrafica.id = attivita_detail.id_socio
                   AND     attivita_detail.id_attpell = $sqlid_attpell
                   AND     attivita_detail.tipo_viaggio > 0
                   AND     anagrafica.id NOT IN(SELECT id_socio
                                                                 FROM mezzi_detail
                                                                 WHERE id_attpell = $sqlid_attpell)
                   ORDER BY 2";
      
      if($debug)
         echo "$fname SQL = $sql<br>";
         
      echo "<form id='addViaggio' method='POST' action='../php/insert_mezzi_detail.php'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' name='id_mezzo' value=$sqlid_mezzo>";
      echo "<input type='hidden' name='data_viaggioA' value='" . $viaggioStart . "'>";
      echo "<input type='hidden' name='data_viaggioR' value='" . $viaggioEnd . "'>";

      echo "<tr>";      
      echo "<td rowspan=2 style='vertical-align: top;'><p class='search'>Elenco</p>";
      echo "<select style='min-width: 200px;' class='search' name='id_socio_viaggio' size=22>";
      $result = $conn->query($sql);     
      while($row = $result->fetch_assoc()) { // Carico i dati dei soci NON a bordo (se abilitato)
                if($authMask["insert"])
                    echo "<option value='" . $row["id"] . ";" . $row["tipo_viaggio"] . "' onClick=\"document.getElementById('addViaggio').submit();\">";
                else
                    echo "<option value='" . $row["id"] . ";" . $row["tipo_viaggio"] .">";
                echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset);
                
                switch($row["tipo_viaggio"]) {
                	         case TO: // Andata
                	                       echo " (A)";
                	                       break;

                	         case FROM: // Ritorno
                	                       echo " (R)";
                	                       break;

                	         case ROUNDTRIP: // Andata e ritorno
                	                       echo " (A/R)";
                	                       break;
                           }
                echo "</option>";
               }
               
      echo "</select></td>";
      echo "</form>";
      
      // Elenco soci sul mezzo nel viaggio di andata
      $sql = "SELECT anagrafica.id, CONCAT(cognome,' ', nome) nome
                   FROM   anagrafica,
                                mezzi_detail
                   WHERE  mezzi_detail.id_attpell = $sqlid_attpell
                   AND      mezzi_detail.id_socio = anagrafica.id
                   AND      mezzi_detail.data_viaggio = '" . $viaggioStart . "'
                   AND      mezzi_detail.id_mezzo = $sqlid_mezzo
                   ORDER BY 2";

      if($debug)
         echo "$fname   SQL = $sql";  
         
      echo "<form id='removeViaggioA' method='POST' action='../php/insert_mezzi_detail.php'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' name='id_mezzo' value=$sqlid_mezzo>";
      echo "<input type='hidden' name='data_viaggioA' value='" . $viaggioStart . "'>";
      echo "<input type='hidden' name='data_viaggioR' value='" . $viaggioEnd . "'>";
      echo "<td><p class='search'>Andata</p>";
      echo "<select style='width: 200px;' class='search' name='id_socio_viaggio' size=10>";

      $result = $conn->query($sql);     
      while($row = $result->fetch_assoc()) { // Carico i dati dei soci  viaggio di andata
                echo "<option value='" . $row["id"] . ";0' onClick=\"document.getElementById('removeViaggioA').submit();\">";
                echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset);
                echo  "</option>";
               }
               
      echo "</select></td>";
      echo "</tr>";
      echo "</form>";
                     
      // Elenco soci sul mezzo nel viaggio di ritorno
      $sql = "SELECT anagrafica.id, CONCAT(cognome,' ', nome) nome
                   FROM   anagrafica,
                                mezzi_detail
                   WHERE  mezzi_detail.id_attpell = $sqlid_attpell
                   AND      mezzi_detail.id_socio = anagrafica.id
                   AND      mezzi_detail.data_viaggio = '" . $viaggioEnd . "'
                   AND      mezzi_detail.id_mezzo = $sqlid_mezzo
                   ORDER BY 2";

      if($debug)
         echo "$fname   SQL = $sql"; 
      
         
      echo "<form id='removeViaggioR' method='POST' action='../php/insert_mezzi_detail.php'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_attpell>";
      echo "<input type='hidden' name='id_mezzo' value=$sqlid_mezzo>";
      echo "<input type='hidden' name='data_viaggioA' value='" . $viaggioStart . "'>";
      echo "<input type='hidden' name='data_viaggioR' value='" . $viaggioEnd . "'>";
      echo "<tr>";
      echo "<td><p class='search'>Ritorno</p>";
      echo "<select style='width: 200px;' class='search' name='id_socio_viaggio' size=10>";

      $result = $conn->query($sql);     
      while($row = $result->fetch_assoc()) { // Carico i dati dei soci  viaggio di ritorno
                echo "<option value='" . $row["id"] . ";0' onClick=\"document.getElementById('removeViaggioR').submit();\">";
                echo  htmlentities($row["nome"], $defCharsetFlags, $defCharset);
                echo  "</option>";
               }
               
      echo "</select></td>";
      echo "</tr>";
     }
  echo "</table>";

  echo "</div>";

$conn->close();

?>
</body>
</html>
