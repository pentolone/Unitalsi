<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione ricevute</title>
  <LINK href="../css/unitalsi.css" rel="stylesheet" type="text/css">
  <meta charset="ISO-8859-1">
  <meta name="author" content="Luca Romano" >

  <script type="text/javascript" src="../js/messaggi.js"></script>
  <script type="text/javascript" src="../js/searchTyping.js"></script>
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
*  Gestione delle ricevute (modifica/cancellazione)
*
*  @file gestione_ricevute.php
*  @abstract Gestisce lmodifica/cancellazione delle ricevute
*  @author Luca Romano
*  @version 1.0
*  @time 2017-06-23
*  @history prima versione
*  
*  @first 1.0
*  @since 2017-06-23
*  @CompatibleAppVer All
*  @where Monza
*
****************************************************************************************************/
$debug=false;
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags();
setlocale(LC_MONETARY, 'it_IT');

$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$fname=basename(__FILE__);
$date_format=ritorna_data_locale();

$index=0;
$update=false;
$nrec=20; // numero di record da visualizzare

// Filtri ricerca
$filterRic='%'; // filtro per intestatario ricevuta
$filterRicDal='1'; // filtro per numero ricevuta
$filterRicAl='99999'; // filtro per numero ricevuta
$table_name="ricevute";
$redirect="../php/gestione_ricevute.php";

// Variabili da tabella
$sqlID=0;
$sqlanno=date('Y');
$sqlanno_min=null;
$sqlanno_selected=$sqlanno;

$sqln_ricevuta=0;
$sqlnome;
$sqlid_attpell=0;
$sqltipo=null;;
$sqldata;
$sqlutente;

$sqlid_sottosezione=$sott_app;

$desc_sottosezione='';


$sqlselect_sottosezione = "SELECT id, nome
                                             FROM   sottosezione";

if(!$multisottosezione) {
   $sqlselect_sottosezione .= " WHERE id_sottosezione = " . $sott_app; 
 }
$sqlselect_sottosezione .= " ORDER BY 2";

$sqlselectanno_ricevuta = "SELECT MIN(YEAR(data_ricevuta)) amin
                                             FROM   ricevute WHERE id_sottosezione=?";                                                          

$sqlselect_ricevute = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                                  ricevute.id,
                                                  ricevute.causale,
                                                  ricevute.importo,
                                                  SUBSTR(DATE_FORMAT(ricevute.data_ricevuta,'" . $date_format . "'),1,10) data_ricevuta,
                                                  LPAD(n_ricevuta, 5, '0') n_ricevuta,
                                                  ricevute.data_ricevuta d_ord,
                                                  tipo_pagamento.descrizione despag,
                                                  ricevute.utente,
                                                  DATE_FORMAT(ricevute.data, '" . $date_format . "') data,
                                                  ricevute.id_attpell,
                                                  ricevute.tipo
                                    FROM    anagrafica,
                                                  ricevute
                                    LEFT JOIN tipo_pagamento
                                    ON        ricevute.id_pagamento = tipo_pagamento.id
                                    WHERE  anagrafica.id = ricevute.id_socio
                                    AND      ricevute.id_sottosezione =?
                                    AND      YEAR(data_ricevuta) = ?
                                    AND      lower(CONCAT(cognome, ' ', nome)) like lower(?)
                                    AND      ricevute.n_ricevuta BETWEEN ? AND ?
                                    ORDER BY 6 DESC LIMIT 0,?";
// Tipo pagamento
$sqlselect_tipopag = "SELECT id, descrizione
                                    FROM   tipo_pagamento
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

  if ($_POST) { // se post allora modificato parametri di input
      $kv = array();
      foreach ($_POST as $key => $value) {
                    $kv[] = "$key=$value";
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                     switch($key) {
      		                     case "id": // ID riga
      					                    $sqlID = $value;
      					                    break;

      		                     case "id_sottosezione": // sottosezione
      					                    $sqlid_sottosezione = $value;
      					                    break;

      		                     case "anno": // anno di competenza
      					                    $sqlanno_selected = $value;
      					                    break;

      		                     case "nrec": // numero righe da visualizzare
      					                    $nrec = $value;
      					                    break;

      		                     case "filterRic": // Filtro per intestatario ricevuta
      					                    $filterRic = $value . '%';
      					                    break;

      		                     case "filterRicNum": // Filtro per numero ricevuta
      		                              if($value > 0) {
      					                        $filterRicDal = $value;
      					                        $filterRicAl = $value;
      					                    } 
      					                    break;
                    }
             $index++;
           } // End foreach
           
           if($sqlID > 0) { // Aggiornamento riga, carico i dati
               $update=true;
               $sql =  "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                          ricevute.causale,
                                          ricevute.importo,
                                          SUBSTR(DATE_FORMAT(ricevute.data_ricevuta,'" . $date_format . "'),1,10) data_ricevuta,
                                          LPAD(ricevute.n_ricevuta, 5, '0') n_ricevuta,
                                          ricevute.data_ricevuta d_ord,
                                          tipo_pagamento.id idpag,
                                          ricevute.utente,
                                          ricevute.id_attpell,
                                          ricevute.tipo,
                                          DATE_FORMAT(ricevute.data ,'" . $date_format . "') data,
                                          ricevute.utente
                             FROM    anagrafica,
                                           ricevute
                             LEFT JOIN tipo_pagamento
                             ON        ricevute.id_pagamento = tipo_pagamento.id
                             WHERE   anagrafica.id = ricevute.id_socio
                             AND       ricevute.id = $sqlID";
               if($debug)
                  echo "$fname: SQL = $sql";

               $result = $conn->query($sql);

               $row = $result->fetch_assoc();
               $sqlnome=$row["nome"];
               $sqlid_attpell=$row["id_attpell"];
               $sqltipo=$row["tipo"];
               $sqlcausale=$row["causale"];
               $sqlimporto=$row["importo"];
               $sqldata_ricevuta=$row["data_ricevuta"];
               $sqln_ricevuta=$row["n_ricevuta"];
               $sqlid_pagamento=$row["idpag"];
               $sqldata=$row["data"];
               $sqlutente=$row["utente"];
              }
     }
 
 $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sqlid_sottosezione);
  disegna_menu();
  echo "<div class='background' style='position: absolute; top: 100px; overflow-x:auto;'>";

// verifica autorizzazioni  
  if($ctrAuth == 0) { // Utente non abilitato
     echo "<h2>Utente non abilitato alla funzione richiesta</h2>";
     echo "</div>";
     echo "</body>";
     echo "</html>";
     return;
     }

  $stmt = $conn->prepare($sqlselectanno_ricevuta); // Minimo anno delle ricevute emesse
  
  $stmt->bind_param("i", $sqlid_sottosezione);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlanno_min);
  $stmt->fetch();
  $stmt->close();
 
   if(!$sqlanno_min)
      $sqlanno_min = date('Y');
 
  $conn->prepare($sqlselect_ricevute);
  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='nrec' value=$nrec>";
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Ricevute emesse";
  if($update) {
      echo " ($sqln_ricevuta &minus;&gt; $sqldata_ricevuta)";
      }
  echo "</td></tr>";

// Sottosezione
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
 
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' required onChange='this.form.submit();'>";
      echo "<option value=''>--- Seleziona la sottosezione ---</option>";
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

// Anno di riferimento
  echo "<tr>";
  echo "<td><p class='required'>Anno di riferimento</p></td>";
  echo "<td><select class='required' name='anno' required onChange='this.form.submit();'>" ;
  $ctr=$sqlanno;
  while($ctr >= $sqlanno_min) {
  	         echo "<option value=" . $ctr;
  	         if($ctr == $sqlanno_selected)
  	             echo " selected";
  	          echo ">" . $ctr . "</option>";
  	         $ctr--;
             } 	
  echo "</select></td>";
  echo "</tr>";
  echo "</form>";

// Se aggiorno visualizzo i dati modificabili

if($update) {
	 echo "<form action='../php/update_sql.php' method='POST'>";
  	 echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
    echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
    echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";
    echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";

    echo "<tr>";
    echo "<td><p class='required'>Nominativo</p></td>";
    echo "<td><p class='required'>" . htmlentities($sqlnome,$defCharsetFlags, $defCharset) . "</p></td>";
    echo "</tr>";

// Attivita'/Viaggio
    echo "<tr>";
    echo "<td><p class='required'>Attivit&agrave;/Pellegrinaggio</p></td>";
    echo "<td><p class='required'>";
    
    switch($sqltipo) {
	       	         case 'A': // Attivita'
	       	                      $sql = "SELECT attivita.descrizione
	       	                                   FROM   attivita,
	       	                                               attivita_detail,
	       	                                               attivita_m
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'A'
	       	                                   AND     attivita_m.id = attivita_detail.id_attpell
	       	                                   AND     attivita_m.id_attivita = attivita.id";
	       	                                   break;

	       	         case 'V': // Viaggio/Pellegrinaggio
	       	                      $sql = "SELECT descrizione_pellegrinaggio.descrizione
	       	                                   FROM   pellegrinaggi,
	       	                                               attivita_detail,
	       	                                               descrizione_pellegrinaggio
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'V'
	       	                                   AND     pellegrinaggi.id = attivita_detail.id_attpell
	       	                                   AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";
	       	                                   break;
	       	        }
	 $sql_desattivita = "Nessuna associazione ($sqlid_attpell)";
	          
	 if($debug)
	      echo "$fname: SQL Attivit&agrave;/Viaggio = $sql<br>";
	 $r = $conn->query($sql);
	 if($r->num_rows > 0) {
	     $r1 = $r->fetch_assoc();
	     $sql_desattivita = $r1["descrizione"];
	    }
	 $r->close();
    echo htmlentities($sql_desattivita,$defCharsetFlags, $defCharset) . "</p></td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><p class='required'>Causale</p></td>";
    echo "<td><input name='causale' class='required' value='" . htmlentities($sqlcausale,$defCharsetFlags, $defCharset) . "' size=50 required/></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><p class='required'>Importo</p></td>";
    echo "<td><input type='number' class='numeror' name='importo' value=$sqlimporto step='0.01' min='0' max='10000.99'>&nbsp;&euro;</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td><p class='required'>Tipo di pagamento</p></td>";
    echo "<td><select class='required' name='id_pagamento'>";
    $result = $conn->query($sqlselect_tipopag);
    while($row = $result->fetch_assoc()) {
    	        echo "<option value=" . $row["id"];
    	        if($row["id"] == $sqlid_pagamento)
    	           echo " selected";
    	        echo ">" . htmlentities($row["descrizione"],$defCharsetFlags, $defCharset) . "</option>";
            } 
    echo "</select></td>";
    echo "</tr>"; 
  
    echo "<tr>";
    echo "<td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqldata . " Utente " . $sqlutente.")</td>";
    echo "</tr>";
  
    echo "<tr>";
    echo "<td colspan='2'><hr></td>";
    echo "</tr>";


     echo "<tr>";
     echo "<td colspan='2'><table style='width: 100%;'><tr>";

     if($authMask["update"]) {
          echo  "<td class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'></td>";
         }     
     echo "</form>";

     if($authMask["delete"]) {
         echo "<form action=\"../php/delete_sql.php\" method=\"post\">";
         echo "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirect . "\">";
         echo "<input type=\"hidden\" name=\"table_name\" value=\"" . $table_name . "\">";
         echo "<input type=\"hidden\" name=\"id\" value=\"" . $sqlID. "\">";
         echo "<td class='button'><input class='md_btn' type='submit' value='Elimina' onclick=\"{return conferma('". ritorna_js("Cancello la ricevuta ?") ."');}\"></td>";
         echo "</form>";
   	     } 
   	   echo "</tr></table></td></tr>";
   
  }

  echo "</table>";
  
  // Elenco ultime 'n' ricevute
  $index=0;
  $stmt = $conn->prepare($sqlselect_ricevute);
  $stmt->bind_param("iisiii", $sqlid_sottosezione, $sqlanno_selected,
                                   $filterRic, $filterRicDal, $filterRicAl, $nrec);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($sqlnome,
                                  $sqlid_ricevuta,
                                  $sqlcausale,
                                  $sqlimporto,
                                  $sqldata_ricevuta,
                                  $sqln_ricevuta,
                                  $dummy,
                                  $sqldes_pag,
                                  $sqlutente,
                                  $datainsert,
                                  $sqlid_attpell,
                                  $sqltipo);

  while($stmt->fetch()) {
  	         if($index==0) { // Intestazione
  	             echo "<br>";
  	             echo "<form action='" . $redirect . "' method='POST'>";
  	             echo "<input type='hidden' name='id' value=$sqlID>";
  	             echo "<input type='hidden' name='anno' value=$sqlanno_selected>";
  	             echo "<table style='background-color: white;opacity: 0.8;'>";
  	             echo "<tr>";
  	             echo "<th colspan='8' style='font: bold 14px Arial, Helvetica, sans-serif;'>Visualizzo ultime ";
  	             echo "<select class='search' name='nrec' onChange='this.form.submit();'>";
  	             echo "<option value=20";
  	             
  	             if(20 == $nrec)
  	                echo " selected";
  	             echo ">20</option>";

  	             echo "<option value=40";
  	             
  	             if(40 == $nrec)
  	                echo " selected";
  	             echo ">40</option>";

  	             echo "<option value=60";
  	             
  	             if(60 == $nrec)
  	                echo " selected";
  	             echo ">60</option>";

  	             echo "<option value=80";
  	             
  	             if(80 == $nrec)
  	                echo " selected";
  	             echo ">80</option>";

  	             echo "<option value=100";
  	             
  	             if(100 == $nrec)
  	                echo " selected";
  	             echo ">100</option>";

  	             echo "<option value=120";
  	             
  	             if(120 == $nrec)
  	                echo " selected";
  	             echo ">120</option>";

  	             echo "<option value=140";
  	             
  	             if(140 == $nrec)
  	                echo " selected";
  	             echo ">140</option>";

  	             echo "<option value=200";
  	             
  	             if(200 == $nrec)
  	                echo " selected";
  	             echo ">200</option>";

  	             echo "</select> righe";
  	             echo "; oppure filtra per numero o intestatario ricevuta";
  	             echo "&nbsp;<input class='search' type='number' min='1' max='99999' name='filterRicNum' maxlength=5 autocomplete='off' placeholder='Num.'>";
  	             echo "&nbsp;<input class='search' name='filterRic' maxlength=60 size=50 autocomplete='off'  placeholder='Intestatario ricevuta'>";
  	             echo "<input type='submit' value='Cerca'>";
  	             echo "</th>";
  	             echo "</form>";
  	             echo "</tr>";
  	             
  	             echo "<tr>";	             
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>#</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Nominativo</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Attivit&agrave;/Pellegrinaggio</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Causale</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Importo</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Aggiornato da</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data/ora</th>";
  	             echo "</tr>";
  	         } // Fine intestazione
  	         $index++;
  	         
  	         echo "<form id='updRicevuta_$index' action='" . $redirect . "' method='POST'>";
  	         echo "<input type='hidden' name='id_sottosezione' value=$sqlid_sottosezione>";
  	         echo "<input type='hidden' name='anno' value=$sqlanno_selected>";
  	         echo "<input type='hidden' name='id' value=" . $sqlid_ricevuta. ">";
            echo "<input type='hidden' name='nrec' value=$nrec>";
  	         echo "<tr>";
  	         echo "<td style='font: bold italic 11px Arial, Helvetica, sans-serif;vertical-align: top; white-space: nowrap;'>";
  	         echo "<a href='#' onClick=\"document.getElementById('updRicevuta_$index').submit();\">";
  	         echo $sqln_ricevuta;
  	         echo "</a></td>";
  	         echo "</form>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif;text-align: center; vertical-align: top;'>";
  	         echo $sqldata_ricevuta;
  	         echo "</td>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($sqlnome, $defCharsetFlags, $defCharset);
  	         echo "</td>";

// Descrizione attivita'/Pellegrinaggio

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";

            switch($sqltipo) {
	       	         case 'A': // Attivita'
	       	                      $sql = "SELECT attivita.descrizione
	       	                                   FROM   attivita,
	       	                                               attivita_detail,
	       	                                               attivita_m
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'A'
	       	                                   AND     attivita_m.id = attivita_detail.id_attpell
	       	                                   AND     attivita_m.id_attivita = attivita.id";
	       	                                   break;

	       	         case 'V': // Viaggio/Pellegrinaggio
	       	                      $sql = "SELECT descrizione_pellegrinaggio.descrizione
	       	                                   FROM   pellegrinaggi,
	       	                                               attivita_detail,
	       	                                               descrizione_pellegrinaggio
	       	                                   WHERE attivita_detail.id_attpell = $sqlid_attpell
	       	                                   AND     attivita_detail.tipo = 'V'
	       	                                   AND     pellegrinaggi.id = attivita_detail.id_attpell
	       	                                   AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";
	       	                                   break;
	       	        } // End switch
	         $sql_desattivita = "Nessuna associazione ($sqlid_attpell)";
	          
	         if($debug)
	            echo "$fname: SQL Attivit&agrave;/Viaggio = $sql<br>";
	         $r = $conn->query($sql);
	         if($r->num_rows > 0) {
	            $r1 = $r->fetch_assoc();
	            $sql_desattivita = $r1["descrizione"];
	           }
	         $r->close();
            echo htmlentities($sql_desattivita,$defCharsetFlags, $defCharset) . "</td>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif;text-align: left;vertical-align: top;'>";
  	         echo htmlentities($sqlcausale, $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 12px Arial, Helvetica, sans-serif;text-align: right;vertical-align: top;'>";
  	         echo money_format('%(!n',$sqlimporto) . "&nbsp;&euro;";
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($sqlutente, $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: center;vertical-align: top;'>";
  	         echo $datainsert;
  	         echo "</td>";
  	         echo "</tr>";

       	}
       	
  if($index > 0)
     echo "</table>";
  else 
     echo "<h1>Nessun dato trovato coi parametri di ricerca</h1>";
     
  echo "<a href='../php/gestione_ricevute.php'><input type='button' value='Indietro'></a>";
  echo "</div>";

$conn->close();

?>
</body>
</html>