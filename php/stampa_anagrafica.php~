<?php
/****************************************************************************************************
*
*  Stampa delle anagrafiche presenti nel database
*
*  @file stampa_anagrafica.php
*  @abstract Genera file PDF con e anagrafiche presenti nel DB in formato A4
*  @author Luca Romano
*  @version 1.0
*  @since 2017-02-03
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
config_timezone();
define('MYPDF_PAGE_ORIENTATION', 'L');


// Include the main TCPDF library (search for installation path).
//require_once('/tcpdf/tcpdf.php');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include $root . "/tcpdf/tcpdf.php";
define("MYPDF_MARGIN_TOP", 35);

$sott_app = ritorna_sottosezione_pertinenza();

$debug=false;
$fname=basename(__FILE__);
$sqlid_sottosezione=0;
$sqlid_gruppo_par=0;
$sqlsuddivisione=false;
$effettivo=false;
$catID=0;

$sqlold_s=0;
$sqlold_p=0;
$fromSearch=false; // Verifica se richiamato da ricerca dati
$sqlSearch='';

$matches = array(); //create array
$pattern = '/[A-Za-z0-9-]*./'; //regex for pattern of first host part (i.e. www)
$pathFile="/img/";

$logo='Logo_UNITALSI.jpg';
$txtF='Stampa anagrafica (Rev 1.0) Generato il: '; // Footer
$pdfH='';
$date_format=ritorna_data_locale();
$index=0;
$html='';
// Check sessione
if(!check_key())
   return;

$idSoc = ritorna_societa_id();
$userid = session_check();

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

      		                       case "id_gruppo_par": // gruppo parrocchiale
      					                      $sqlid_gruppo_par = $value;
      					                       break;

      		                      case "suddivisione": // richiesta suddivisione per gruppo parrocchiale
      					                    $sqlsuddivisione = $value;
      				                        break;

      		                      case "effettivo": // Possibili valori 0 = Tutti, 1 = Effettivo, 2 = Ausiliario
      					                    $effettivo = $value;
      				                        break;

      		                      case "id_categoria": // richiesto categoria specifica
      					                    $catID = $value;
      				                        break;
 
      		                      case "searchTxt": // campo di ricerca (ignoring)
      				                        break;
      	
      		                       default: // OPS!
      				                        //echo "UNKNOWN key = ".$key;
      				                        //return;
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
                          CONCAT(TRIM(anagrafica.indirizzo), ' - ',
                                        anagrafica.cap,' ',
                                        anagrafica.citta) indirizzo,
                                        anagrafica.luogo_nascita,
                                        anagrafica.telefono,
                                        anagrafica.cellulare,
                                        anagrafica.email,
                                        anagrafica.cf, anagrafica.id_gruppo_par,
                                        anagrafica.id_categoria,
                                        SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                                        gruppo_parrocchiale.descrizione desp,
                                        categoria.descrizione descat,
                                        anagrafica.socio_effettivo
             FROM   anagrafica LEFT JOIN gruppo_parrocchiale
                                          ON    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
                                          LEFT JOIN categoria
                                          ON    anagrafica.id_categoria = categoria.id
             WHERE 1";

if($sqlid_sottosezione > 0)
    $sql .= " AND anagrafica.id_sottosezione = " . $sqlid_sottosezione;

if($sqlid_gruppo_par > 0)
    $sql .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo_par;

switch($effettivo) {
            case 1: // Effettivo
                       $sql .= " AND anagrafica.socio_effettivo = 1";
                       break;
            
            case 2: // Non Effettivo
                       $sql .= " AND anagrafica.socio_effettivo != 1";
                       break;
}

if($catID > 0)
    $sql .= " AND anagrafica.id_categoria = " . $catID;

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
$pdf = new MYPDF($txtF. ritorna_data_attuale(),MYPDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco anagrafica (pronto per la stampa)');
$pdf->SetSubject('Anagrafica');
$pdf->SetKeywords('Anagrafica');

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
          $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
	       if($rowH["id"] != $sqlold_s) {
	       	 $pdf->AddPage();
	       	 $sqlold_s = $rowH["id"];
             }

          $sqlq = $sql . " AND anagrafica.id_sottosezione = " . $rowH["id"];
	       
	       if($sqlsuddivisione) {
	       	 $sqlq .= " ORDER BY desp, nome";
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
	                 	  $sqlold_p = $row["id_gruppo_par"];
	                     if($sqlsuddivisione || $sqlid_gruppo_par != 0) {
	       	               $pdf->SetFont ('helvetica', 'B', '12px' , '', 'default', true );
	       	               $pdf->MultiCell(0, 0, 'Gruppo : ' . $row["desp"], 0, 'L', false,1, 10,$pdf->getY(), true);
                          }	
	       	           //$pdf->writeHTML('<hr>');

                        $html = "<table><tr>";
                       }	

	                 if(($row["id_gruppo_par"] != $sqlold_p) && $sqlsuddivisione) {
	       	           $pdf->AddPage();
	       	           $sqlold_p = $row["id_gruppo_par"];
	       	           $pdf->SetFont ('helvetica', 'B', '12px' , '', 'default', true );
	       	           $pdf->MultiCell(0, 0, 'Gruppo : ' . $row["desp"], 0, 'L', false,1, 10,$pdf->getY(), true);

                       }
	                 $html .= "<td><p>" . $row["nome"] . " ";
	                 $html .= $row["indirizzo"]. " ";
	                 $html .= "Nato il: " . substr($row["dt_nas"], 0, 10). "<br>";
	
	                 if(($index % 2 == 0) && ($index > 0)) {
	                      $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
                        }

                     $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
                     $txtPrint = $row["nome"] . " (" . $row["dt_nas"]. 
                                        "  Categoria: " . $row["descat"];
                      if($row["socio_effettivo"])
                         $txtPrint .= '/SOCIO EFFETTIVO';
                      $txtPrint .= ')';
                     $pdf->MultiCell(130, 0,substr($txtPrint,0,160) , 0, 'L', false,0, 10,'', true);
                     $pdf->MultiCell('', '',$row["indirizzo"], 0, 'L', false,1, $pdf->getX()+1,'', false);
   
 //                    $pdf->SetFont ('helvetica', '' , '8px' , '', 'default', true );
  //                   $pdf->MultiCell(17, '', 'Nato/a il: ' , 0, 'L', false,0, '','', true);

  //                   $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
  //                   $pdf->MultiCell(18, '',$row["dt_nas"], 0, 'L', false,0, '','', true);

     //                $pdf->SetFont ('helvetica', '' , '8px' , '', 'default', true );
     //                $pdf->MultiCell(5, '', 'a: ' , 0, 'L', false,0, '','', true);

        //             $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
        //             $pdf->MultiCell('', '',$row["luogo_nascita"], 0, 'L', false,1, '','', true);

              //       $pdf->SetFont ('helvetica', '' , '8px' , '', 'default', true );
                 //    $pdf->MultiCell(17, '','Categoria: ', 0, 'L', false,0, '','', true);
 
                 //    $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
                    // $pdf->MultiCell(30, '',$row["descat"], 0, 'L', false,0, '','', true);

//                     $pdf->SetFont ('helvetica', '' , '8px' , '', 'default', true );
//                     $pdf->MultiCell(9, '','C.F.: ', 0, 'L', false,0, '','', true);

 //                    $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
                     //$pdf->MultiCell(32, '',$row["cf"], 0, 'L', false,1, '','', true);
                     
                     if(!$sqlsuddivisione && $sqlid_gruppo_par == 0) {
                   //       $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
                      //)    $pdf->MultiCell(32, '',$row["cf"], 0, 'L', false,0, '','', true);

                          $pdf->SetFont ('helvetica', '' , '8px' , '', 'default', true ); 
                          $pdf->MultiCell(13, '','Gruppo : ' , 0, 'L', false,0, '','', true);

                           $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
                           $pdf->MultiCell('', '',$row["desp"], 0, 'L', false,1, '','', true);
                           }
                        //else {
                           // $pdf->SetFont ('helvetica', 'B' , '8px' , '', 'default', true );
                         //   $pdf->MultiCell(0, '',$row["cf"], 0, 'L', false,1, '','', true);
                          // }
                     $pdf->SetY($pdf->GetY() + 1);
                     $index++;
 	     }
}
$conn->close();

if($index > 0)
   if($debug)
      echo $html;
    else
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
      $pdf->Output('Stampa_anagrafica.pdf', 'I');
    
//============================================================+
// END OF FILE
//============================================================+