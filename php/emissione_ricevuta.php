<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Emissione ricevuta</title>
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
*  Emette la ricevuta alla persona
*
*  @file emissione_ricevuta.php
*  @abstract Emette la ricevuta
*  @author Luca Romano
*  @version 1.1
*  @time 2018-01-02
*  @history Aggiunto anno di competenza
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-19
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/ritorna_numero_ricevuta.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$update = false;
$search=false;
$debug=false;
$fname=basename(__FILE__);
$date_format=ritorna_data_locale();
$table_name="ricevute";
$redirect="../php/emissione_ricevuta.php";
$print_target="../php/inserisci_e_stampa.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlid_anagrafica=0;
$sqlid_tipopag=1;
$sqltipoArray=array();
$sqltipo='-';
$sqltipo_lista='-';
$sqlid_attpell=0;
$sqlid_attpell_lista=0;
$sqlanno = date('Y');

$sqldes_attpell='';
$sqldt_dal='';
$sqlcausale='';
$sqlimporto=0;
$sqln_ricevuta=0;
$dummy='';

$desc_sottosezione='';

$sqlselect_anagrafica  = "SELECT id, CONCAT(cognome, ' ',nome) nome
                                          FROM   anagrafica
                                          WHERE 1";

if(!$multisottosezione) {
   $sqlselect_anagrafica .= " AND id_sottosezione = " . $sott_app; 
 }

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

// Tipo pagamento
$sqlselect_tipopag = "SELECT id, descrizione
                                    FROM   tipo_pagamento
                                    ORDER BY 2";


$sqlselect_viaggi_attivita = "SELECT 'V', pellegrinaggi.id, descrizione_pellegrinaggio.descrizione,
                                                            SUBSTR(DATE_FORMAT(dal, '" . $date_format . "'),1,10) dt_dal,
                                                            dal dt_ord
                                               FROM    pellegrinaggi,
                                                            descrizione_pellegrinaggio
                                               WHERE  descrizione_pellegrinaggio.id =id_attpell
                                               AND      pellegrinaggi.id_sottosezione = ?
                                               AND      pellegrinaggi.anno = ?
                                               UNION
                                               SELECT 'A', attivita_m.id, attivita.descrizione,
                                                            SUBSTR(DATE_FORMAT(attivita_m.dal, '" . $date_format . "'),1,10) dt_dal,
                                                            dal dt_ord
                                               FROM    attivita_m,
                                                            attivita
                                               WHERE  attivita_m.id_attivita = attivita.id
                                               AND      attivita_m.id_sottosezione = ?
                                               AND      attivita_m.anno = ?
                                               ORDER BY 3, 5";

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

  if ($_POST) { // se post allora ho modificato i valori di selezione oppure confermo emissione

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

   		                       case "anno": // Anno di competenza
      					                    $sqlanno = $value;
      					                    break;

   		                       case "id_attpell": // Attivita'/Viaggio
      					                    $sqlid_attpell = $value;
      					                    break;

   		                       case "tipo": // Tipo
      					                    $sqltipo = $value;
      					                    break;
                    }
                  }
                  $index++;

    }

 // if($sqlid_sottosezione > 0)  
    // $sqlselect_anagrafica .= " AND id_sottosezione =  $sqlid_sottosezione";

  if($sqlid_attpell > 0) {
     $sqlselect_anagrafica .= " AND anagrafica.id IN(SELECT id_socio
                                               FROM  attivita_detail
                                               WHERE attivita_detail.id_attpell =  $sqlid_attpell
                                               AND     attivita_detail.tipo = '$sqltipo')";

     }  
    
  $sqlselect_anagrafica .= " ORDER BY 2";
  if($debug)
     echo "$fname: SQL = $sqlselect_anagrafica<br>";

  if($debug)
     echo "$fname: SQL ELENCO = $sqlselect_viaggi_attivita<br>";

  $stmt = $conn->prepare($sqlselect_viaggi_attivita);
  $stmt->bind_param('iiii', $sqlid_sottosezione, $sqlanno, $sqlid_sottosezione, $sqlanno);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqltipo_lista, $sqlid_attpell_lista, $sqldes_attpell, $sqldt_dal, $dummy);

  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione); 
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px;'>";
  
// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body></html>";
     return;
     }
  
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Emissione ricevuta</td>";
  echo "</tr>";

  echo "<form action='" . $redirect . "' method='POST' id='formRic'>";
  echo "<input type='hidden' name='id_anagrafica' value='" . $sqlid_anagrafica . "'>";
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>".  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
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

  // Anno di competenza
  $aa = date('Y');
  $ctr = $aa-3; // Visualizzo ultimi 3 anni
  echo "<tr>";
  echo "<td><p class='required'>Seleziona anno di competenza</p></td>";
  echo "<td>";
  echo "<select name='anno'  required class='required' onChange=this.form.submit();>";

  while($aa > $ctr) {
            echo "<option value=$aa";
  
            if($aa == $sqlanno) {
  	             echo " selected";
               }
            echo ">$aa</option>";
            $aa--;
           }
  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Seleziona Attivit&agrave/Viaggio/Pellegrinaggio</p></td>";
  echo "<td>";
  echo "<input type='hidden' name='tipo' id='tipo'>";
  echo "<select name='id_attpell' id='selectTipo' required class='required' onChange=setTipo();>";

  echo "<option value=''>--- Selezione Attivit&agrave/Viaggio/Pellegrinaggio ---</option>";
  $sqltipoArray[0] = $sqltipo_lista;
  $index=1;
  while($stmt->fetch()) { // Ciclo per le voci attivita'/viaggio/pellegrinaggio
            echo "<option value=" . $sqlid_attpell_lista;
            if($sqlid_attpell_lista == $sqlid_attpell) 
                echo " selected";
            echo ">" . htmlentities($sqldes_attpell, $defCharsetFlags, $defCharset) . " -&gt; " . $sqldt_dal . "</option>";
            $sqltipoArray[$index] = $sqltipo_lista;
            $index++;
    }

  echo "</select></td>";
  echo "</tr>";

  echo "</form>";

  if($sqlid_attpell > 0) {
  	
//  	echo $sqlselect_anagrafica;
 // 	return;
      echo "<form action='" . $print_target . "' method='POST' target='_blank'>";
      echo "<input type='hidden' name='id_sottosezione' value='" . $sqlid_sottosezione . "'>";
      echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
      echo "<input type='hidden' name='id_attpell' value=" . $sqlid_attpell . ">";
      echo "<tr>";
      echo "<td><p class='required'>Seleziona Nominativo</p></td>";
      echo "<td><select class='required' id='searchTxt' name='id-hidden' required/>";
      echo "<option value=>--- Seleziona nominativo ---</option>";
      $result = $conn->query($sqlselect_anagrafica);
      while($row = $result->fetch_assoc()) {
                echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["nome"], $defCharsetFlags, $defCharset) . "</option>";
       	    }
       	
      echo "</select></td>";
      echo "</tr>";
                   
      echo "<tr>";
      echo "<td><p>Causale</p></td>";
      echo "<td><p>";
      echo "<input id='causale' name='causale' size=110 maxlength='100' />";
      echo "</p></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td><p class='required'>Valore</p></td>";
      echo "<td><input class='numeror' id='importo' maxlength='5' size='5' type='number' min='0.01' step='0.01'. name='importo' value='' required/></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td><p class='required'>Tipo pagamento</p></td>";
      echo "<td>";
      echo "<select name='id_pagamento' class='required' required>";
  
      $result = $conn->query($sqlselect_tipopag);
      while($row = $result->fetch_assoc()) {
                echo "<option value=" . $row["id"];
                if($sqlid_tipopag == $row["id"])
                    echo " selected";
                echo ">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
       	     }
      echo "</select></td>";
      echo "</tr>";

      if($authMask["update"]) { // Permetto di inputare manualmente il numero e data del documento
         echo "<tr>";
         echo "<td><p>Eventuale inserimento manuale numero documento/data</p></td>";
         echo "<td><p><input id='n_doc' maxlength='6' size='5' type='number' min='0' max='99999' name='numero_doc' value=''/>
                  &nbsp;<input id='dt_doc' type='date' name='dt_doc' value='" . date('Y-m-d') . "'></p></td>";
         echo "</tr>";
        }

      echo "<tr><td colspan='2'><hr></td></tr>";
  
      if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
          echo "<tr>";
          echo  "<td colspan='2' style='text-align: center;'>";
          echo "<input class='in_btn' id='btn' type='submit' value='Emetti' onclick=\"{return conferma('". ritorna_js("Confermi emissione ricevuta  ?"). "');}\"></td>";
          echo "</tr>";
         }  
  
      echo "</form>";
     }  
  echo "</table>";
  echo "</div>";

$conn->close();

?>
</body>
</html>
<script>window.location.href('../php/emissione_ricevuta.php');
    function setTipo() {
           var obj = <?php echo json_encode($sqltipoArray); ?>,
                  sourceField = document.getElementById("selectTipo"),
                  i = sourceField.selectedIndex,
                  targetField = document.getElementById("tipo");
                  
        targetField.value = obj[i];
        document.getElementById("formRic").submit();
     }
  </script>  
