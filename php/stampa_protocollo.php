<?php
/****************************************************************************************************
*
*  Stampa l'elenco del protocollo nel periodo
*
*  @file stampa_protocollo.php
*  @abstract Genera file PDF con l'elenco del protocollo
*  @author Luca Romano
*  @version 1.0
*  @since 2017-03-03
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 37);

setlocale(LC_MONETARY, 'it_IT');

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
   protected $wSize=array();
	protected $addH;
	
	function __construct( $par, $subIntestazione, $wSize, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        $this->wSize = $wSize;
        $this->subintestazione = $subIntestazione;
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
       $this->SetFont('helvetica', 'BI', '10px');
       $this->Cell(0, 0, $this->subintestazione, 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
       
// Legenda campi di stampa 
       $this->SetX(5);
       $this->SetY(33);
       $this->SetFont('helvetica', 'BI', '10px');
       $this->Cell(12, 0, '#', 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->Cell(17, 0, 'Tipo', 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->Cell(17, 0, 'Invio', 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->Cell(17, 0, 'Ric.', 0, false, 'C', 0, '', 0, false, 'M', 'M');
       $this->Cell($this->wSize["oggetto"], 0, 'Oggetto', 0, false, 'L', 0, '', 0, false, 'M', 'M');
       $this->Cell($this->wSize["mittente"], 0, 'Mitt./Dest.', 0, false, 'L', 0, '', 0, false, 'M', 'M');
       $this->Cell($this->wSize["nlettera"], 0, 'N.lettera', 0, false, 'L', 0, '', 0, false, 'M', 'M');
       $this->Cell(0, 0, 'Consegnato da', 0, false, 'L', 0, '', 0, false, 'M', 'M');
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

$sott_app = ritorna_sottosezione_pertinenza();

$debug=false;
$fname=basename(__FILE__);

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$pdfH='';
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$date_format=ritorna_data_locale();
$index=0;
$html='';
$sizePortrait = array("oggetto" => 50, "mittente" => 30, "nlettera" => 20, "consegnato" =>40);
$sizeLandscape = array("oggetto" => 70, "mittente" => 60, "nlettera" => 20, "consegnato" =>60);
$sizew=array();

// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$sqlid_sottosezione=0;
$sqlanno=0;

// Dati risultanti dalla query

$sqltipo='-';
$sqldal='';
$sqlal='';
$effettivo=false;
$tesseramento=false;
$catID=0;
$prn_format='L';

$outputCSV=false;
$target_CSV='../php/esporta_csv.php';

 $intestazioneCSV = array("ID", "TIPO", "NUMERO PROTOCOLLO","DATA LETTERA", "DATA RICEZIONE", "OGGETTO" , "MITTENTE",
                                           "DESTINATARIO", "NUMERO LETTERA","CONSEGNATO CON", "REFERENTE", "NOTE", "DATA/ORA", "UTENTE");

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');
   
//$logo = '../' . $extraDir . $pathFile . $logo;
$logo = '../images/' . $logo;
//echo $logo;

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

      		                       case "tipo": // tipo
      					                      $sqltipo = $value;
      					                       break;

      		                       case "dal": // dal
      					                      $sqldal = $value;
      					                       break;

      		                       case "al": // al
      					                      $sqlal = $value;
 
      		                      case "prn_format": // Formato stampa
      					                     $prn_format = $value;
      					                     if($prn_format == 'L')
      					                         $sizew = $sizeLandscape;
      					                     else
      					                         $sizew = $sizePortrait;
      				                        break;

      		                      case "CSV_x": // Richiesta Output CSV
      		                              $outputCSV=true;
      				                        break;
      	
      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
      				                        break;
      				              }  
                  $index++;
                }
	
}

$subintestazione = 'Stampa protocollo dal ' . substr($sqldal,8,2) . '/' .
                                                                        substr($sqldal,5,2) . '/'.
                                                                        substr($sqldal,0,4) . ' al ' .
                                                                        substr($sqlal,8,2) . '/' .
                                                                        substr($sqlal,5,2) . '/'.
                                                                        substr($sqlal,0,4);

// SQL per protocollo
$sql =  "SELECT id, tipo,";

if($outputCSV)
   $sql .=  "CONCAT('\'', codice) codice,";
else
   $sql .=  "codice,";

$sql .=              "SUBSTR(DATE_FORMAT(data_invio, '" . $date_format . "'),1,10) data_invio,
                          SUBSTR(DATE_FORMAT(data_arrivo, '" . $date_format . "'),1,10) data_arrivo,
                          oggetto, mittente, destinatario, n_lettera, consegnato,
                          referente, note,
                          DATE_FORMAT(data, '" . $date_format . "') data,
                          utente
            FROM    protocollo
            WHERE  id_sottosezione = " . $sqlid_sottosezione .
           " AND     data_invio BETWEEN '" . $sqldal . "' AND '" . $sqlal . "'";

if($sqltipo != '-')
    $sql .= " AND tipo ='" . $sqltipo . "'";
         
$sql .= "            ORDER BY 1 DESC";

if($debug)
    echo "$fname SQL = $sql<br>";;

if($outputCSV) {
	echo "<form name='CSV' action='" . $target_CSV . "' method='POST'>";
	echo "<input type='hidden' name='sqlsearch' value='" . htmlspecialchars($sql, $defCharsetFlags, $defCharset) . "'>";
   echo "<input type='hidden' name='extras' value='" . htmlentities(serialize($intestazioneCSV)) . "'>";
                	
	echo "</form>";
   echo "<script>this.document.CSV.submit();</script>";
   return;
}// SQL per società (sede centrale)
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id = " . $sqlid_sottosezione;

// SQL per sottosezione
$sqlH1 = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome
                 FROM   sezione, sottosezione
                 WHERE  sezione.id = sottosezione.id_sezione
                 AND      sottosezione.id = " . $sqlid_sottosezione;

if($debug)
    echo $sqlH;
$result = $conn->query($sqlH);
$row = $result->fetch_assoc();
$pdfH = $row["nome"];
$pdfH1 = $row["indirizzo"];

$pdf = new MYPDF('Stampa elenco protocollo (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s') ,$subintestazione, $sizew, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco protocollo (pronto per la stampa)');
$pdf->SetSubject('Elenco protocollo');
$pdf->SetKeywords('protocollo');

// set default header data
$pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,64,0), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
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

//$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');

// Accedo al DB
$result = $conn->query($sql);
$html = "<table><tr>";

$index=0;
$y=0;
$yNewLine=MYPDF_MARGIN_TOP;
$pdf->setY($yNewLine);
$oht = "<table>";
//$pdf->writeHTML($oht);

while($row = $result->fetch_assoc()) {
	       if($index == 0) {
		        $pdf->AddPage();
	       }
	       else {
	           $yNewLine = $pdf->getY()+7;
              $pdf->setY($yNewLine); 
	          }

	       
		    $index++;		        
		    $y = $pdf->getY();

	       $sqlcodice = $row["codice"];
	       $sqltipo = $row["tipo"];
	       $sqldata_invio = $row["data_invio"];
	       $sqldata_arrivo = $row["data_arrivo"];
	       $sqloggetto = $row["oggetto"];
	       $sqlmittente= $row["mittente"];
	       $sqldestinatario = $row["destinatario"];
	       $sqln_lettera = $row["n_lettera"];
	       $sqlconsegnato = $row["consegnato"];
	       $sqldata = $row["data"];
	       $sqlutente = $row["utente"];

          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
          
// Inizio output

          $pdf->Cell(17,0, $sqlcodice, 0,0, 'L', 0,0);
          
          if($sqltipo == 'IN')
              $textToPrint = "Entrata";
          else
              $textToPrint = "Uscita";
         
          $pdf->Cell(12,0, $textToPrint, 0,0, 'L', 0,0);
          $pdf->Cell(17,0, $sqldata_invio, 0,0, 'C', 0,0);
          $pdf->Cell(17,0, $sqldata_arrivo, 0,0, 'C', 0,0);
          
          $pdf->MultiCell($sizew["oggetto"],0, $sqloggetto, 0, 'L', 0,0);          
  
          if($sqlmittente)
             $pdf->Multicell($sizew["mittente"],0, $sqlmittente, 0, 'L', 0,0, $pdf->getX(),'');
           else
             $pdf->Multicell($sizew["mittente"],0, $sqldestinatario, 0, 'L', 0,0, $pdf->getX() ,'');

          $pdf->Multicell(20,0, $sqln_lettera, 0, 'L', 0,0, $pdf->getX(),'');
 
          $pdf->Multicell(0,0, $sqlconsegnato, 0, 'L', false,1, $pdf->getX(),''); 
 }
 
   if($index > 0) { // Ok! 

       if($debug)
          echo $html;
       else
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.

          $pdf->Output('Elenco_protocollo.pdf', 'I');

      }
   else 
      echo "<h2>Nessun dato trovato coi parametri di ricerca</h2>";
   $conn->close();
//============================================================+
// END OF FILE
//============================================================+
