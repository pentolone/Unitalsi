<?php
/****************************************************************************************************
*
*  Stampa l'elenco delle prenotazioni
*
*  @file stampa_prenotazioni.php
*  @abstract Genera file PDF con l'elenco dei soci associati alla camera
*  @author Luca Romano
*  @version 1.0
*  @since 2017-03-19
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 38);

setlocale(LC_MONETARY, 'it_IT');
$debug=false;
$fname=basename(__FILE__);
$additionalH = array('Camera', 'Nominativo' ,'Nato/a', 'Periodo', 'Note');

$root = realpath($_SERVER["DOCUMENT_ROOT"]);

include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
	protected $addH;
	protected $desMezzo;
	private $i=0;
	private $width=[15,50,20,50,0];

	
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

    public function setPiano($desPiano)
    {
    	$this->desPiano = $desPiano;
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
       $this->SetFont('helvetica', 'BI', '12px');
       $this->Cell(0, 0, $this->desPiano, 0, false, 'R', 0, '', 0, false, 'M', 'M');
 
       $this->SetY(34);
       $this->writeHTML('<hr><br>');

       $this->SetY(36);
       
       $this->SetFont('helvetica', 'BI', '10px');
       for($i=0; $i < count($this->addH) ; $i++) {
       	   $this->Cell($this->width[$i], 0,$this->addH[$i] , 0, false, 'L', 0, '', 0, false, 'M', 'M');
            }
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
$pdfH='';
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$date_format=ritorna_data_locale();
$index=0;
$ctrPosti=0;
$html='';

// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();
$sqlid_sottosezione=0;
$sqlanno=0;
$sqlold_piano=0;

// Dati risultanti dalla query
$sqlnome='';
$sqldata_nascita='';
$sqlid_categoria=0;
$sqlid_gruppo_par=0;
$sqlid_attpell=0;
$sqlid_prn=array();
$i_array=0;

$sqldal='';
$sqlal='';
$prn_format='L';

$outputCSV=false;
$target_CSV='../php/esporta_csv.php';
$id_prn = array();

 $intestazioneCSV = array("NOME","INDIRIZZO", "CAP", "CITTA" , "LUOGO DI NASCITA","DATA DI NASCITA",
                                           "CODICE FISCALE", "ID GRUPPO","ID CATEGORIA", "CAUSALE" , "IMPORTO",
                                            "DATA RICEVUTA", "NUMERO RICEVUTA", "DATA DI ORDINAMNETO",
                                             "TESSERA UNITALSI", "GRUPPO", "CATEGORIA");


$sqldes_dis='SCONOSCIUTA'; // descrizione disabilita'
$sqldes_par='SCONOSCIUTO'; // descrizione gruppo parrocchiale
$sqldes_cat='SCONOSCIUTO'; // descrizione categoria

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
/*
$sqlid_sottosezione=17; //DEBUG!
$sqlanno=2017;
$sqln_ricevuta=2;
*/

if(!$_POST) { // se NO post allora chiamata errata
   echo "$fname: Chiamata alla funzione errata<br>";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {
    	            if(is_array($value) && $key=='id_prn') {
    	            	   if($debug)
    	            	       print_r($value);
    	            	       
    	            	   $i_array=0;
      		            foreach($value as $value1) { // Carico array piani della struttura da stampare
                                    $sqlid_prn[$i_array] = $value1; 	
                                    $i_array++;
                 	               }
      				     continue;

    	               }
    	            else
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

      		                      case "id_attpell": // id viaggio/pellegrinaggio+data (separato da ",")
      					                    $sqlid_attpell = explode(",", $value)[0];
      					                    $sqldata_viaggio = explode(",", $value)[1];
      					                    break;

      		                       case "dal": // al
      					                      $sqldal = $value;
      					                       break;

      		                       case "al": // al
      					                      $sqlal = $value;
      					                       break;

      		                      case "prn_format": // Formato stampa
      					                     $prn_format = $value;
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

$sql = "SELECT descrizione_pellegrinaggio.descrizione,
                         SUBSTR(DATE_FORMAT(pellegrinaggi.al, '" . $date_format . "'),1,10) al
             FROM   pellegrinaggi,
                         descrizione_pellegrinaggio
             WHERE pellegrinaggi.id = " . $sqlid_attpell .
           " AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND     dal = '" . $sqldata_viaggio . "'";
           
$result = $conn->query($sql);
$row = $result->fetch_assoc();
      
$subintestazione = $row["descrizione"] . "     Periodo: " . substr($sqldata_viaggio,8,2) . '/' .
                                                                   substr($sqldata_viaggio,5,2) . '/'.
                                                                   substr($sqldata_viaggio,0,4) .
                                                                 " - " . $row["al"];
// SQL per camere
$sql = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                          SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,                         
                          anagrafica.telefono,
                          anagrafica.cellulare,
                          anagrafica.telefono_rif,
                          AL_camere.codice,
                          AL_piani.descrizione despiano,
                          AL_struttura.nome dess,
                          id_gruppo_par,
                          id_categoria, 
                          disabilita.descrizione desdis,
                          gruppo_parrocchiale.descrizione desp,
                          SUBSTR(DATE_FORMAT(AL_occupazione.dal, '" . $date_format . "'),1,10) dal,  
                          SUBSTR(DATE_FORMAT(AL_occupazione.al, '" . $date_format . "'),1,10) al,  
                          AL_piani.id
            FROM    AL_camere,
                          AL_piani,
                          AL_occupazione,
                          AL_struttura,
                          anagrafica
                          LEFT JOIN gruppo_parrocchiale
                          ON    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
                          LEFT JOIN categoria
                          ON    anagrafica.id_categoria = categoria.id
                          LEFT JOIN disabilita
                          ON    anagrafica.id_disabilita = disabilita.id
             WHERE  anagrafica.id = AL_occupazione.id_socio
             AND      AL_occupazione.id_attpell = " . $sqlid_attpell .
           " AND      AL_occupazione.id_camera = AL_camere.id
             AND      AL_piani.id = AL_camere.id_piano
             AND      AL_piani.id_struttura = AL_struttura.id
             AND      AL_piani.id IN(";
             
             for($i_array = 0; $i_array < count($sqlid_prn); $i_array++)
                   $sql .= $sqlid_prn[$i_array] . ", ";
                   
             $sql = rtrim($sql, ", ") . ") 
             ORDER BY 7, 6, 1";

if($debug)
    echo "$fname SQL = $sql<br>";;

if($outputCSV) {
	echo "<form name='CSV' action='" . $target_CSV . "' method='POST'>";
	echo "<input type='hidden' name='sqlsearch' value='" . htmlspecialchars($sql, $defCharsetFlags, $defCharset) . "'>";
   echo "<input type='hidden' name='extras' value='" . htmlentities(serialize($intestazioneCSV)) . "'>";
                	
	echo "</form>";
   echo "<script>this.document.CSV.submit();</script>";
   return;
}
// SQL per società (sede centrale)
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

$pdf = new MYPDF('Stampa prenotazioni struttura (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s') ,$subintestazione, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Prenotazioni struttura (pronto per la stampa)');
$pdf->SetSubject('Struttura/Viaggi');
$pdf->SetKeywords('Prenotazioni');

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
$pdf->setAdditionalH($additionalH);

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


$index=0;
$html = "<table>";
while($row = $result->fetch_assoc()) {
	       if($sqlold_piano == 0) {
	       	 $sqlold_piano = $row["id"];
              $resultH = $conn->query($sqlH);
              $rowH = $resultH->fetch_assoc();

              $pdfH = $rowH["nome"];
              $pdfH1 = $rowH["indirizzo"];
              $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
              $pdf->setPiano("Struttura " . $row["dess"]. " prenotazioni " . $row["despiano"]);
		        $pdf->AddPage();
	       }

	       if($sqlold_piano != $row["id"]) { // Totale Piano
	       	 $sqlold_piano = $row["id"];
              $pdf->writeHTML('<HR><br>');
              $pdf->SetFont ('helvetica', 'B', '10px' , '', 'default', true );
         
              $pdf->Multicell(0, 0, '(Totale #' . sprintf('%03d', $index) . " )", 0, 'L', false,0, '','', true);
              $index=0;
              $ctrPosti=0;
              $pdf->setPiano("Struttura " . $row["dess"]. " prenotazioni " . $row["despiano"]);
              $pdf->AddPage();
	          }

	       $sqlnome = $row["nome"];
	       
	       if(strlen($sqlnome) > 25)
	          $sqlnome = substr($sqlnome, 0, 25);
	       $sqldata_nascita = $row["dt_nas"];
	       $sqldes_dis = $row["desdis"];
	       $sqldes_piano = $row["despiano"];
	       $sqldes_par = $row["desp"];
	       $sqlcamera = $row["codice"];

	       $html .= "<tr>";
	       $html .= "<td>" . $sqlnome . "</td>";
	       $html .= "<td>Nato il: " . substr($sqldata_nascita, 0, 10);
	       $html .= "<td>Piano: " . $sqldes_piano ."</td>";
	       $html .= "<td>Camera: " . $row["codice"] ."</td>";
	       $html .= "<td>gruppo: " . $sqldes_par ."</td></tr>";
	       $html .=  "<tr><td colspan=\"4\"><hr></td></tr>";

          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
          $txtPrint = $sqlnome . " (" . substr($sqldata_nascita,0,10). 
                                        "  Categoria: " . $sqldes_cat . " Gruppo: " . $sqldes_par . ")";
          $pdf->MultiCell(12, 0,$sqlcamera , 0, 'L', false,0, '','', true);
          $pdf->MultiCell(50, 0,$sqlnome , 0, 'L', false,0, '','', true);

          $pdf->SetFont ('helvetica', 'N', '8px' , '', 'default', true );
          $pdf->MultiCell(17, 0,substr($sqldata_nascita, 0, 10) , 0, 'L', false,0, '','', true);
          $pdf->MultiCell(50, 0,$row["dal"] ." " . $row["al"] , 0, 'L', false,0, '','', true);
//          $pdf->MultiCell(23, 0,$row["cellulare"] , 0, 'L', false,0, '','', true);
  //        $pdf->MultiCell(23, 0, , 0, 'L', false,0, '','', true);

          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
//          $pdf->MultiCell(6, 0,$row["n_posti"], 0, 'R', false,0, '','', true);
          $pdf->SetFont ('helvetica', 'N', '8px' , '', 'default', true );
//          $pdf->MultiCell(40, 0,$row["dsez"], 0, 'L', false,0, '','', true);
//          $pdf->MultiCell(50, 0,$sqldes_par, 0, 'L', false,0, '','', true);
          $pdf->SetX(150);
          $pdf->MultiCell(0, 0,$sqldes_dis, 0, 'L', false,1, '','', true);
          $index++;
        //  $ctrPosti += $row["n_posti"];
 }
 
   if($index > 0) { // Stampo totale
       $pdf->writeHTML('<HR><br>');
       $pdf->SetFont ('helvetica', 'B', '10px' , '', 'default', true );
         
       $pdf->Multicell(0, 0, '(Totale #' . sprintf('%03d', $index) . " )", 0, 'L', false,0, '','', true);
        if($debug)
          echo $html;
       else
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
          $pdf->Output('Prenotazioni_struttura.pdf', 'I');

      }
   $conn->close();
//============================================================+
// END OF FILE
//============================================================+
