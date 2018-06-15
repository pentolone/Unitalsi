<?php
/****************************************************************************************************
*
*  Stampa i viaggi presenti nel database
*
*  @file stampa_viaggio.php
*  @abstract Genera file PDF con anagrafiche e saldo dei partecipanti presenti nel DB in formato A4
*  @author Luca Romano
*  @version 1.0
*  @since 2017-02-21
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
define('EURO',chr(128));
setlocale(LC_MONETARY, 'it_IT');

// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";
define("MYPDF_MARGIN_TOP", 35);

$sott_app = ritorna_sottosezione_pertinenza();

$debug=false;
$fname=basename(__FILE__);
$sqlid_sottosezione=0;
$sqlid_pellegrinaggio=0;
$sqlanno=0;

$sqlold_s=0;
$sqlold_p=0;
$fromSearch=false; // Verifica se richiamato da ricerca dati
$totViaggio= array(0,0,0,0,0); // Totale spese viaggio
$ctrViaggio=0; // Totale partecipanti per viaggio
$sqlSearch='';

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$txtF='Stampa viaggio/pellegrinaggio (Rev 1.0) Generato il: '; // Footer
$pdfH='';
$date_format=ritorna_data_locale();
$index=0;
$html='';
// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$subInt="Nominativo                                       Data di nascita      Albergo       Viaggio   Riduzione  Associativa      Totale";

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
                                        $txtF='Stampa risultati ricerca anagrafica (Rev 1.0) Generato il: '; // Footer
      				                        break;

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                       case "id_prn": // id pellegrinaggio
      					                      $sqlid_pellegrinaggio= $value;
      					                       break;

      		                      case "anno": // anno riferimento
      					                    $sqlanno = $value;
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
	
}

// SQL per sottosezione
$sqlsottosezione= "SELECT id, nome
                                 FROM   sottosezione";

if($sqlid_sottosezione > 0)
    $sqlsottosezione .= " WHERE id = " . $sqlid_sottosezione;
    
$sqlsottosezione .= " ORDER BY 2";

// SQL per anagrafica
$sql = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                                        SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                        descrizione_pellegrinaggio.descrizione desp,
                                        viaggi.id_pellegrinaggio id_pell,
                                        viaggi.costo_pernotto,
                                        viaggi.costo_mezzo,
                                        viaggi.costo_riduzione,
                                        viaggi.costo_altri,
                                        IFNULL(SUBSTR(DATE_FORMAT(dal, '" . $date_format . "'),1,10), 'xx/xx/xxxx') dt_dal,
                                        IFNULL(SUBSTR(DATE_FORMAT(al, '" . $date_format . "'),1,10), 'xx/xx/xxxx') dt_al
             FROM   anagrafica,
                          viaggi,
                          pellegrinaggi,
                          descrizione_pellegrinaggio
             WHERE anagrafica.id = viaggi.id_socio
             AND     viaggi.id_pellegrinaggio = pellegrinaggi.id
             AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id";

//if($sqlid_sottosezione > 0)
    //$sql .= " AND viaggi.id_sottosezione = " . $sqlid_sottosezione;

if($sqlid_pellegrinaggio > 0)
    $sql .= " AND viaggi.id_pellegrinaggio = " . $sqlid_pellegrinaggio;

if($sqlanno > 0)
    $sql .= " AND viaggi.anno = " . $sqlanno;

// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione";
if($sqlid_sottosezione > 0)
    $sqlH .= " AND sottosezione.id = " . $sqlid_sottosezione;

$sqlH .= " ORDER BY nome"; 

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
       $this->Image('/images/Logo_UNITALSI.jpg', '', '', 20);
  
       $this->SetY(12);
       $this->SetFont('helvetica', 'B', '13px');
       $this->Cell(0, 15, $headerData['title'], 0, true, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetFont('helvetica', 'B', '10px');
       $this->Cell(0, 15, $headerData['string'], 0, false, 'C', 0, '', 0, false, 'M', 'M');

       $this->SetY(25);
       $this->Cell(0, 15, $this->addH, 0, false, 'R', 0, '', 0, false, 'M', 'M');
       $this->SetY(30);
       $this->writeHTML('<hr><br>');
       
       $this->SetY(33);
       $this->SetFont('helvetica', 'BI', '10px');
       $this->Cell(0, 0, $this->subintestazione, 0, false, 'L', 0, '', 0, false, 'M', 'M');
       $this->SetY(35);
      // $this->get_
     //      $this->writeHTMLCell(30, 0, 5, $y, '<img src="' . $logo . '">'); 

        // Set font
        // Page number
        //$this->Cell(0, 10, $foot . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
             
// create new PDF document
$pdf = new MYPDF($txtF. date('d/m/Y H:i:s'), $subInt ,PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco partecipanti (pronto per la stampa)');
$pdf->SetSubject('Anagrafica, Viaggi');
$pdf->SetKeywords('Anagrafica, Viaggi');

// set default header data
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

$pdf->SetFont ('helvetica', '', '12px' , '', 'default', true );
//$pdf->Cell(80, 0, $pdfH, false, 'L', 2, 'C',false,'',0,false,'C','C');

// Accedo al DB (ciclo per le sottosezioni)
if($debug)
    echo "$fname SQL Header: $sqlH<br>";
$resultH = $conn->query($sqlH);
$index=0;
while($rowH = $resultH->fetch_assoc()) {

	       $pdfH = $rowH["nome"];
          $pdfH1 = $rowH["indirizzo"];
	       if(($rowH["id"] != $sqlold_s) && $index != 0) {
	       	 $pdf->AddPage();
	       	 $sqlold_s = $rowH["id"];
             }

          $sqlq = $sql . " AND viaggi.id_sottosezione = " . $rowH["id"];
	       
	       if($sqlid_pellegrinaggio == 0) {
	       	 $sqlq .= " ORDER BY desp,id_pell, nome";
            }
          else
              $sqlq .=  " ORDER BY nome"; 

           if($fromSearch)
               $sqlq = $sqlSearch;
               
           if($debug)
              echo "$fname: SQL Anagrafica $sqlq<br>";          
          $result = $conn->query($sqlq);

// Accedo al DB (ciclo per le anagrafiche)
          while($row = $result->fetch_assoc()) {
	                 if($index == 0) {
	                 	  $sqlold_p = $row["id_pell"];

                        $html = "<table><tr>";
                        $pdf->setAdditionalH("Elenco partecipanti - " .$row["desp"] . "       Periodo: " . $row["dt_dal"] . " - " . $row["dt_al"]);
                        $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
                        $pdf->AddPage();
                       }	

	                 if($row["id_pell"] != $sqlold_p) {
	       	           $sqlold_p = $row["id_pell"]; // Scrivo i totali
                        $pdf->writeHTML('<hr>');
                        $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
                        $pdf->MultiCell(86, 0,"N. partecipanti " . $ctrViaggio, 0, 'L', false,0, 10,'', true);
                        $pdf->MultiCell(20, '',money_format('%(!n',$totViaggio[0]), 0, 'R', false,0, '','', true);
                        $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[1]), 0, 'R', false,0, '','', true);
                        $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[2]), 0, 'R', false,0, '','', true);
                        $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[3]), 0, 'R', false,0, '','', true);
                        $pdf->MultiCell(0, '',money_format('%(!n',$totViaggio[4]), 0, 'R', false,0, '','', true);

	       	           $ctrViaggio=0;
	       	           $totViaggio[0] = 0;
	       	           $totViaggio[1] = 0;
	       	           $totViaggio[2] = 0;
	       	           $totViaggio[3] = 0;
	       	           $totViaggio[4] = 0;
                        $pdf->setAdditionalH("Elenco partecipanti - " .$row["desp"] . "       Del: " . $row["dt_dal"]);
                        $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
	       	           $pdf->AddPage();
	       	           //$pdf->SetFont ('helvetica', 'B', '12px' , '', 'default', true );
	       	           //$pdf->MultiCell(0, 0, 'Gruppo : ' . $row["desp"], 0, 'L', false,1, 10,$pdf->getY(), true);

                       }
	                 $html .= "<td><p>" . $row["nome"] . " ";
	                 $html .= "Nato il: " . substr($row["dt_nas"], 0, 10). "<br>";
	
	                 if(($index % 2 == 0) && ($index > 0)) {
	                      $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
                        }

                     $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
                     $pdf->MultiCell(70, 0,$row["nome"], 0, 'L', false,0, 10,$pdf->getY(), true);

                     $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
                     $pdf->MultiCell(18, '',$row["dt_nas"], 0, 'L', false,0, '','', true);
                     //$pdf->SetY($pdf->GetY() + 1);

                     if($row["costo_pernotto"] != 0)
                         $pdf->MultiCell(18, '',money_format('%(!n',$row["costo_pernotto"]), 0, 'R', false,0, '','', true);
                     else
                         $pdf->MultiCell(18, '',' ', 0, 'R', false,0, '','', true);

                     if($row["costo_mezzo"] != 0)
                         $pdf->MultiCell(18, '',money_format('%(!n',$row["costo_mezzo"]), 0, 'R', false,0, '','', true);
                     else
                         $pdf->MultiCell(18, '',' ', 0, 'R', false,0, '','', true);

                     if($row["costo_riduzione"] != 0)
                         $pdf->MultiCell(18, '',money_format('%(!n',$row["costo_riduzione"]), 0, 'R', false,0, '','', true);
                     else
                         $pdf->MultiCell(18, '',' ', 0, 'R', false,0, '','', true);

                     if($row["costo_altri"] != 0)
                         $pdf->MultiCell(18, '',money_format('%(!n',$row["costo_altri"]), 0, 'R', false,0, '','', true);
                     else
                         $pdf->MultiCell(18, '',' ', 0, 'R', false,0, '','', true);

                     $pdf->SetFont ('helvetica', 'B' , '9px' , '', '' );
                     $tot = $row["costo_pernotto"] + $row["costo_mezzo"] - $row["costo_riduzione"] + $row["costo_altri"];
                     $pdf->MultiCell(0, '',money_format('%(!n',$tot), 0, 'R', false,1, '','', true);
                     
                     $totViaggio[0] += $row["costo_pernotto"];
                     $totViaggio[1] += $row["costo_mezzo"];
                     $totViaggio[2] += $row["costo_riduzione"];
                     $totViaggio[3] += $row["costo_altri"];
                     $totViaggio[4] += $tot;
                     $ctrViaggio +=1;
                     $index++;

 	     }
}
$conn->close();

if($index > 0)
   if($debug)
      echo $html;
    else {
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------
// Stampo i totali
      $pdf->writeHTML('<hr>');
      $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
      $pdf->MultiCell(86,0,"N. partecipanti " . $ctrViaggio, 0, 'L', false,0, 10,'', true);
      $pdf->MultiCell(20, '',money_format('%(!n',$totViaggio[0]), 0, 'R', false,0, '','', true);
      $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[1]), 0, 'R', false,0, '','', true);
      $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[2]), 0, 'R', false,0, '','', true);
      $pdf->MultiCell(18, '',money_format('%(!n',$totViaggio[3]), 0, 'R', false,0, '','', true);
      $pdf->MultiCell(0, '',money_format('%(!n',$totViaggio[4]), 0, 'R', false,0, '','', true);
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
      $pdf->Output('Stampa_viaggi.pdf', 'I');
   } 
//============================================================+
// END OF FILE
//============================================================+