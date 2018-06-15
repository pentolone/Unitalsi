<?php
/****************************************************************************************************
*
*  Stampa il foglio presenze dei partecipanti al viaggio/pellegrinaggio
*
*  @file stampa_presenze.php
*  @abstract Genera file PDF con i presenti nel viaggio selezionato in formato Landscape
*  @author Luca Romano
*  @version 1.0
*  @since 2017-10-02
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');

define('EURO',chr(128));
// Definisco i margini
define("MYPDF_MARGIN_TOP", 39);
define("MYPDF_MARGIN_LEFT", 10);
define("MYPDF_MARGIN_RIGHT", 10);
define('MYPDF_PAGE_ORIENTATION_LANDSCAPE', 'L');
define('MYPDF_PAGE_ORIENTATION_PORTRAIT', 'P');
setlocale(LC_MONETARY, 'it_IT');
$debug=false;
$fname=basename(__FILE__);

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione=array();
	protected $subPos=array();
	protected $addH;
   private $i=0;
	
	function __construct( $par, $subIntestazione, $subPosizione, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        $this->subintestazione = $subIntestazione;
        $this->subPos = $subPosizione;
        //...
    }
    public function setAdditionalH($additionalH)
    {
    	$this->addH = $additionalH;
    }
    // Page header
    public function Header() {
       $headerData = $this->getHeaderData();
       
       $this->SetX(8);
       $this->SetY(8);
       $this->Image('/images/Logo_UNITALSI.jpg', '', '', 20);
  
       $this->SetY(12);
       $this->SetFont('helvetica', 'B', '13px');
       $this->Cell(0, 15, $headerData['title'], 0, true, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetFont('helvetica', 'B', '10px');
       $this->Cell(0, 15, $headerData['string'], 0, false, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetY(25);
       $this->Cell(0, 15, $this->addH, 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
       
       $this->SetY(37);

       $this->SetFont('helvetica', 'BI', '12px');
       for($i=0;$i < count($this->subintestazione);$i++) {
             $this->Cell($this->subPos[$i], 0, $this->subintestazione[$i], 1, 0, 'C', 0, '', 0, false, 'B', 'B');
            }
        $this->Ln();
       //$this->Cell(20,0,'Ehii ',1,1,'L',0,'',0);
       // $this->SetY(35);
    }

    // Page footer
    public function Footer() {
    	  $foot = $this->par . ' Pagina ';
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', '', '6px');
        // Page number
        $this->Cell(0, 10, $foot . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Fine class TCPDF
$date_format=ritorna_data_locale();

$sott_app = ritorna_sottosezione_pertinenza();

$suddGroup=false; // Suddivisione per gruppo

$sqlid_sottosezione=0;
$sqlid_attpell=0;
$sqlanno=0;
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
$prn_format=MYPDF_PAGE_ORIENTATION_LANDSCAPE;
$totalizza=false;
$i=0;
$ctrViaggio=0; // Totale partecipanti per viaggio
$sqlSearch='';
$table_name='unknown';

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$txtF=''; // Footer
$pdfH='';
$index=0;
$html='';
// Check sessione
if(!check_key())
   return;

$userid = session_check();

// Campi intestazione
$subInt=array("Nominativo","Gruppo","Servizio");

// Lunghezza campi intestazione
$subPos=array(50, 50, 30);

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');
   
//$logo = '../' . $extraDir . $pathFile . $logo;

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
      		                      case "sqlsearch": // Stringa select ( da search))
      				                        $sqlSearch = $value;
      				                        $fromSearch=true;
                                        $txtF='Stampa risultati ricerca attività (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s'); // Footer
      				                        break;

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                       case "tipo": // 'A' = Attivita 'V' = Viaggio/Pellegrinaggio
      					                      $sqltipo= $value;
      					                       break;

      		                       case "id_prn": // id attivita' / pellegrinaggio
      					                      $sqlid_attpell= $value;
      					                       break;

      		                      case "anno": // anno riferimento
      					                    $sqlanno = $value;
      				                        break;

      		                      case "prn_format": // formato stampa
      					                    $prn_format = $value;
      				                        break;

      		                      case "searchTxt": // campo di ricerca (ignoring)
      				                        break;
      	
      		                       default: // OPS!
      				                        echo "UNKNOWN key = ".$key;
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
             $table_name = 'pellegrinaggi';
             $txtF = "Stampa foglio presenze (Rev 1.0) Generato il: " . date('d/m/Y H:i:s'); 
	
}

// SQL per sottosezione
$sqlsottosezione= "SELECT id, nome
                                 FROM   sottosezione
                                 WHERE id = $sqlid_sottosezione";
    
// SQL per anagrafica
$sqlV = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                        SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                        SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                                        SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                                        anagrafica.sesso,
                                        anagrafica.n_biancheria,
                                        gruppo_parrocchiale.descrizione des_par,
                                        gruppo_parrocchiale.id id_par,
                                        IFNULL(servizio.descrizione, 'Nessuno') des_ser,
                                        anagrafica.id ida,
                                        anagrafica.ts,
                                        SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) sdal,
                                        SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) sal,
                                        attivita_detail.id idd
                          FROM    anagrafica,
                          attivita_detail
                          LEFT JOIN servizio ON attivita_detail.id_servizio = servizio.id,
                          gruppo_parrocchiale
             WHERE anagrafica.id = attivita_detail.id_socio
             AND     attivita_detail.tipo = 'V'
             AND     anagrafica.id_gruppo_par = gruppo_parrocchiale.id
             AND     attivita_detail.id_attpell = $sqlid_attpell
             ORDER BY nome";

// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND sottosezione.id =  $sqlid_sottosezione";

// SQL per pellegrinaggio
$sql = "SELECT  CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                           SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                           SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al,
                           dal dal_c,
                           al al_c
             FROM     pellegrinaggi,
                           descrizione_pellegrinaggio
             WHERE   pellegrinaggi.id = $sqlid_attpell
             AND       pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";

// SQL per numero di partecipanti divisi per provincia
$sqlProv = "SELECT COUNT(*) ctr,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                                 dal dal_order,
                                 al al_order,
                                 province.nome
                    FROM    attivita_detail,
                                 anagrafica,
                                 province
                    WHERE  anagrafica.id_provincia = province.id
                    AND      attivita_detail.id_socio = anagrafica.id
                    AND      attivita_detail.tipo = 'V'
                    AND      attivita_detail.id_attpell = $sqlid_attpell
                    GROUP BY 2,3,4,5,6
                    ORDER BY 6, 4, 5";
              
$r = $conn->query($sql);
$row = $r->fetch_assoc();
$txtP = "Foglio presenze - " .$row["desa"] . "       Periodo: " . $row["dal"] . " - " . $row["al"];

// Stampo i giorni di permanenza
$sDate=$row["dal_c"];
$dateToPrint=$sDate;
$eDate=$row["al_c"];
//$stop_date = date('Y-m-d H:i:s', strtotime($stop_date . ' +1 day'));
$i=3;

$diff = abs(strtotime($eDate) - strtotime($sDate));
$days = floor($diff / (60*60*24));
$days++;

//57, 60, 30
$dLength = round((300-130)/$days);

if($debug) {
    echo "$fname: ciclo per i giorni ($days)<br>";
    echo "$fname: lunghezza casella ($dLength)<br>";
    }
    
while($dateToPrint <= $eDate) {
	       if($debug)
              echo "$fname: data da stampare ($dateToPrint)<br>";

	       $subInt[$i] = strtolower(date("d",strtotime($dateToPrint)));
	       if($debug)
              echo "$fname: giorno da stampare ($subInt[$i])<br>";
	       $subPos[$i] = $dLength;
	       $i++;
	       $dateToPrint = date('Y-m-d', strtotime($dateToPrint . ' +1 day'));
	       
          }
             
// create new PDF document
$pdf = new MYPDF($txtF, $subInt ,$subPos, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Foglio presenze (pronto per la stampa)');
$pdf->SetSubject('Presenze, Attivita/Viaggi');
$pdf->SetKeywords('Presenze, Attivita/Viaggi');

// set default header data
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(MYPDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, MYPDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Add a page
// This method has several options, check the source code documentation for more information.

// Set font to helvetica

$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );

// Accedo al DB (Dati della sottosezione)
if($debug)
    echo "$fname SQL Header: $sqlH<br>";

$resultH = $conn->query($sqlH);
$index=0;
$rowH = $resultH->fetch_assoc();

$pdfH = $rowH["nome"];
$pdfH1 = $rowH["indirizzo"];
          
if($fromSearch)
   $sqlV = $sqlSearch;

$oldid_grp=0;
$desGrp=' ';
               
if($debug) {
    echo "$fname: SQL Partecipanti $sqlV<br>";          
    echo "$fname: SQL # Partecipanti per provincia $sqlProv<br>";
   }          

$result = $conn->query($sqlV);
// Accedo al DB (ciclo per le anagrafiche)
while($row = $result->fetch_assoc()) {
	       if($index == 0) { // Prima riga, intestazione
	       	 $oldid_grp = $row["id_par"];

	           if($suddGroup) { // Stampo il gruppo
	              $desGrp = $row["des_par"];
                }

	           if($descat)
	               $txt .= "     Categoria: $descat";
	               
              $pdf->setAdditionalH($txtP);
              $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
              $pdf->AddPage();
             }	


	       $html .= "<td><p>" . $row["nome"] . " ";
	       $html .= "Nato il: " . substr($row["dt_nas"], 0, 10). "<br>";
	
	       if(($index % 2 == 0) && ($index > 0)) {
	            $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
               }
                         
          // Nome partecipante
          $textToPrint = substr($row["nome"], 0, 23);
          $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
          $pdf->Cell(50, 0,$textToPrint, 1,0, 'L');

          // Gruppo
           $textToPrint = $row["des_par"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->Cell(50, '',$textToPrint, 1, 0, 'L');

          // Servizio
           $textToPrint = $row["des_ser"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->Cell(30, '',$textToPrint, 1, 0, 'L');

          // Giorni
           $textToPrint = ' ';
           
           for($i=3; $i < count($subInt); $i++) {
                 $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
                 $pdf->Cell($dLength, '',$textToPrint, 1, 0, 'L');
                }
           $pdf->Ln();


           $ctrViaggio +=1;
           $index++;
 	     }

if($index > 0)
   if($debug)
      echo $html;
    else {

      $pdf->setAdditionalH($txtP. " (Partecipanti per provincia)");
      $pdf->AddPage();
      $rsP = $conn->query($sqlProv);
      $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
      while($rrsP = $rsP->fetch_assoc()) {
      	          $pdf->Cell(60,0,"Provincia di " . $rrsP["nome"]);
      	          $pdf->Cell(70,0,"Dal: " . $rrsP["dal"] . " al: " . $rrsP["al"]);
      	          $pdf->Cell(10,0,"Totale: ");
                $pdf->Cell(15, 0, $rrsP["ctr"],  0, 1, 'R');
              }
      $pdf->Output('Stampa_partecipanti.pdf', 'I');
   } 
else {
	echo "<html><body>";
	echo "<h2>Nessun dato trovato con i parametri di ricerca</h2>";
	echo "</body></html>";
}
$conn->close();
 
//============================================================+
// END OF FILE
//============================================================+