<?php
/****************************************************************************************************
*
*  Stampa l'elenco dei soci associati ai mezzi
*
*  @file stampa_mezzi.php
*  @abstract Genera file PDF con l'elenco dei soci associato al mezzo
*  @author Luca Romano
*  @version 1.0
*  @since 2017-03-07
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
$additionalH = array('Nominativo' ,'Nato/a', 'Riferimenti',  'Posti', 'Note');
//                                  'Gruppo', 'Categoria');


$root = realpath($_SERVER["DOCUMENT_ROOT"]);

include $root . "/tcpdf/tcpdf.php";

class MYPDF extends TCPDF {
	private $par;
	protected $subintestazione;
	protected $addH;
	protected $desMezzo;
	private $i=0;

	
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

    public function setMezzo($desMezzo)
    {
    	$this->desMezzo = $desMezzo;
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
       $this->Cell(0, 0, $this->desMezzo, 0, false, 'R', 0, '', 0, false, 'M', 'M');
 
       $this->SetY(34);
       $this->writeHTML('<hr><br>');

       $this->SetY(36);
       
       $this->SetFont('helvetica', 'BI', '10px');
       for($i=0; $i < count($this->addH) ; $i++) {
       	    switch($i) {
       	    	    case 0:       	    	          
       	                $this->Cell(50, 0,$this->addH[$i] , 0, false, 'L', 0, '', 0, false, 'M', 'M');
       	                break;

       	    	    case 1:
       	                $this->Cell(17, 0,$this->addH[$i] , 0, false, 'C', 0, '', 0, false, 'M', 'M');
       	                break;

       	    	    case 2:
       	                $this->Cell(69, 0,$this->addH[$i] , 0, false, 'C', 0, '', 0, false, 'M', 'M');
       	                break;

       	    	    case 3:
       	                $this->Cell(6, 0,$this->addH[$i] , 0, false, 'R', 0, '', 0, false, 'M', 'M');
       	                break;

       	    	    case 4:
       	    	          $this->SetX(159);
       	                $this->Cell(0, 0,$this->addH[$i] ,0, false, 'L', 0, '', 0, false, 'M', 'M');
       	                break;
       	              }
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
$sqlold_mezzo=0;

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
   echo "$fname: Chiamata alla funzione errata";
   return;
}
else {
    $kv = array();
    foreach ($_POST as $key => $value) {
    	            if(is_array($value) && $key=='id_prn') {
    	            	   if($debug)
    	            	       print_r($value);
    	            	       
    	            	   $i_array=0;
      		            foreach($value as $value1) {
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

$sql = "SELECT descrizione_pellegrinaggio.descrizione
             FROM   pellegrinaggi,
                          descrizione_pellegrinaggio
             WHERE pellegrinaggi.id = " . $sqlid_attpell .
           " AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND     (dal = '" . $sqldata_viaggio . "' OR al ='" . $sqldata_viaggio . "')";
           
$result = $conn->query($sql);
$row = $result->fetch_assoc();
      
$subintestazione = $row["descrizione"] . " Del: " . substr($sqldata_viaggio,8,2) . '/' .
                                                                   substr($sqldata_viaggio,5,2) . '/'.
                                                                   substr($sqldata_viaggio,0,4);
// SQL per mezzi
$sql = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                          SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,                         
                          anagrafica.telefono,
                          anagrafica.cellulare,
                          anagrafica.telefono_rif,
                          mezzi_disponibili.descrizione desm,
                          mezzi_disponibili.capienza,
                          mezzi_disponibili.id,
                          mezzi_detail.n_posti,
                          id_gruppo_par,
                          id_categoria, 
                          disabilita.descrizione desdis,
                          gruppo_parrocchiale.descrizione desp
            FROM    
                          mezzi_disponibili,
                          mezzi_detail,
                          anagrafica
                          LEFT JOIN gruppo_parrocchiale
                          ON    anagrafica.id_gruppo_par = gruppo_parrocchiale.id
                          LEFT JOIN categoria
                          ON    anagrafica.id_categoria = categoria.id
                          LEFT JOIN disabilita
                          ON    anagrafica.id_disabilita = disabilita.id

             WHERE  anagrafica.id = mezzi_detail.id_socio
             AND      mezzi_detail.id_attpell = " . $sqlid_attpell .
           " AND      mezzi_disponibili.id_sottosezione = " . $sqlid_sottosezione .
           " AND      mezzi_detail.id_mezzo = mezzi_disponibili.id
             AND      mezzi_detail.data_viaggio = '" . $sqldata_viaggio . "'
             AND      mezzi_detail.id_mezzo IN(";
             
             for($i_array = 0; $i_array < count($sqlid_prn); $i_array++)
                   $sql .= $sqlid_prn[$i_array] . ", ";
                   
             $sql = rtrim($sql, ", ") . ") 
             ORDER BY 6, 8, 1";

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

$pdf = new MYPDF('Stampa composizione mezzi (Rev 1.0) Generato il: ' . date('d/m/Y H:i:s') ,$subintestazione, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Composizione mezzi (pronto per la stampa)');
$pdf->SetSubject('Elenco ricevute');
$pdf->SetKeywords('Ricevute');

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
	       if($sqlold_mezzo == 0) {
	       	 $sqlold_mezzo = $row["id"];
              $resultH = $conn->query($sqlH);
              $rowH = $resultH->fetch_assoc();

              $pdfH = $rowH["nome"];
              $pdfH1 = $rowH["indirizzo"];
              $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
              if($row["capienza"] == 0)
                  $txt = 'N/A';
              else
                   $txt = $row["capienza"];
              $pdf->setMezzo("Composizione " . $row["desm"] . " Capienza: " . $txt);
		        $pdf->AddPage();
	       }

	       if($sqlold_mezzo != $row["id"]) { // Totale mezzo
	       	 $sqlold_mezzo = $row["id"];
              $pdf->writeHTML('<HR><br>');
              $pdf->SetFont ('helvetica', 'B', '10px' , '', 'default', true );
         
              $pdf->Multicell(0, 0, '(Partecipanti/Posti #' . sprintf('%03d', $index) . "/#" . sprintf('%03d', $ctrPosti). " )", 0, 'L', false,0, '','', true);
              $index=0;
              $ctrPosti=0;
              $pdf->setMezzo("Composizione " . $row["desm"] . " Capienza: " . $row["capienza"]);
              $pdf->AddPage();
	          }

	       $sqlnome = $row["nome"];
	       $sqldata_nascita = $row["dt_nas"];
	       $sqldes_dis = $row["desdis"];
	       $sqldes_par = $row["desp"];

	       $html .= "<tr>";
	       $html .= "<td>" . $sqlnome . "</td>";
	       $html .= "<td>Nato il: " . substr($sqldata_nascita, 0, 10);
	       $html .= "<td>categoria: " . $sqldes_cat ."</td>";
	       $html .= "<td>gruppo: " . $sqldes_par ."</td></tr>";
	       $html .=  "<tr><td colspan=\"4\"><hr></td></tr>";

          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
          $txtPrint = substr($sqlnome, 0, 26);// . " (" . substr($sqldata_nascita,0,10). 
                                       // "  Categoria: " . $sqldes_cat . " Gruppo: " . $sqldes_par . ")";
          $pdf->MultiCell(50, 0,$txtPrint , 0, 'L', false,0, '','', true);

          $pdf->SetFont ('helvetica', 'N', '8px' , '', 'default', true );
          $pdf->MultiCell(17, 0,substr($sqldata_nascita, 0, 10) , 0, 'L', false,0, '','', true);
          $pdf->MultiCell(70, 0,$row["telefono"] ." " . $row["cellulare"] . " " .$row["telefono_rif"] , 0, 'L', false,0, '','', true);
//          $pdf->MultiCell(23, 0,$row["cellulare"] , 0, 'L', false,0, '','', true);
  //        $pdf->MultiCell(23, 0, , 0, 'L', false,0, '','', true);

          $pdf->SetFont ('helvetica', 'B', '8px' , '', 'default', true );
          $pdf->MultiCell(6, 0,$row["n_posti"], 0, 'R', false,0, '','', true);
          $pdf->SetFont ('helvetica', 'N', '8px' , '', 'default', true );
//          $pdf->MultiCell(40, 0,$row["dsez"], 0, 'L', false,0, '','', true);
//          $pdf->MultiCell(50, 0,$sqldes_par, 0, 'L', false,0, '','', true);
          $pdf->SetX(150);
          $pdf->MultiCell(0, 0,$sqldes_dis, 0, 'L', false,1, '','', true);
          $index++;
          $ctrPosti += $row["n_posti"];
 }
 
   if($index > 0) { // Stampo totale
       $pdf->writeHTML('<HR><br>');
       $pdf->SetFont ('helvetica', 'B', '10px' , '', 'default', true );
         
       $pdf->Multicell(0, 0, '(Partecipanti/Posti #' . sprintf('%03d', $index) . "/#" . sprintf('%03d', $ctrPosti). ")", 0, 'L', false,0, '','', true);
        if($debug)
          echo $html;
       else
   //$pdf->writeHTML($html, true, false, true, false, '');
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
          $pdf->Output('Composizione_mezzi.pdf', 'I');

      }
   $conn->close();
//============================================================+
// END OF FILE
//============================================================+
