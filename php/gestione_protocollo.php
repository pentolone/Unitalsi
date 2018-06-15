<?php
require_once('../php/unitalsi_include_common.php');
if(!check_key())
   return;
?>
<html>
<head>
  <title>Gestione protocollo</title>
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
*  Gestione della protocollo (entrata/uscita)
*
*  @file gestione_protocollo.php
*  @abstract Gestisce la movimentazione del protocollo
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
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 
$sott_app = ritorna_sottosezione_pertinenza();
$multisottosezione = ritorna_multisottosezione();
$fname=basename(__FILE__);
$date_format=ritorna_data_locale();

$index=0;
$debug=false;
$update=false;
$nrec=20; // numero di record da visualizzare
$filterProt='%'; // filtro per numero protocollo
$table_name="protocollo";
$redirect="../php/gestione_protocollo.php";

// Variabili da tabella
$sqlID=0;
$sqltipo='';
$sqlcodice;
$sqldata_invio=date('Y-m-d');
$sqldata_arrivo=date('Y-m-d');
$sqln_lettera=null;
$sqlmittente=null;
$sqldestinatario=null;
$sqloggetto=null;
$sqlconsegnato=null;
$sqlreferente=null;
$sqlnote=null;
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

$sqlselect_protocollo = "SELECT id, tipo, codice,
                                                     SUBSTR(DATE_FORMAT(data_invio, '" . $date_format . "'),1,10) data_invio,
                                                     SUBSTR(DATE_FORMAT(data_arrivo, '" . $date_format . "'),1,10) data_arrivo,
                                                     n_lettera, mittente, destinatario, oggetto, consegnato, referente,
                                                     note,
                                                     DATE_FORMAT(data, '" . $date_format . "') data,
                                                     utente
                                        FROM    protocollo";

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

      		                     case "tipo": // tipo protocollo
      					                    $sqltipo = $value;
      					                    break;

      		                     case "nrec": // numero righe da visualizzare
      					                    $nrec = $value;
      					                    break;

      		                     case "filterProt": // Filtro per protocollo
      					                    $filterProt = $value . '%';
      					                    break;
                    }
             $index++;
           } // End foreach
           
           if($sqlID > 0) { // Aggiornamento riga, carico i dati
               $update=true;
               $sql =  "SELECT id, tipo, codice,
                                          data_invio, data_arrivo,
                                          n_lettera, mittente, destinatario, oggetto, consegnato, referente,
                                          note,
                                          DATE_FORMAT(data, '" . $date_format . "') data,
                                          utente
                             FROM    protocollo
                             WHERE  id = $sqlID";
               if($debug)
                  echo "$fname: SQL = $sql";

               $result = $conn->query($sql);

               $row = $result->fetch_assoc();
               $sqltipo=$row["tipo"];
               $sqlcodice=$row["codice"];
               $sqldata_invio=$row["data_invio"];
               $sqldata_arrivo=$row["data_arrivo"];
               $sqln_lettera=$row["n_lettera"];
               $sqlmittente=$row["mittente"];
               $sqldestinatario=$row["destinatario"];
               $sqloggetto=$row["oggetto"];
               $sqlconsegnato=$row["consegnato"];
               $sqlreferente=$row["referente"];
               $sqlnote=$row["note"];
               $sqldata=$row["data"];
               $sqlutente=$row["utente"];
              }
     }
     
  $sqlselect_protocollo .= " WHERE id_sottosezione = $sqlid_sottosezione
                                           AND codice LIKE '$filterProt'
                                           ORDER BY 1 DESC LIMIT 0, $nrec";

 $desc_sottosezione = ritorna_sottosezione_pertinenza_des($conn, $sott_app);
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

  echo "<form name='searchTxt' action='" . $redirect . "' method='POST'>";
  echo "<input type='hidden' name='nrec' value=$nrec>";
  echo "<table>";
  echo "<tr>";
  echo "<td colspan='2' class='titolo'>Gestione Protocollo";
  if($update) {
      echo " ($sqlcodice &minus;&gt; ";
      
      if($sqltipo == 'IN')
         echo "Entrata";
      else 
         echo "Uscita";
      echo ")</td>";
      }
  echo "</tr>";

// Sottosezione
  echo "<tr>";
  echo "<td><p class='required'>Sottosezione</p></td>";
 
  if(!$multisottosezione || $update) {
  	   echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
      echo "<td><p class='required'>" .  htmlentities($desc_sottosezione, $defCharsetFlags, $defCharset) ."</p></td>";
     }
  else { 
      echo "<td><select class='required' name='id_sottosezione' required>" ;
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

// Tipo (solo in fase di inserimento)

if(!$update) {
  echo "<tr>";
  echo "<td><p class='required'>Seleziona Tipo</p></td>";
  echo "<td><select class='required'  required name='tipo' onChange='this.form.submit();'>";
  echo "<option value=>--- Seleziona tipo di protocollo ---</option>";
  echo "<option value='IN'";
  
  if($sqltipo == 'IN')
     echo " selected";

  echo ">Entrata</option>";

  echo "<option value='OUT'";
  if($sqltipo == 'OUT')
     echo " selected";

  echo ">Uscita</option>";

  echo "</select></td>";
  echo "</tr>";
  }
  
  echo "</form>";

  if($sqltipo != "") {
  	
  	  if(!$update) {
  	      echo "<form action='../php/insert_protocollo.php' method='POST'>";
  	    }
  	    
  	  else {
  	      echo "<form action='../php/update_sql.php' method='POST'>";
  	  	   echo "<input type='hidden' name='id' value='" . $sqlID . "'>";
  	  	 }
    
     echo "<input type='hidden' name='redirect' value='" . $redirect . "'>";
     echo "<input type='hidden' name='table_name' value='" . $table_name . "'>";
     echo "<input type='hidden' name='id_sottosezione' value=" . $sqlid_sottosezione . ">";
     echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
  
     echo "<tr>";
     echo "<td colspan='2'><hr></td>";
     echo "</tr>";

     switch($sqltipo) { // Disegno i campi di input dipendenti dal tipo (IN/OUT)
                case 'IN': // In entrata
                              echo "<tr>";
                              echo "<td><p class='required'>Data lettera</td>";
                              echo "<td><input name='data_invio' type='date' class='required' value='" . $sqldata_invio . "'required></p></td>";
                              echo "</tr>";

                              echo "<tr>";
                              echo "<td><p class='required'>Numero lettera</td>";
                              echo "<td><input name='n_lettera' maxlength='10' size='8' class='required'
                                        value='" . htmlentities($sqln_lettera, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              

                              echo "<tr>";
                              echo "<td><p class='required'>Data arrivo</td>";
                              echo "<td><input name='data_arrivo' type='date' class='required' value='" . $sqldata_arrivo . "' required></p></td>";
                              echo "</tr>";

                              echo "<tr>";
                              echo "<td><p class='required'>Mittente</td>";
                              echo "<td><input name='mittente' maxlength='100' size='70' class='required' 
                                       value='" . htmlentities($sqlmittente, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              

                              echo "<tr>";
                              echo "<td><p class='required'>Oggetto</td>";
                              echo "<td><input name='oggetto' maxlength='100' size='70' class='required' 
                                        value='" . htmlentities($sqloggetto, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              

                              echo "<tr>";
                              echo "<td><p class='required'>Consegnato con</td>";
                              echo "<td><input name='consegnato' maxlength='100' size='70' class='required'
                                        value='" . htmlentities($sqlconsegnato, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              
                              break;

                case 'OUT': // In uscita                
                              echo "<tr>";
                              echo "<td><p class='required'>Data lettera</td>";
                              echo "<td><input name='data_invio' type='date' class='required' value='" . $sqldata_invio . "' required></p></td>";
                              echo "</tr>";

                              echo "<tr>";
                              echo "<td><p class='required'>Destinatario</td>";
                              echo "<td><input name='destinatario' maxlength='100' size='70' class='required'
                                       value='" . htmlentities($sqldestinatario, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              

                              echo "<tr>";
                              echo "<td><p class='required'>Oggetto</td>";
                              echo "<td><input name='oggetto' maxlength='100' size='70' class='required'
                                       value='" . htmlentities($sqloggetto, $defCharsetFlags, $defCharset) . "' required></p></td>";
                              echo "</tr>";                              
                              break;             
              } // End switch

// Dati comuni a entrambe le tipologie di protocollo

      echo "<tr>";
      echo "<td><p>Referente</p></td>";
      echo "<td><input name='referente' maxlength='100' size='70'
               value='" . htmlentities($sqlreferente, $defCharsetFlags, $defCharset) . "' ></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td><p>Note</p></td>";
      echo "<td><p><textarea name='note' maxlength='300'>" .  htmlentities($sqlnote, $defCharsetFlags, $defCharset) . "</textarea></p></td>";
      echo "</tr>";
  
      echo "<tr>";
      echo "<td colspan='2'><hr></td>";
      echo "</tr>";

      echo "<tr>";
      
      if(!$update) {
          if($authMask["insert"]) {
              echo  "<td colspan='2' class='button'><input class='in_btn' id='btn' type='submit' value='Inserisci'></td>";
           }
       }
      else {
          echo "<td class='tb_upd' colspan='2'>(Ultimo aggiornamento " .$sqldata . " Utente " . $sqlutente.")</td>";
          echo "</tr>";
    
          echo "<tr>";

          if($authMask["update"]) {
              echo  "<td colspan='2' class='button'><input class='md_btn' id='btn' type='submit' value='Aggiorna'></td>";
             }     
      }
      echo "</tr>";
      echo "</form>";
  } // Fine creazione form

  echo "</table>";
  
  // Elenco ultimi 10 protocolli
  $index=0;
  $result = $conn->query($sqlselect_protocollo);

  while($row = $result->fetch_assoc()) {
  	         if($index==0) { // Intestazione
  	             echo "<br>";
  	             echo "<form action='" . $redirect . "' method='POST'>";
  	             echo "<input type='hidden' name='tipo' value='" . $sqltipo . "'>";
  	             echo "<input type='hidden' name='id' value=$sqlID>";
  	             echo "<table style='background-color: white;opacity: 0.8;'>";
  	             echo "<tr>";
  	             echo "<th colspan='12' style='font: bold 14px Arial, Helvetica, sans-serif;'>Visualizzo ultime ";
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
  	             echo "; oppure filtra per numero protocollo";
  	             echo "&nbsp;<input class='search' name='filterProt' maxlength=9 size=10>";
  	             echo "<input type='submit' value='Cerca'>";
  	             echo "</th>";
  	             echo "</form>";
  	             echo "</tr>";
  	             
  	             echo "<tr>";	             
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>#</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Tipo</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data lettera</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data ricezione</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Oggetto</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Mittente/Destinatario</th>";
//  	             echo "<th style='font: bold 12px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Destinatario</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'># lettera</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Consegnato con</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Referente</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Note</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Aggiornato da</th>";
  	             echo "<th style='font: bold 11px Arial, Helvetica, sans-serif;border-bottom: 1px; border-bottom-style: solid;'>Data/ora</th>";
  	             echo "</tr>";
  	         }
  	         $index++;
  	         
  	         echo "<form id='updProtocollo_$index' action='" . $redirect . "' method='POST'>";
  	         echo "<input type='hidden' name='id' value=" . $row["id"] . ">";
            echo "<input type='hidden' name='nrec' value=$nrec>";
  	         echo "<tr>";
  	         echo "<td style='font: bold italic 11px Arial, Helvetica, sans-serif;vertical-align: top; white-space: nowrap;'>";
  	         echo "<a href='#' onClick=\"document.getElementById('updProtocollo_$index').submit();\">";
  	         echo $row["codice"];
  	         echo "</a></td>";
  	         echo "</form>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif;vertical-align: top;'>";
  	         if($row["tipo"] == 'IN')
  	            echo "Entrata";
  	         else
  	            echo "Uscita";
  	         echo "</td>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif; text-align: center;vertical-align: top;'>";
  	         echo $row["data_invio"];
  	         echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif;text-align: center;vertical-align: top;'>";
  	         echo $row["data_arrivo"];
  	         echo "</td>";

  	         echo "<td style='font: italic 11px Arial, Helvetica, sans-serif;vertical-align: top; width: 150px;'>";
  	         echo htmlentities($row["oggetto"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif;vertical-align: top;'>";
  	         if($row["mittente"])
  	             echo htmlentities($row["mittente"], $defCharsetFlags, $defCharset);
  	         else
  	             echo htmlentities($row["destinatario"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	        // echo "<td style='font: italic 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	        // echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($row["n_lettera"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($row["consegnato"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif; text-align: left; vertical-align: top;'>";
  	         echo htmlentities($row["referente"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: italic 10px Arial, Helvetica, sans-serif; text-align: left; width: 150px;'>";
  	         echo htmlentities($row["note"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo htmlentities($row["utente"], $defCharsetFlags, $defCharset);
  	         echo "</td>";

  	         echo "<td style='font: bold 10px Arial, Helvetica, sans-serif; text-align: left;vertical-align: top;'>";
  	         echo $row["data"];
  	         echo "</td>";
  	         echo "</tr>";

       	}
       	
  if($index > 0)
     echo "</table>";
  else 
     echo "<h1>Nessun dato trovato col parametro di ricerca $filterProt</h1>";
  echo "</div>";

$conn->close();

?>
</body>
</html>
