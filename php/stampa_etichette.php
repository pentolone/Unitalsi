<?php
/****************************************************************************************************
*
*  Stampa le etichette dei partecipanti al viaggio/pellegrinaggio
*
*  @file stampa_etichette.php
*  @abstract Genera file PDF con le etichette
*  @author Luca Romano
*  @version 1.0
*  @since 2017-08-02
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');

define("MYPDF_MARGIN_HEADER",0);
define("MYPDF_MARGIN_FOOTER",0);
define("MYPDF_MARGIN_TOP",5);
define("MYPDF_MARGIN_LEFT",0);
define("MYPDF_MARGIN_RIGHT",0);
define("MYPDF_MARGIN_BOTTOM",2);

define('MYPDF_PAGE_ORIENTATION', 'P'); // Portrait (A4)

define("N_ETICHETTE", 3); // Numero di etichette per socio
define("LABEL_HEIGHT", 48); // Altezza etichette
define("LABEL_PADDING_TOP", 0); // Padding etichetta TOP
define("LABEL_PADDING_BOTTOM", 0); // Padding etichetta BOTTOM
define("LABEL_PADDING_LEFT", 4); // Padding etichetta LEFT
define("LABEL_PADDING_RIGHT", 0); // Padding etichetta RIGHT
define("LINE_PADDING", 3); // Left and right padding for line
$debug=false;
$fname=basename(__FILE__);

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();

$descrizione=null;

$sqlid_sottosezione=0;
$sqlid_attpell=0;
$sqlanno=0;
$sqlid_socio=0;
$sqltipo='-';
$sqlExec='';
$sqldatacheck=null;
$sqlsex='T';
$sqlid_categoria=0;
$descat=null;

$sqlold_s=0;
$sqlold_p=0;
$fromSearch=false; // Verifica se richiamato da ricerca dati
$totalone=0;
$valueToprint='';
$i=0;
$ctrViaggio=0; // Totale partecipanti per viaggio
$sqlSearch='';
$table_name='unknown';

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$index=0;
$startRow=1;
$html='';
// Check sessione
if(!check_key())
   return;

$userid = session_check();

// Database connect
$conn = DB_connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if(!$_POST) { // se NO post allora chiamata errata
   echo "Chiamata alla funzione errata";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {
                  $kv[] = "$key=$value";
      
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}

                   switch($key) {

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                       case "id_prn": // id attivita' / pellegrinaggio
      					                      $sqlid_attpell= $value;
      					                       break;

      		                      case "anno": // anno riferimento
      					                    $sqlanno = $value;
      				                        break;

      		                      case "riga": // riga da cui far partire la stampa
      					                    $startRow = $value;
      				                        break;

      		                      case "id_socio": // Eventuale socio per cui ristampare le etichette
      					                    $sqlid_socio = $value;
      				                        break;
      	
      		                       default: // OPS!
      				                        echo "UNKNOWN key = ".$key;
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
	
}
    
// SQL per anagrafica dei partecipanti
$sql_ana = "SELECT DISTINCT anagrafica.id, CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                                 tipo_viaggio,
                                 gruppo_parrocchiale.descrizione des_gruppo
                    FROM    anagrafica,
                                 attivita_detail,
                                 gruppo_parrocchiale
                     WHERE anagrafica.id = attivita_detail.id_socio
                     AND     attivita_detail.id_attpell = $sqlid_attpell
                     AND     anagrafica.id_gruppo_par = gruppo_parrocchiale.id
                     AND     attivita_detail.tipo_viaggio >= 0";

if($sqlid_socio > 0) {
	$sql_ana .= " AND attivita_detail.id_socio = $sqlid_socio";
   }
$sql_ana .= " ORDER BY 2";

if($debug)
    echo "$fname: SQL Anagrafica $sql_ana<br>";          
            
// create new PDF document
$pdf = new TCPDF(MYPDF_PAGE_ORIENTATION);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Etichette viaggio/pellegrinaggio (pronto per la stampa)');
$pdf->SetSubject('Anagrafica, Etichette, Viaggio');
$pdf->SetKeywords('Anagrafica, Etichette, Viaggio');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(MYPDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, MYPDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(MYPDF_MARGIN_HEADER);
$pdf->SetFooterMargin(MYPDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, MYPDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set padding
$pdf->setCellPaddings(LABEL_PADDING_LEFT, LABEL_PADDING_TOP, LABEL_PADDING_RIGHT, LABEL_PADDING_BOTTOM);


// Set font to helvetica
$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');


$sql = "SELECT descrizione_pellegrinaggio.descrizione
             FROM   descrizione_pellegrinaggio,
                          pellegrinaggi
             WHERE  pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND      pellegrinaggi.id = $sqlid_attpell"; 

// Accedo al DB (per descrizione viaggio/pellegrinaggio)
if($debug)
    echo "$fname: SQL Pellegrinaggio $sql<br>";          
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$descrizione = $row["descrizione"]; // Descrizione Viaggio/Pellegrinaggio

// Accedo al DB (ciclo per le anagrafiche)
if($debug)
    echo "$fname: SQL Anagrafica $sql_ana<br>";          
        
// Accedo al DB (ciclo per le anagrafiche con tipo viaggio > 0)
$result = $conn->query($sql_ana);

if($result->num_rows > 0) // Add Page
    $pdf->AddPage();

$pageWidth = round($pdf->getPageWidth()); // Get the width of the page
$labelWidth = round($pageWidth / N_ETICHETTE);

if($debug) {
	echo "$fname: Page Width = $pageWidth<br>";
	echo "$fname: Label Width = $labelWidth<br>";
   }

while($row = $result->fetch_assoc()) {	
          // Seleziono mezzo assegnato
          $mezzo='Mezzo non assegnato';
          $sql = "SELECT mezzi_disponibili.descrizione
                       FROM   mezzi_disponibili,
                                    mezzi_detail
                       WHERE  mezzi_detail.id_socio = " . $row["id"] .
                     " AND      mezzi_detail.id_attpell = $sqlid_attpell
                       AND      mezzi_detail.id_mezzo = mezzi_disponibili.id";
           // Accedo al DB (descrizione mezzo)
           if($debug)
               echo "$fname: SQL Mezzo $sql<br>";          

           $rs1 = $conn->query($sql);

            if($rs1->num_rows > 0) {// Get descrizione mezzo
               $rw1 = $rs1->fetch_assoc();
               $mezzo = $rw1["descrizione"];
              }
              
           switch($row["tipo_viaggio"]) {
           	          case TO:
           	                        $mezzo .= " A";
           	                        break;
           	          case FROM:
           	                        $mezzo .= " R";
           	                        break;
           	          case ROUNDTRIP:
           	                        $mezzo .= " A/R";
           	                        break;
           	         }
 
          $rs1->close();
          // Seleziono Camera assegnata
          $camera='Non Assegnata';
          $piano='Non Assegnato';
          $sql = "SELECT AL_camere.codice,
                                    AL_piani.descrizione
                       FROM   AL_camere,
                                    AL_piani,
                                    AL_occupazione
                       WHERE  AL_occupazione.id_socio = " . $row["id"] .
                     " AND      AL_occupazione.id_attpell = $sqlid_attpell
                       AND      AL_occupazione.id_camera = AL_camere.id
                       AND      AL_camere.id_piano = AL_piani.id";
           // Accedo al DB (descrizione piano/camera)
           if($debug)
               echo "$fname: SQL Camera $sql<br>";          

           $rs1 = $conn->query($sql);

            if($rs1->num_rows > 0) {// Get descrizione mezzo
               $rw1 = $rs1->fetch_assoc();
               $piano = $rw1["descrizione"];
               $camera = $rw1["codice"];
              }

          // Mi posiziono sul foglio in base al numero di riga da cui iniziare la stampa
          if($startRow > 1) {
              $pdf->setY($pdf->getY() + ($startRow -1)*LABEL_HEIGHT);
              $startRow = 1; // Reset startRow; solo la prima volta
             }
	       // Stampo N_ETICHETTE  per  socio
          // Set font to helvetica Bold 12px
          $pdf->SetFont ('helveticaB', '', '12px' , '', 'default', true );
	       for($index = 1; $index < N_ETICHETTE; $index++) {
	       	    
	       	   // Stampo mezzo
                $pdf->Cell($labelWidth, 0, $mezzo, 0, 0, 'C');                
	             $y = $pdf->getY();     
	       	   } // END for
          $pdf->Cell($labelWidth, 0, $mezzo, 0, 1, 'C'); 

	       // Stampo Linea
	       for($index = 0; $index < N_ETICHETTE; $index++) {
                $pdf->Line($pdf->getX()+LINE_PADDING, $pdf->getY(), ($pdf->getX() + $labelWidth-LINE_PADDING), $pdf->getY());
                $pdf->setX($pdf->getX() + $labelWidth);     	
	       	   } // END for

          $pdf->setX(0);
          $pdf->setY($pdf->getY()+5);
          // Set font to helvetica 10px Bold
          $pdf->SetFont ('helveticaB', '', '10px' , '', 'default', true );

	       // Stampo Descrizione viaggio/pellegrinaggio
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->Cell($labelWidth, 0, $descrizione, 0, 0, 'L');
       //$this->Cell(0, 0, $this->subintestazione, 0, false, 'C', 0, '', 0, false, 'M', 'M');
	       	   } // END for
          $pdf->Cell($labelWidth, 0, $descrizione, 0, 1, 'L'); 

          // Set font to helvetica 10px
          $pdf->SetFont ('helvetica', '', '10px' , '', 'default', true );
	       // Stampo Descrizione Gruppo
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->Cell($labelWidth, 0, $row["des_gruppo"], 0, 0, 'L'); 
	       	   } // END for
           $pdf->Cell($labelWidth, 0, $row["des_gruppo"], 0, 1, 'L'); 

          // Set font to helvetica 11px Bold
          $pdf->SetFont ('helveticaB', '', '11px' , '', 'default', true );
	       // Stampo Nome socio
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->Cell($labelWidth, 0, substr($row["nome"],0, 24), 0, 0, 'L'); 
	       	   } // END for
           $pdf->Cell($labelWidth, 0, substr($row["nome"],0, 24), 0, 1, 'L'); 

          // Set font to helvetica 10px
          $pdf->SetFont ('helvetica', '', '10px' , '', 'default', true );
	       // Stampo Permanenza
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->Cell($labelWidth, 0, "Dal " . $row["dal"] . " Al " . $row["al"], 0, 0, 'L'); 
	       	   } // END for
           $pdf->Cell($labelWidth, 0, "Dal " . $row["dal"] . " Al " . $row["al"], 0, 1, 'L'); 

	       // Stampo Linea
	       for($index = 0; $index < N_ETICHETTE; $index++) {
                $pdf->Line($pdf->getX()+LINE_PADDING, $pdf->getY(), ($pdf->getX() + $labelWidth-LINE_PADDING), $pdf->getY());
                $pdf->setX($pdf->getX() + $labelWidth);     	
	       	   } // END for
          $pdf->setX(0);
          $pdf->setY($pdf->getY()+2);

          // Set font to helvetica 11px Bold
          $pdf->SetFont ('helveticaB', '', '11px' , '', 'default', true );
	       // Stampo Piano
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->Cell($labelWidth, 0, "Albergo " . $piano, 0, 0, 'L'); 
	       	   } // END for
           $pdf->Cell($labelWidth, 0, "Albergo " . $piano, 0, 1, 'L'); 
           
	       // Stampo Camera
	       for($index = 1; $index < N_ETICHETTE; $index++) {
                $pdf->SetFont ('helveticaB', '', '11px' , '', 'default', true );
                $pdf->Cell(17, 0, "Camera ", 0, 0, 'L');
                $pdf->SetFont ('helveticaB', '', '15px' , '', 'default', true );
                $pdf->Cell($labelWidth - 17, 0, $camera, 0, 0, 'L');
	       	   } // END for
          $pdf->SetFont ('helveticaB', '', '11px' , '', 'default', true );
          $pdf->Cell(17, 0, "Camera ", 0, 0, 'L'); 
          $pdf->SetFont ('helveticaB', '', '15px' , '', 'default', true );
          $pdf->Cell($labelWidth - 17, 0, $camera, 0, 1, 'L');
           
//          $pdf->setX(0);
           $pdf->setY($y + LABEL_HEIGHT);
//           $pdf->Cell($labelWidth, 0, $mezzo, 0, 0, 'C');
	      } // END while
	       	   
$pdf->Output('Stampa_etichette.pdf', 'I');
$conn->close();
 
//============================================================+
// END OF FILE
//============================================================+