<?php
/****************************************************************************************************
*
*  Stampa l'ultima riga inserita nella base dati
*
*  @file stampa_riga_inserita.php
*  @abstract Genera file PDF con i dati dell'ultima riga inserita
*  @author Luca Romano
*  @version 1.0
*  @since 2018-02-05
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');

define("MYPDF_MARGIN_HEADER",0);
define("MYPDF_MARGIN_FOOTER",0);
define("MYPDF_MARGIN_TOP",35);
define("MYPDF_MARGIN_LEFT",3);
define("MYPDF_MARGIN_RIGHT",3);
define("MYPDF_MARGIN_BOTTOM",2);

define('MYPDF_PAGE_ORIENTATION', 'P'); // Portrait (A4)

$debug=false;
$fname=basename(__FILE__);
$sott_app = ritorna_sottosezione_pertinenza();

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";
$date_format=ritorna_data_locale();
$sqlID=0; // ID ultima riga inserita
$table_name='unknown'; // tabella da cui stampare i dati
$sql=null; // SELECT statement
$txtF='Stampa riga inserita (Rev 1.0) Generato il: '; // Footer
$logo='Logo_UNITALSI.jpg';

// Array da compilare coi campi da stampare
$arrayFieldTitle=array();

// Class TCPDF
class MYPDF extends TCPDF {
	private $par;
	
	function __construct( $par , $orientation, $unit, $format ) 
    {
        parent::__construct( $orientation, $unit, $format, false, 'UTF-8', false );

        $this->par = $par;
        //...
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
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
       $this->SetY(35);
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

$index=0;
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

      		                      case "id": // id ultima riga inserita
      				                        $sqlID = $value;
      				                        break;

      		                       case "tablename": // nome tabella
      					                      $table_name = $value;      					                      
      					                       break;

      		                       default: // OPS!
      				                        echo "UNKNOWN key = ".$key;
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
	
}
// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione
               AND     sottosezione.id = $sott_app";

$resultH = $conn->query($sqlH);
$rowH = $resultH->fetch_assoc();

$pdfH = $rowH["nome"];
$pdfH1 = "Ultima riga inserita in tabella $table_name; ID = $sqlID";

switch($table_name) {
	         case "anagrafica":
	                 $sql = "SELECT UPPER(anagrafica.nome),
	                                           UPPER(anagrafica.cognome),
	                                           CONCAT(UPPER(anagrafica.luogo_nascita), ' - ',
	                                                         SUBSTR(DATE_FORMAT(anagrafica.data_nascita,'" . $date_format . "'), 1,10)) nas,
	                                           anagrafica.cf,
	                                           categoria.descrizione dcat,
	                                           gruppo_parrocchiale.descrizione dgrp,
	                                           CONCAT(UPPER(anagrafica.indirizzo),' - ',
	                                                          anagrafica.cap, ' ',
	                                                          UPPER(anagrafica.citta)) ind,
	                                           anagrafica.telefono,
	                                           anagrafica.cellulare,
	                                           anagrafica.email,
	                                           CONCAT(tipo_documento.descrizione,' ',
	                                                         UPPER(anagrafica.n_doc), ' ',
	                                                         SUBSTR(DATE_FORMAT(anagrafica.data_ril, '" . $date_format . "'),1,10), ' ',
	                                                         SUBSTR(DATE_FORMAT(anagrafica.data_exp, '" . $date_format . "'),1,10)) doc
	                                           FROM  anagrafica,
	                                                       tipo_documento,
	                                                       categoria,
	                                                       gruppo_parrocchiale
	                                           WHERE anagrafica.id = $sqlID
	                                           AND     anagrafica.id_tipo_doc = tipo_documento.id
	                                           AND     anagrafica.id_categoria = categoria.id
	                                           AND     anagrafica.id_gruppo_par = gruppo_parrocchiale.id";
	                                          
      					                      
      					 // Valorizzo array
      					 $arrayFieldTitle[0] = "Nome";
      					 $arrayFieldTitle[1] = "Cognome";
      					 $arrayFieldTitle[2] = "Luogo e data di nascita";
      					 $arrayFieldTitle[3] = "Codice fiscale";
      					 $arrayFieldTitle[4] = "Categoria";
      					 $arrayFieldTitle[5] = "Gruppo";
      					 $arrayFieldTitle[6] = "Indirizzo";
      					 $arrayFieldTitle[7] = "Telefono";
      					 $arrayFieldTitle[8] = "Cellulare";
      					 $arrayFieldTitle[9] = "E-mail";
      					 $arrayFieldTitle[10] = "Documento Rilasciato Scadenza";
	                 break;
         }    
    
if(!$sql) // SQL vuoto
    return;

if($debug)
    echo "$fname: SQL ultima riga inserita $sql<br>";
              
// create new PDF document
$pdf = new MYPDF($txtF. ritorna_data_attuale(), MYPDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);
$pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Ultima riga inserita in tabella ' . $table_name .', (pronto per la stampa)');
$pdf->SetSubject('Ultima riga, $table_name');
$pdf->SetKeywords('Ultima riga, $table_name');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, MYPDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, MYPDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set padding
//$pdf->setCellPaddings(LABEL_PADDING_LEFT, LABEL_PADDING_TOP, LABEL_PADDING_RIGHT, LABEL_PADDING_BOTTOM);


// Set font to helvetica
$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');

// Accedo al DB
$rs = $conn->query($sql);

$rw = $rs->fetch_assoc();
$finfo = $rs->fetch_fields();

$pdf->AddPage();

$index = 0;
foreach($finfo as $val) { // Ciclo per i campi
             $pdf->SetFont ('helvetica', '', '11px' , '', 'default', true );
             $pdf->Cell(60, 0, $arrayFieldTitle[$index], 0, 0, 'L');
             $pdf->SetFont ('helveticaB', '', '12px' , '', 'default', true );
             $pdf->Cell(0, 0, $rw[$val->name], 0, 1, 'L');
             $pdf->Ln();
             $index++;
            }

$pdf->Output('Stampa_ultima_riga.pdf', 'I');
$conn->close();
 
//============================================================+
// END OF FILE
//============================================================+