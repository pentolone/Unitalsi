<?php
/****************************************************************************************************
*
*  Stampa i frontespizi delle camere per il viaggio
*
*  @file stampa_frontespizi.php
*  @abstract Genera file PDF con l'elenco delle ricevute emesse
*  @author Luca Romano
*  @version 1.1
*  @last 2017-09-29
*  @since 2017-03-03
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');

$debug=false;
$fname=basename(__FILE__);
// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
define("MYPDF_MARGIN_TOP", 35);
define('BORDER_REQUIRED', 0); // 0 = No borders, 1 = Borders
define('NRICEVUTA_LENGTH', 33);
define('IMPORTO_LENGTH', 17);
define('MYPDF_MARGIN_LEFT',4);
define('MYPDF_MARGIN_RIGHT',4);

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
	protected $addH;
	
	function __construct( $par, $subIntestazione, $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
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
       $this->Image('/images/Logo_UNITALSI.jpg', '', '', 20); // Logo UNITALSI
       $this->Image('/images/Logo Borghetto.jpg', 175,'', 30); // Logo Struttura
  
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

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$logoStruttura='Logo_UNITALSI.jpg';
$pdfH='';
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$date_format=ritorna_data_locale();
$index=0;
$html='';

// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$sqlid_sottosezione=0;
$sqlanno=0;
$sqln_ricevuta=0;
$rooms=array();

// Dati risultanti dalla query
$sqlnome='';
$sqlindirizzo='';
$sqlcap='';
$sqlcitta='';
$sqldata_nascita='';
$sqlcf='';
$sqlluogo_nascita='';
$sqlid_categoria=0;

$sqldal='';
$sqlal='';
$effettivo=false;
$tesseramento=false;
$catID=0;

$prn_format='P';

$str = $_SERVER["SERVER_NAME"]; // Get the server name

preg_match($pattern, $str, $matches); //find matching pattern, it should always work (i.e. www)
$extraDir =  rtrim($matches[0], '.');
   
//$logo = '../' . $extraDir . $pathFile . $logo;
$logo = '../images/' . $logo;
$logoStruttura = '../images/' . $logoStruttura;
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
                   if($debug) {
                    	 echo $fname . ": KEY = " . $key . '<br>';
                    	 echo $fname . ": VALUE = " . $value . '<br>';
                    	 echo $fname . ": INDEX = " . $index . '<br><br>';
                    	}
    	    	      if(is_array($value) && $key=='room') {
    	            	   if($debug)
    	            	       print_r($value);
    	            	       
    	            	   $i_array=0;
      		            foreach($value as $value1) {
                                   $rooms[$i_array] = $value1; 	
                                   $i_array++;
                 	               }
      				     continue;

    	               }
    	            else
                      $kv[] = "$key=$value";
      

                   switch($key) {
      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;
      	
      		                      case "anno": // Anno di pertinenza
      				                        $sql_anno= $value;
      				                        break;
      	
      		                      case "id_attpell": // Identificativo viaggio/pellegrinaggio
      				                        $sqlid_attpell= $value;
      				                        break;

      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
      				                        break;
      				              }  
                  $index++;
                }

}

// SQL viaggio
$sqlselect_pellegrinaggio = "SELECT SUBSTRING(DATE_FORMAT(pellegrinaggi.dal,'" . $date_format ."'),1,10) dal,
                                                SUBSTRING(DATE_FORMAT(pellegrinaggi.al,'" . $date_format ."'),1,10) al, 
                                                pellegrinaggi.dal dal_order,
                                                pellegrinaggi.id id_prn, descrizione_pellegrinaggio.descrizione desa,
                                                pellegrinaggi.al al_ot
                                    FROM   descrizione_pellegrinaggio,
                                                pellegrinaggi
                                    WHERE pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
                                    AND     pellegrinaggi.id = $sqlid_attpell";
               
if($debug)
    echo "$fname SQL Sub intestazione = $sqlselect_pellegrinaggio<br>";
    
$r = $conn->query($sqlselect_pellegrinaggio);
$r1 = $r->fetch_assoc();

$subintestazione = $r1["desa"] . "         Periodo: " . $r1["dal"] . " - " . $r1["al"];
$r->close();

// SQL per camere selezionate
$sql = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                          anagrafica.indirizzo,
                          anagrafica.cap,
                          citta,
                          luogo_nascita,
                          SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas, 
                          AL_camere.codice,
                          AL_piani.descrizione desp,
                          AL_piani.id,
                          AL_occupazione.id_camera,
                          SUBSTR(DATE_FORMAT(AL_occupazione.dal, '" . $date_format . "'),1,10) dal_display,                
                          SUBSTR(DATE_FORMAT(AL_occupazione.al, '" . $date_format . "'),1,10) al_display,                
                          n_tessera_unitalsi,
                          AL_occupazione.dal,
                          AL_occupazione.al
            FROM    anagrafica,
                          AL_occupazione,
                          AL_camere,
                          AL_piani
             WHERE  anagrafica.id = AL_occupazione.id_socio
             AND      AL_occupazione.id_attpell= $sqlid_attpell
             AND      AL_occupazione.id_camera = AL_camere.id
             AND      AL_camere.id_piano = AL_piani.id
             AND      AL_occupazione.id_camera IN(";
             
for($i=0; $i < count($rooms);$i++) {
      $sql .= $rooms[$i] . ", ";
}

$sql = rtrim($sql, ", ") . ")";
$sql .= " ORDER BY 8, 9, 7, 10, AL_occupazione.dal, AL_occupazione.al, 1";

if($debug)
    echo "$fname SQL Camere = $sql<br>";;

// SQL per sottosezione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id = " . $sqlid_sottosezione;

if($debug)
    echo "$fname SQL Intestazione = $sqlH<br>";

$result = $conn->query($sqlH);
$row = $result->fetch_assoc();
$pdfH = $row["nome"];
$pdfH1 = $row["indirizzo"];

$pdf = new MYPDF('Stampa frontespizi (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s') ,$subintestazione, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Frontespizi (pronti per la stampa)');
$pdf->SetSubject('Frontespizi');
$pdf->SetKeywords('Frontespizi');

// set default header data
$pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,64,0), array(0,64,128));
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

// Accedo al DB
$result = $conn->query($sql);
$html = "<table><tr>";

$index=0;
$old_room = '-';
$old_dal = '0001-01-01';
$old_al = '0001-01-01';

while($row = $result->fetch_assoc()) {
		    $index++;

	       if($row["codice"] != $old_room) { // Stampo dati della camera
	           $old_room = $row["codice"];
              $old_dal = '0001-01-01';
              $old_al = '0001-01-01';
		        $pdf->AddPage();

              //$pdf->writeHTML('<HR>');
              $pdf->SetFont ('helvetica', 'B', '16px' , '', 'default', true );
              $textToPrint =  "COMPOSIZIONE CAMERA: " . $row["codice"];
              $pdf->Cell(80, 0, $textToPrint, BORDER_REQUIRED, 0, 'L', 0, '', 0, false, 'T', 'T');
         
              $textToPrint =  $row["desp"];   
              $pdf->Cell(0, 0, $textToPrint, BORDER_REQUIRED, 1, 'R', 0, '', 0, false, 'T', 'T');
              $pdf->writeHTML('<HR>');
	          }

	       if($row["dal"] != $old_dal ||
	           $row["al"] != $old_al) { // Stampo periodo dal al
	           $textToPrint = " ";
              $pdf->Cell(0, 0, $textToPrint, BORDER_REQUIRED, 1, 'C', 0, '', 0, false, 'T', 'T');
              $pdf->SetFont ('helvetica', 'NI', '18px' , '', 'default', true );

	           $textToPrint = "Dal: " . $row["dal_display"] . " Al: " . $row["al_display"];
              $pdf->Cell(0, 0, $textToPrint, BORDER_REQUIRED, 1, 'C', 0, '', 0, false, 'T', 'T');
              $pdf->SetFont ('helvetica', 'NI', '1px' , '', 'default', true );
              $pdf->writeHTML('<HR>');
              
              $old_dal = $row["dal"];
              $old_al = $row["al"];
	          }
	          
          $pdf->SetFont ('helvetica', 'B', '20px' , '', 'default', true );
          $textToPrint =   $row["nome"];
          $pdf->Cell(0, 0, $textToPrint, BORDER_REQUIRED, 1, 'L', 0, '', 0, false, 'T', 'T');

 }
 
   if($index > 0) { // Stampo pdf
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
          $pdf->Output('Frontespizi.pdf', 'I');
        }

   $conn->close();
//============================================================+
// END OF FILE
//============================================================+
