<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione attivit&agrave;</title>
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
*  Gestione della descrizione delle attivita'/viaggi/pellegrinaggi
*
*  @file gestione_attivita.php
*  @abstract Gestisce la descrizione delle attivita
*  @author Luca Romano
*  @version 1.0
*  @time 2017-01-23
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-01-23
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
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();

$index=0;
$indexA=0;

$update = false;
$debug=false;
$fname=basename(__FILE__);
$msgText="Compilare i dati e, al termine, confermare modifica/inserimento";
$table_name="attivita";
$redirect="../php/gestione_attivita.php";

$sqlID=0;
$sqldescrizione='';
$sqlid_sottosezione=$sott_app;
$sqlOperation=0;
$sqlmainAdd=false;
$sqlmainRemove=0;
$sqlparentCost=0;
$sqlchildCost=0;

$sqlvoce_costo='';
$sqlcosto = 0;
$sqltessera=0;
$sqlnote='';
$sqltipo='A';

$sqlProgChildCost=0;
$sqlcosti=array();
$desc_sottosezione='';

$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";
if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselect_attivita = "SELECT id,  descrizione
                                    FROM   attivita";

if(!$multisottosezione) {
   $sqlselect_attivita .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_attivita .= " ORDER BY 2";

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

  if ($_POST) { // se post allora fase di modifica o aggiunta voci di costo
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

         		           case "descrizione": // Descrizione attivita
          					        $sqldescrizione = $value;
      				       	        break;
 
         		           case "tmp": // Descrizione attivita
          					        $sqldescrizione = $value;
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

         		           case "tessera": // Rinnovo tessera
          					        $sqltessera = $value;
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

         		           case "updateChildCost": // Costo di voce secondaria da modificare
          					        $sqlUpdateChildCost = $value;
      				       	        break;                    

         		           case "childCost": // ID Costo di voce secondario
          					        $sqlchildCost = $value;
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
      				       }
                   $index++;
                  }
                  
       if($update && ($sqlOperation == 0)) {
           $sql = "SELECT descrizione, id_sottosezione, note,
                                     DATE_FORMAT((data), '" .$date_format . "') data,
                                     utente FROM attivita WHERE id = " . $sqlID;
                                 
          if($debug)
              echo "$fname SQL = $sql<br>";
          $result = $conn->query($sql);
          $row = $result->fetch_assoc();
          $sqldescrizione = $row["descrizione"];
          $sqlid_sottosezione = $row["id_sottosezione"];
          $sqlnote = $row["note"];
          $sqltimestamp= $row["data"];
          $sqlutente= $row["utente"];
          
          $sqlcosti = carica_array($conn, $sqlID);
         }
       else { // Sto aggiungendo/eliminando voci di costo/sottocosto
          switch($sqlOperation) {
          	         case MAINADD: // Aggiungo voce di costo principale
          	                       if($debug)
          	                           echo "$fname Aggiungo voce di costo principale<br>";
          	                        
                                    array_push($sqlcosti, array(0, $sqlvoce_costo  , $sqlcosto, $sqltessera));
                                   // $sqlProgMainCost += 1;
                                    break;

          	         case CHILDADD: // Aggiungo voce di costo secondaria
          	                       if($debug)
          	                           echo "$fname Aggiungo voce di costo secondaria<br>";

                                    //if(array_key_exists(2,$sqlcosti[$sqlparentCost]))
                                       //$sqlcosti[$sqlparentCost][2][$sqlchildCost] = array($sqlvoce_costoA, $sqlcostoA);
                                    //else
                                       array_push($sqlcosti[$sqlparentCost],array(0, $sqlvoce_costoA, $sqlcostoA));
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

          	         case CHILDUPDATE: // Modifico voce di costo secondaria
          	                       if($debug)
          	                           echo "$fname Modifico voce di costo secondaria<br>";
                                   $sqlcosti[$sqlparentCost][$sqlUpdateChildCost][1] = $sqlvoce_costoA;
                                   $sqlcosti[$sqlparentCost][$sqlUpdateChildCost][2] = $sqlcosto;
                                   break;

          	         case CHILDREMOVE: // Rimuovo una voce dei costi aggiuntivi
          	                       if($debug)
          	                           echo "$fname Elimino una voce di costo aggiuntivo $sqlParentCost/$sqlchildCost<br>";

//                                    unset($sqlcosti[$sqlparentCost][$sqlchildCost]);
                                    array_splice($sqlcosti[$sqlparentCost], $sqlchildCost, 1);
                                    //$sqlcosti = array_values($sqlcosti);
                                    //$sqlProgMainCost -= 1;
                                    break;
                     }    
         }
     }
  $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione); 
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
  echo "<td colspan='2' class='titolo'>Gestione Anagrafica attivit&agrave;";

  if($update)
     echo "&nbsp;<img src='../images/Unitalsi_edit32.png' title='Aggiornamento'>";
  echo "</td>";
  echo "</tr>";

// Campo per la ricerca del comune
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<tr>";
  echo "<td><p class='search'>Seleziona Attivit&agrave;</td></p></td>";
  echo "<td><select class='search' id='searchTxt' name='id-hidden'  onChange='this.form.submit();'>";
  echo "<option value=0>--- Seleziona la voce da modificare ---</option>";
  $result = $conn->query($sqlselect_attivita);
  while($row = $result->fetch_assoc()) {
            echo "<option value=\"" . $row["id"] . "\">" . htmlentities($row["descrizione"], $defCharsetFlags, $defCharset) . "</option>";
              } 	
       
  // End select
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
  if(!$multisottosezione) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sott_app . ">";
      echo "<td><p class='required'>" . htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' id='id_sottosezione-list' 
               onChange=\"setHiddenCostValues('id_sottosezione-list', 'id_sottosezione', 1);\" required>" ;
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

  echo "<tr>";
  echo "<td><p class='required'>Nome Attivit&agrave;</p></td>";
  echo "<td><input class='required' onkeyup=\"setHiddenCostValues('tmp', 'descrizione', 1);\" onFocus=\"setHiddenCostValues('tmp', 'descrizione', 1);\" maxlength='100' size='110' type='value' name='tmp' id='tmp' autofocus value='" .  htmlentities($sqldescrizione, $defCharsetFlags, $defCharset) ."' required/></td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";
 
  if($msgText) { // Se messaggio valorizzato visualizzo
      echo "<tr>";
      echo "<td colspan='2'><p class='alert'>$msgText</p></td>";
      echo "</tr>";
     } 
 
// Genero elenco costi attivita'
  for($index = 0; $index < count($sqlcosti) ;$index++) {
  	     $sqldesc = $sqlcosti[$index][1];
  	     $sqlcosto = $sqlcosti[$index][2];
  	     
  	     $sqltessera = $sqlcosti[$index][3];

        if($index == 0)  {
            echo "<tr><td colspan='2' align='left'><input type='checkbox' name='tessera' value=1";
                if($sqltessera)
                    echo " checked";
                echo ">Rinnovo Tessera</td></tr>"; 
               }
  	     
  	     echo "<tr><td colspan='2'><table>";
          
        // Form modifica voce principale
        echo "<form name='updateVoce' action='" . $redirect . "' method='POST'>";
        echo "<input type='hidden' name='operazione' value=" . MAINUPDATE . ">";
        if($update) {
            echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
           }
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
        echo "<input type='hidden' name='updateMainCost' value=" . $index . ">";
        echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
        echo "<input type='hidden' name='descrizione'>";
        echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
        echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";

        echo "<tr>";
        echo "<td><p class='required'>(#" . sprintf('%03d', ($index+1)) . ") Voce costo principale / Valore</p></td>";
        echo "<td><p class='required'>";
        echo "<input class='field' name='voce_costo' size='70' maxlength='100' value='" . 
                 $sqldesc . "'/>";
        echo "&nbsp;";        
        echo "<input class='prezzo' name='costo' type='number' size='11' maxlength='10' value='" . 
                sprintf('%.2f', $sqlcosto) . "'/>";
        echo "</p></td>";
        
        echo "<td>";
        echo "<input name='Submit' align='center' type='image' src='../images/Unitalsi_edit16.ico' title='Aggiorna voce costo'>";
        echo "</td>";
        echo "</form>";
       
        // Forma eliminazione voce principale
        if(!$update) {
            echo "<form name='deleteVoce' action='" . $redirect . "' method='POST'>";
            echo "<input type='hidden' name='operazione' value=" . MAINREMOVE . ">";
            echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
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
       
        echo "</td></tr>";
        // Qui ciclo for per eventuali ulteriori costi aggiuntivi
 
        echo "<tr><td colspan='2'><table>";
        
        $array_exists = array_key_exists(ARRAY_POS,$sqlcosti[$index]);
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
            echo "&nbsp;<input class='prezzo' name='costo' type='number' size='11' maxlength='10' value='" . 
                    sprintf('%.2f', $sqlcosto) . "'/>&nbsp";
            echo "</td>";
                
            echo "<td><input name='Submit' align='center' type='image' src='../images/Unitalsi_edit16.ico' title='Aggiorna voce costo'>";
            echo "</td></form>";
        	   
            if(!$update) {
                echo "<form name='deleteVoceAggiuntiva' action='" . $redirect . "' method='POST'>";
                echo "<input type='hidden' name='operazione' value=" . CHILDREMOVE . ">";
                echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
                echo "<input type='hidden' name='parentCost' value=" . $index. ">";
                echo "<input type='hidden' name='childCost' value=" . $indexA. ">";
                echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
                echo "<input type='hidden' name='descrizione'>";
                echo "<input type='hidden' name='note' value='" . htmlentities($sqlnote) . "'>";
                echo "<input type='hidden' name='msgAlert' value='Dato eliminato, al termine confermare la schermata'>";
        
                echo "<td>";
                echo "<input name='Submit' align='center' type='image' src='../images/delete.png' title='Elimina voce costo aggiuntiva'>";
                echo "</td>";
                echo "</form>";
              }
            else {
                echo "<td>&nbsp;</td>";
               }
            echo "</tr>";
        } // End for sottocosti

       echo "<form name='costiAggiuntivi' action='" . $redirect . "' method='POST'>";
       if($update) {
           echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
          }
        echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
        echo "<input type='hidden' name='descrizione'>";
        echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
        echo "<input type='hidden' name='parentCost' value=" . $index . ">";
        echo "<input type='hidden' name='childCost' value=" . $indexA . ">";
        echo "<input type='hidden' name='operazione' value=" . CHILDADD . ">";
        echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";
        echo '<tr>';  
        echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
        echo "<td><p class='sottotitoli_lista'>(#" . sprintf('%03d/%03d', ($index+1), ($indexA-ARRAY_POS+1)) . ") Voce costo aggiuntiva / Valore</p></td>";
        echo "<td><p class='required'>";

        echo "<input class='field' placeholder='Inserisci l&apos;eventuale voce di costo aggiuntiva' autocomplete='off' size='70' maxlength='100' id='v" . $index . ($indexA+1) . "' name='voce_costoA' onkeyup=toggleButton('v" . $index . ($indexA+1) . "','btn" . $index . ($indexA+1) ."') />";
        echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01' name='costoA' value='0.00' required/>";
        echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
        echo "&nbsp;<input type='submit' id='btn" . $index . ($indexA+1) ."' value='+' disabled color='cyan'></p></td>";
        echo "</tr></form>";
        echo "<tr><td></td><td colspan='2'><hr></td></tr>";
        echo "</table></td></tr>";
    } // End for costi principali

  echo "<form name='costi' action='" . $redirect . "' method='POST'>";
  if($update) {
      echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
     }
  echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
  echo "<input type='hidden' name='descrizione'>";
  echo "<input type='hidden' name='progMainCost' value=" . count($sqlcosti) . ">";
  echo "<input type='hidden' name='operazione' value=" . MAINADD . ">";
  echo "<input type='hidden' name='msgAlert' value='Dato acquisito, al termine confermare la schermata'>";
  echo '<tr>';  
  echo "<td><p class='required'>(#" . sprintf('%03d', ($index+1)) . ") Voce costo principale / Valore</td></p></td>";
  echo "<td><p class='required'>";

  if($index==0) {
      echo "<input placeholder='Inserisci la voce di costo' autocomplete='off' class='required' size='70' maxlength='100' id='v' name='voce_costo' onkeyup=toggleButton('v','btn') required/>";
      echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01'name='costo' value='0.00' required/>";
      echo "&nbsp;<input type='checkbox' name='tessera' value=1>Rinnovo Tessera&nbsp;"; 

     }
  else {
      echo "<input class='field' placeholder='Inserisci la voce di costo' autocomplete='off' size='70' maxlength='100' id='v' name='voce_costo' onkeyup=toggleButton('v','btn') />";
      echo "&nbsp;<input class='prezzo' id='prezzo' maxlength='10' size='11' type='number' min='0.00' step='0.01'name='costo' value='0.00' required/>";
     }
   echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
   echo "&nbsp;<input type='submit' id='btn' value='+' disabled color='cyan'></p></td>";
   echo "</form>";
  
  echo "<tr>";
  echo "<td colspan='2'><hr></td>";
  echo "</tr>";

  echo "<tr>";
  if($update) {
     echo "<form action='../php/insert_attivita_costi.php' method='POST'>";
     echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
     echo "<input type='hidden' name='id-hidden' value='" . $sqlID . "'>";
     echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
     echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
     echo "<input type='hidden' id='descrizione' name='descrizione'>";
     echo "<input type='hidden' id='id_sottosezione' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
     echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
     echo "<tr><td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqltimestamp . " Utente " . $sqlutente.")</td>";
     echo "</tr>";

     echo "<tr>";
     echo "<td colspan='2'><table style='width: 100%;'><tr>";

     if($authMask["update"]) {
         echo  "<td class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'></td>";
        }
     echo "</form>";

     if($authMask["delete"]) {
     	// Verifico se esistono delle movimentazioni sull'attivit�. Se presenti NON permetto la cancellazione
     	
     	   $sql = "SELECT id_attivita
     	                FROM   attivita_m
     	                WHERE id_attivita = $sqlID";
     	                
     	   $checkM = $conn->query($sql);
     	   
     	   if($checkM->num_rows == 0) { // Ok, posso eliminare
             echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
             echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
             echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
             echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
             echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello " . $sqldescrizione . " ?") ."');}\"></td>";
             echo "</form>";   
          }      
   	     }
   	     echo "</tr></table></td></tr>";
   	 }
  
   else {
      if($authMask["insert"]) { // Visualizzo pulsante solo se abilitato
  	       echo "<form action='../php/insert_attivita_costi.php' method='post'>";
          echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
          echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
          echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
          echo "<input type='hidden' id='descrizione' name='descrizione'>";
          echo "<input type='hidden' id='id_sottosezione' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
          echo "<input type='hidden' name='costiArray' value='" . htmlentities(serialize($sqlcosti)) . "'>";
          echo  "<td colspan='2' class='button'><p><input class='in_btn' id='btn' type='submit' value='Inserisci'
                      onClick=\"{return(setHiddenCostValues('tmp', 'descrizione',1));}\"></p>";
          echo "</form></td>";
          echo "</tr>";
        }
     }
  echo "</table>";
  if($debug)
     echo "$fname DUMP ARRAY = " . var_dump($sqlcosti) . "<br>";

  echo "</div>";
  $conn->close();

?>
</body>
</html>
