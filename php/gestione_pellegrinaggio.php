<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione Viaggi/Pellegrinaggi</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
  <script type="text/javascript" src="../js/setHiddenCosts.js"></script>
  <script type="text/javascript">
  // Funzione per abilitare/disabilitare pulsante di aggiunta voci
      function toggleButton(ref, bttnID) {
      //	alert('Ecco');
      	     var inputField =  document.getElementById(ref);
           var btn = document.getElementById(bttnID);

      	     if(inputField.value.trim() == '') { // Empty string
      	        btn.disabled=true;
              }
      	     else { // OK to proceed
      	        btn.disabled=false;
              }
       }
  </script>

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
*  Gestione dei pellegrinaggi
*
*  @file gestione_pellegrinaggio.php
*  @abstract Gestisce i pellegrinaggi
*  @author Luca Romano
*  @version 1.0
*  @time 2017-02-20
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-02-20
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
require_once('../php/carica_array.php');
define('MAINADD',1);
define('CHILDADD',2);
define('MAINREMOVE',3);
define('CHILDREMOVE',4);
define('MAINUPDATE',5);
define('CHILDUPDATE',6);

define('ARRAY_POS',4);

define('EURO',chr(128));
setlocale(LC_MONETARY, 'it_IT');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
$date_format=ritorna_data_locale();

$sott_app = ritorna_sottosezione_pertinenza();
$sqlid_sottosezione=$sott_app;
$multisottosezione = ritorna_multisottosezione();

$index=0;
$indexA=0;

$update=false;
$debug=false;
$fname=basename(__FILE__);
$msgText="Compilare i dati e, al termine, confermare modifica/inserimento";
$table_name="pellegrinaggi";
$redirect="../php/gestione_pellegrinaggio.php";

$sqlID=0;
$sqldescrizione=null;
$sqlid_desp=null;
$sqlanno=date('Y');
$sqldal=null;
$sqlal=null;
$sqlnote='';
$sqlOperation=0;
$sqlmainAdd=false;
$sqlmainRemove=0;
$sqlparentCost=0;
$sqlchildCost=0;

$sqlvoce_costo='';
$sqlcosto = 0;
$sqlnote='';
$sqltipo='V';

$sqlLoadID=0; // ID da cui caricare i costi

$sqlProgChildCost=0;
$sqlcosti=array();

$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_p = "SELECT 0, pellegrinaggi.id,  anno, 
                                       SUBSTRING(DATE_FORMAT(dal, '" . $date_format . "'),1,10) dal,  dal dal_order,
                                       descrizione_pellegrinaggio.descrizione
                          FROM   pellegrinaggi,
                                       descrizione_pellegrinaggio
                          WHERE  pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id AND
                                       pellegrinaggi.anno = $sqlanno AND
                                       pellegrinaggi.id_sottosezione = " . $sott_app; 
 
 if($multisottosezione) {
        $sqlselect_p .= " UNION
                                          SELECT 1, pellegrinaggi.id,  anno, 
                                          SUBSTRING(DATE_FORMAT(dal, '" . $date_format . "'),1,10) dal, dal dal_order,
                                          CONCAT(descrizione_pellegrinaggio.descrizione,' (Sottosezione di ' , sottosezione.nome,')') descrizione
                                          FROM    pellegrinaggi,
                                                       descrizione_pellegrinaggio,
                                                       sottosezione
                                          WHERE  pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                          AND      descrizione_pellegrinaggio.id_sottosezione = sottosezione.id AND
                                                       pellegrinaggi.anno = $sqlanno
                                          AND      pellegrinaggi.id_sottosezione != " . $sott_app; 
 }
$sqlselect_p .= " ORDER BY 1, 5 DESC, 6";

$sqlselect_other_costs = "SELECT pellegrinaggi.id, YEAR(dal) aa,
                                                       descrizione_pellegrinaggio.descrizione
                                          FROM    pellegrinaggi,
                                                       descrizione_pellegrinaggio
                                          WHERE  pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                          AND      pellegrinaggi.id IN(SELECT costi.id_attpell
                                                                                     FROM  costi)";

if(($userid = session_check()) == 0)
    return;

config_timezone();
$sqlutente = ritorna_utente();
$sqltimestamp=date('d/m/Y H:i:s');
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


  if ($_POST) { // se post allora fase di modifica o cambio sottosezione
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                    if($debug) {
                        echo $fname . ": KEY = " . $key . '<br>';
                        echo $fname . ": VALUE = " . $value . '<br>';
                        echo $fname . ": INDEX = " . $index . '<br><br>';                    	
                      }
                    
                    switch($key) {

         		           case "id-hidden": // Table ID (Update)
          					        $sqlID = $value;
      		       			        $update = true;
      				       	        break;

         		           case "id_sottosezione": // Sottosezione
          					        $sqlid_sottosezione = $value;
     				       	        break;

         		           case "id_attpell": // ID descrizione pellegrinaggio
          					        $sqlid_desp = $value;
     				       	        break;

         		           case "descrizione": // Descrizione aggiuntiva pellegrinaggio
          					        $sqldescrizione = $value;
      				       	        break;
 
         		           case "anno": // Anno
          					        $sqlanno = $value;
      				       	        break;
 
         		           case "dal": // Data inizio
          					        $sqldal = $value;
      				       	        break;
 
         		           case "al": // Data fine
          					        $sqlal = $value;
      				       	        break;

         		           case "note": // Note
          					        $sqlnote = $value;
      				       	        break;

         		           case "voce_costo": // Voce di costo
          					        $sqlvoce_costo = $value;
      				       	        break;                    

         		           case "costo": // Voce di costo
          					        $sqlcosto = $value;
      				       	        break;                    

         		           case "voce_costoA": // Voce di costo altri
          					       $sqlvoce_costoA = $value;
      				       	        break;                    

         		           case "costoA": // Valore costo
          					        $sqlcostoA = $value;
      				       	        break;                    

         		           case "removeMainCost": // Costo di voce principale da eliminare
          					        $sqlmainRemove = $value;
      				       	        break;                    

         		           case "updateMainCost": // Costo di voce principale da modificare
          					        $sqlUpdateMainCost = $value;
      				       	        break;                    

         		           case "parentCost": // Costo di voce principale a cui legare i costi aggiuntivi
          					        $sqlparentCost = $value;
      				       	        break;                    

         		           case "childCost": // ID Costo di voce secondario
          					        $sqlchildCost = $value;
      				       	        break;                    

         		           case "updateChildCost": // Costo di voce secondaria da modificare
          					        $sqlUpdateChildCost = $value;
      				       	        break;                    

         		           case "progChildCost": // Costo di voce secondario
          					        $sqlProgChildCost = $value;
      				       	        break;                    

         		           case "operazione": // Operazione richiesta
          					        $sqlOperation = $value;
      				       	        break;                    

         		           case "msgAlert": // Messaggio da visualizzare
          					        $msgText = $value;
      				       	        break;                    

         		           case "costiArray": // Array voci costi
          					        $sqlcosti = unserialize($value);
      				       	        break;                    

         		           case "loadArray": // Array voci costi (da altro viaggio/pellegrinaggio)
          					        $sqlLoadID = $value;
          					        $sqlcosti = carica_array($conn,$sqlLoadID,'V', true );
      				       	        break;                    
      				       }
                   $index++;
                  }
                  
       if($update && ($sqlOperation == 0)) {
	       $sql = "SELECT descrizione, id_sottosezione, id_attpell,anno, dal,al, note,
                                 DATE_FORMAT((data), '" .$date_format . "') data,
                                 utente FROM " . $table_name . " WHERE id = " . $sqlID;
                                 
         if($debug)
           echo "$fname SQL = $sql<br>";
         $result = $conn->query($sql);
         $row = $result->fetch_assoc();
         $sqldescrizione = $row["descrizione"];
         $sqlid_sottosezione = $row["id_sottosezione"];
         $sqlid_desp = $row["id_attpell"];
         $sqlanno = $row["anno"];
         $sqldal = $row["dal"];
         $sqlal = $row["al"];
         $sqlnote = $row["note"];
         $sqltimestamp= $row["data"];
         $sqlutente= $row["utente"];
         $a=array();
         $sqlcosti = carica_array($conn, $sqlID, $sqltipo);
         if($debug) {
             echo "$fname:  ARRAY=";
             var_dump($sqlcosti);
          }
         
        }
       else { // Sto aggiungendo/eliminando voci di costo/sottocosto
          switch($sqlOperation) {
          	         case MAINADD: // Aggiungo voce di costo principale
          	                       if($debug)
          	                           echo "$fname Aggiungo voce di costo principale<br>";
          	                        
                                    array_push($sqlcosti, array(0, $sqlvoce_costo  , $sqlcosto, 0));
                                    break;

          	         case MAINUPDATE: // Modifico voce di costo principale
          	                       if($debug)
          	                           echo "$fname Modifico voce di costo principale<br>";
          	                        
                                    //array_push($sqlcosti, array(0, $sqlvoce_costo  , $sqlcosto, 0));
                                   // $sqlProgMainCost += 1;
                                   $sqlcosti[$sqlUpdateMainCost][1] = $sqlvoce_costo;
                                   $sqlcosti[$sqlUpdateMainCost][2] = $sqlcosto;
                                   break;

          	         case MAINREMOVE: // Rimuovo una voce principale
          	                       if($debug)
          	                           echo "$fname Elimino una voce di costo principale $sqlparentCost<br>";
                                    //array_push($sqlcosti, (array((htmlentities($sqlvoce_costo)), $sqlcosto)));
                                    unset($sqlcosti[$sqlmainRemove]);
                                    $sqlcosti = array_values($sqlcosti);
                                    break;

          	         case CHILDADD: // Aggiungo voce di costo secondaria
          	                       if($debug)
          	                           echo "$fname Aggiungo voce di costo secondaria<br>";

                                    //if(array_key_exists(2,$sqlcosti[$sqlparentCost]))
                                       //$sqlcosti[$sqlparentCost][2][$sqlchildCost] = array($sqlvoce_costoA, $sqlcostoA);
                                    //else
                                       array_push($sqlcosti[$sqlparentCost],array(0, $sqlvoce_costoA, $sqlcostoA));
                                    break;

          	         case CHILDUPDATE: // Modifico voce di costo secondaria
          	                       if($debug)
          	                           echo "$fname Modifico voce di costo secondaria<br>";
                                   $sqlcosti[$sqlparentCost][$sqlUpdateChildCost][1] = $sqlvoce_costoA;
                                   $sqlcosti[$sqlparentCost][$sqlUpdateChildCost][2] = $sqlcosto;
                                   break;

          	         case CHILDREMOVE: // Rimuovo una voce dei costi aggiuntivi
          	                       if($debug)
          	                           echo "$fname Elimino una voce di costo aggiuntivo $sqlParentCost/$sqlchildCost<br>";

                                    array_splice($sqlcosti[$sqlparentCost], $sqlchildCost, 1);
                                    break;
                     }    
         }
     }
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
  echo "<td colspan='2' class='titolo'>Gestione Viaggi/Pellegrinaggi";
  
  if($update)
     echo "&nbsp;<img src='../images/Unitalsi_edit32.png' title='Aggiornamento'>";
  echo "</td>";
  echo "</tr>";

// Campo per la ricerca della riduzione
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Seleziona Viaggio/Pellegrinaggio</p></td>";
  echo "<td><p class='search'><select class='search' id='searchTxt' name='id-hidden' onChange='this.form.submit();'>";
  echo "<option value=0>--- Seleziona la voce da modificare ---</option>";
  
  if($debug)
      echo "$fname SQL datalist = $sqlselect_p<br>";
  $result = $conn->query($sqlselect_p);
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" .
                      $row["dal"] . " " .htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select>";
  echo "</td></tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>".  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<form id='changeSottosezione' action='" . $redirect . "' method='POST'>";
      echo "<td><p class='required'><select class='required' name='id_sottosezione' ".
               "onChange=\"document.getElementById('changeSottosezione').submit();\" required>" ;
      echo "<option value=''>--- Seleziona la sottosezione ---</option>";
      $result = $conn->query($sqlselect_sottosezione);
      while($row = $result->fetch_assoc()) {
       	       echo "<option value=" . $row["id"];
                 if($row["id"] == $sqlid_sottosezione)  {
   	    	           echo " selected";
                    } 	
       	echo ">" . htmlentities($row["nome"],$defCharsetFlags, $defCharset) . "</option>";
       	}
       echo '</select></p></td>'; 
       echo '</form></p></td>'; 
     }
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Descrizione</p></td>";
  echo "<td><select class='required' id='id_des_pellegrinaggio-list' autofocus
                onChange=\"setHiddenCostValues('id_des_pellegrinaggio-list', 'id_attpell', 1);\" 
                required>" ;
  echo "<option value=''>--- Seleziona descrizione pellegrinaggio ---</option>";
  $sqlSelect_desp = "SELECT   id, descrizione
                                   FROM    descrizione_pellegrinaggio
                                   WHERE  id_sottosezione = " . $sqlid_sottosezione .
                                 " ORDER BY 2";
      
  $result = $conn->query($sqlSelect_desp);
  while($row = $result->fetch_assoc()) {
       	   echo "<option value=" . $row["id"];
             if($row["id"] == $sqlid_desp)  {
   	    	       echo " selected";
                } 	
       	echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . "</option>";
       	}
  echo "</select></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p>Descrizione aggiuntiva</p></td>";
  echo "<td><p><input id='descrizione-h' maxlength='100' size='110' type='value' value='" .  htmlentities($sqldescrizione, $defCharsetFlags, $defCharset) .
                     "' onkeyup=\"setHiddenCostValues('descrizione-h', 'descrizione', 1);\" required/></p></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Anno di competenza</p></td>";
  echo "<td><input class='required' id='anno-h' type=number min='2001' max='2100' maxlength=4 value=$sqlanno
                     onChange=\"setHiddenCostValues('anno-h', 'anno', 1);\" required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Dal</p></td>";
  echo "<td><input class='required' id='dal-h' type='date' value='" .$sqldal ."'
                     onChange=\"setHiddenCostValues('dal-h', 'dal', 1);\" required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Al</p></td>";
  echo "<td><input class='required' id='al-h' type='date' value='" .$sqlal ."'
                     onChange=\"setHiddenCostValues('al-h', 'al', 1);\" required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
 
  if($msgText) { // Se messaggio valorizzato visualizzo
      echo "<tr>";
      echo "<td colspan='2'><p class='alert'>$msgText</p></td>";
      echo "</tr>";
     } 
 
   if(!$update) { // Propongo di caricare i dati dei costi da altro viaggio/pellegrinaggio gia' valorizzato
      $sqlselect_other_costs .= " AND descrizione_pellegrinaggio.id_sottosezione = $sqlid_sottosezione
                                                  ORDER BY 2 DESC,1";

      if($debug)
          echo "$fname: SELECT other costs = $sqlselect_other_costs<br>";
      echo "<form id='loadCosts' action='" . $redirect . "' method='post'>";
      echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
      echo "<input type='hidden' name='id_attpell' value=$sqlid_desp>";
//      echo "<input type='hidden' name='descrizione' value='" . htmlentities($sqldescrizione,$defCharsetFlags, $defCharset) . "'>";
      echo "<input type='hidden' name='descrizione' value='" . $sqldescrizione . "'>";
      echo "<input type='hidden' name='anno' value=$sqlanno>";
      echo "<input type='hidden' name='dal' value='" . $sqldal . "'>";
      echo "<input type='hidden' name='al' value='" . $sqlal . "'>";
      //echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";

      echo "<tr>";
      echo "<td><p>Carica i costi da</p></td>";
      echo "<td><select name='loadArray' class='search' onChange='document.getElementById(\"loadCosts\").submit();'>";
      echo "<option value=>--- Seleziona sorgente tabella costi ---</option>";

      $result = $conn->query($sqlselect_other_costs);
      while($row = $result->fetch_assoc()) {
       	      echo "<option value=" . $row["id"] . ">";
       	      echo htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . " (" . $row["aa"] . ")</option>";
       	    }
      echo "</select></td>";
      echo "</tr>";
      echo "</form>";
      }
// Genero elenco costi attivita'
  for($index = 0; $index < count($sqlcosti) ;$index++) {
  	
  	     echo "<tr><td colspan='2'><table>";
  	
  	     if($debug)
  	         echo "$fname INDEX=$index<br>";
  	     $sqldesc = $sqlcosti[$index][1];
  	     $sqlcosto = $sqlcosti[$index][2];

  	     $sqltessera = $sqlcosti[$index][3];
         
        // Form modifica voce principale
        echo "<form name='updateVoce' action='" . $redirect . "' method='POST'>";
        echo "<input type='hidden' name='operazione' value=" . MAINUPDATE . ">";
        if($update) {
            echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
           }
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
        echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
        echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
        echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
        echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
        echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
        echo "<input type='hidden' name='updateMainCost' value=" . $index . ">";
        echo "<input type='hidden' name='descrizione'>";
        echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
        echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";

        echo "<tr>";
        echo "<td><p class='required'>(#" . sprintf('%03d', ($index+1)) . ") Voce costo principale / Valore</p></td>";
        echo "<td><p class='required'>";
        echo "<input class='field' name='voce_costo' size='70' maxlength='100' value='" . 
                 $sqldesc . "'/>";
        echo "&nbsp;";        
        echo "<input class='prezzo' name='costo' type='number' step='0.01' size='11' maxlength='10' value='" . 
                sprintf('%.2f', $sqlcosto) . "'/>";
        echo "</p></td>";
        
        echo "<td>";
        echo "<input name='Submit' align='center' type='image' src='../images/Unitalsi_edit16.ico' title='Aggiorna voce costo'>";
        echo "</td>";
        echo "</form>";
       
        // Form eliminazione voce principale
        if(!$update) {
             echo "<form name='deleteVoce' action='" . $redirect . "' method='POST'>";
             echo "<input type='hidden' name='operazione' value=" . MAINREMOVE . ">";
             echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
             echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
             echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
             echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
             echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
             echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
             echo "<input type='hidden' name='removeMainCost' value=" . $index . ">";
             echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
             echo "<input type='hidden' name='descrizione'>";
             echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
             echo "<input type='hidden' name='msgAlert' value='Dato eliminato, al termine confermare la schermata'>";
       
            echo "<td>";
            echo "<input name='Submit' align='center' type='image' src='../images/delete.png' title='Elimina voce costo'>";
            echo "</td>";
            echo "</tr>";
            echo "</form>";
         }
         else {
            echo "<td>&nbsp;</td>";
            echo "</tr>";
         }
        
        // Aggiungo sottovoci costo
        // Form aggiunta costi aggiuntivi alla voce principale
       
        echo "<input type='hidden' name='voce_costo_m' value='" . $sqlcosti[$index][0]. "'>";
 
        echo "<tr><td colspan='4'><table>";
        
        $array_exists = array_key_exists(ARRAY_POS,$sqlcosti[$index]);

        // Qui ciclo for per eventuali ulteriori costi aggiuntivi
        for($indexA = ARRAY_POS; $array_exists && ($indexA  < (count($sqlcosti[$index]))); $indexA++) {
  	         $sqldesc = $sqlcosti[$index][$indexA][1];
        	   $sqlcosto = $sqlcosti[$index][$indexA][2];
         
        // Form modifica voce secondaria
            echo "<form name='updateVoceAggiuntiva' action='" . $redirect . "' method='POST'>";
            echo "<input type='hidden' name='operazione' value=" . CHILDUPDATE . ">";
            if($update) {
               echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
              }
            echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
            echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
            echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
            echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
            echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
            echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
            echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
            echo "<input type='hidden' name='parentCost' value=" . $index. ">";
            echo "<input type='hidden' name='updateChildCost' value=" . $indexA . ">";
            echo "<input type='hidden' name='descrizione'>";
            echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
            echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";

            echo "<tr>"; 
            echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><p class='sottotitoli_lista'>(#" . sprintf('%03d/%03d', ($index+1), ($indexA-ARRAY_POS+1)) . ") Voce costo aggiuntiva / Valore</p></td>";
            echo "<td><p class='required'>";
            echo "<input class='field' name='voce_costoA' size='70' maxlength='100' value='" . 
                 $sqldesc . "'/>";
            echo "&nbsp;<input class='prezzo' name='costo' type='number' step='0.01' size='11' maxlength='10' value='" . 
                    sprintf('%.2f', $sqlcosto) . "'/>&nbsp";
            echo "</td>";
                
            echo "<td><input name='Submit' align='center' type='image' src='../images/Unitalsi_edit16.ico' title='Aggiorna voce costo'>";
            echo "</td></form>";

            if(!$update) {
                echo "<form name='deleteVoceAggiuntiva' action='" . $redirect . "' method='POST'>";
                echo "<input type='hidden' name='operazione' value=" . CHILDREMOVE . ">";
                echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
                echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
                echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
                echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
                echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
                echo "<input type='hidden' name='parentCost' value=" . $index. ">";
                echo "<input type='hidden' name='childCost' value=" . $indexA. ">";
                echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
                echo "<input type='hidden' name='descrizione'>";
                echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
                echo "<input type='hidden' name='msgAlert' value='Dato eliminato, al termine confermare la schermata'>";
        
                echo "<td>";
                echo "<input name='Submit' align='center' type='image' src='../images/delete.png' title='Elimina voce costo aggiuntiva'>";
                echo "</td></form>";
              }
            else {
                echo "<td>&nbsp;</td>";
               }
            echo "</tr>";
           }

        echo "<form name='costiAggiuntivi' action='" . $redirect . "' method='POST'>";
        if($update) {
            echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
           }
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
        echo "<input type='hidden' name='descrizione'>";
        echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
        echo "<input type='hidden' name='parentCost' value=" . $index . ">";
        echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
        echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
        echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
        echo "<input type='hidden' name='childCost' value=" . $indexA . ">";
        echo "<input type='hidden' name='operazione' value=" . CHILDADD . ">";
        echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";
        echo '<tr>';  
        echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
        echo "<td><p class='sottotitoli_lista'>(#" . sprintf('%03d/%03d', ($index+1), ($indexA-ARRAY_POS+1)) . ") Voce costo aggiuntiva / Valore</p></td>";
        echo "<td><p class='required'>";

        echo "<input class='field' placeholder='Inserisci l&apos;eventuale voce di costo aggiuntiva' autocomplete='off' size='70' maxlength='100' id='v" . $index . ($indexA+1) . "' name='voce_costoA' onkeyup=toggleButton('v" . $index . ($indexA+1) . "','btn" . $index . ($indexA+1) ."') />";
        echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01' name='costoA' value='0.00' required/></td>";
        echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
        echo "<td><input type='submit' id='btn" . $index . ($indexA+1) ."' value='+' disabled color='cyan'></p></td>";
        echo "<td>&nbsp;</td>";
        echo "</tr></form>";
        echo "<tr><td colspan='6'><hr></td></tr>";
        echo "</table></td></tr>";
        }

  echo "<form name='costi' action='" . $redirect . "' method='POST'>";
  if($update) {
      echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
     }
  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
  echo "<input type='hidden' name='anno' value=" . $sqlanno . ">";
  echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
  echo "<input type='hidden' name='descrizione' value='" .  $sqldescrizione . "'>";
  echo "<input type='hidden' name='dal' value='" .  $sqldal . "'>";
  echo "<input type='hidden' name='al' value='" .  $sqlal . "'>";
  echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
  echo "<input type='hidden' name='operazione' value=" . MAINADD . ">";
  echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";
  echo '<tr>';  
  echo "<td><p class='required'>(#" . sprintf('%03d', ($index+1)) . ") Voce costo principale / Valore</td></p></td>";
  echo "<td><p class='required'>";

  if($index==0) {
      echo "<input placeholder='Inserisci la voce di costo' autocomplete='off' class='required' size='70' maxlength='100' id='v' name='voce_costo' onkeyup=toggleButton('v','btn') required/>";
      echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01'name='costo' value='0.00' required/>";
     }
  else {
      echo "<input class='field' placeholder='Inserisci la voce di costo' autocomplete='off' size='70' maxlength='100' id='v' name='voce_costo' onkeyup=toggleButton('v','btn') />";
      echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01'name='costo' value='0.00' required/>";
     }
   echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
   echo "&nbsp;<input type='submit' id='btn' value='+' disabled color='cyan'></p></td>";
   echo "</form>";

  echo "<tr>";
  echo "<td colspan='4'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  if($update) {
     echo "<form action='../php/insert_attivita_costi.php' method='POST'>";
     echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
     echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
     echo "<input type=\"hidden\" name=\"table_name\" value='" . $table_name . "'>";
     echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
     echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
     echo "<input type='hidden' name='dal' value='" . $sqldal . "'>";
     echo "<input type='hidden' name='al' value='" . $sqlal . "'>";
     echo "<input type='hidden' id='descrizione' name='descrizione' value='" . $sqldescrizione . "'>";
     echo "<input type='hidden' id='id_sottosezione' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
     echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td colspan='2'><table style='width: 100%;'><tr>";

     if($authMask["update"]) { // Verifico se non ci sono soci associati
         echo  "<td class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'></td>";
      }
     echo "</form>";

     if($authMask["update"]) {
         echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
         echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
         echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
         echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldescrizione . " ?") ."');}\"></form></td>";
   	     }  
   	   echo "</tr></table></td></tr>";
   	  }  
   else { // inserimento attivita'
   	  echo "<form action='../php/insert_attivita_costi.php' method='post'>";
     echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
     echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
     echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
     echo "<input type='hidden' name='anno' value='" . $sqlanno . "'>";
     echo "<input type='hidden' name='dal' value='" . $sqldal . "'>";
     echo "<input type='hidden' name='al' value='" . $sqlal . "'>";
     echo "<input type='hidden' id='descrizione' name='descrizione' value='" . $sqldescrizione . "'>";
     echo "<input type='hidden' id='id_sottosezione' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
     echo "<input type='hidden' name='id_attpell' value=" . $sqlid_desp . ">";
     echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";

     if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
         echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'
                     onClick=\"{return(setHiddenCostValues(('id_des_pellegrinaggio-list', 'id_attpell',1));}\"></td>";
         }
     echo "</form>";
     }
  echo "</tr>";
  echo "</table>";
  if($debug)
      echo "$fname DUMP ARRAY = " . var_dump($sqlcosti) . "<br>";

  echo "</div>";
  $conn->close();

?>
</body>
</html>
