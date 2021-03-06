<?php
/****************************************************************************************************
*
*  Stampa i partecipanti al viaggio/pellegrinaggio
*
*  @file stampa_partecipanti.php
*  @abstract Genera file PDF con anagrafiche partecipanti all'attivita' presenti nel DB in formato Landscape
*  @author Luca Romano
*  @version 1.0
*  @since 2017-05-02
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
require_once('../php/ritorna_tessera_rinnovata.php');

//require_once('../php/ritorna_tessera_rinnovata.php');
define('EURO',chr(128));
define("MYPDF_MARGIN_TOP", 35);
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
       
       $this->SetY(33);

       $this->SetFont('helvetica', 'BI', '10px');
       for($i=0;$i < count($this->subintestazione);$i++) {
             $this->Cell($this->subPos[$i], 0, $this->subintestazione[$i], 0, false, 'L', 0, '', 0, false, 'M', 'M');
            }
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

// Fine class TCPDF
$date_format=ritorna_data_locale();
$sott_app = ritorna_sottosezione_pertinenza();

$suddGroup=false; // Suddivisione per gruppo
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

// Se richiesto esportazione CSV
$outputCSV=false;
$target_CSV='../php/esporta_csv.php';
$csv_filename = 'exportdata.csv';

$intestazioneCSV = array("NOME","DATA NASCITA", "SESSO", "VIAGGIO/PELLEGRINAGGIO", "DAL", "AL", 
                                          "GRUPPO", "DISABILITA", "BIANCHERIA", "CELLULARE");

$sqlid_sottosezione=0;
$sqlid_attpell=0;
$sqlanno=0;
$sqltipo='-';
$sqlExec='';
$sqldatacheck=null;
$sqlsex='T';
$sqlid_categoria=0;
$descat=null;
$sqlcellulare='B'; // Se 'B' stampo numero biancheria/'C' numero di cellulare

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
$condSel=''; // Select aggiuntiva

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
$subInt=array("Nominativo","Permanenza","Sesso","Gruppo","DNA","Disabilita'", "Biancheria");

// Lunghezza campi intestazione
$subPos=array(70, 40, 12, 60, 20, 50, 10);

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

      		                       case "id_gruppo": // id gruppo parrocchiale
      					                      $sqlid_gruppo= $value;
      					                       break;

      		                       case "suddGroup": // Ordina per gruppo
      					                      $suddGroup= $value;
      					                       break;

      		                      case "anno": // anno riferimento
      					                    $sqlanno = $value;
      				                        break;

      		                      case "prn_format": // formato stampa
      					                    $prn_format = $value;
      				                        break;
 
      		                      case "sex": // Sesso
      		                               $sqlsex = $value;
      				                        break;
 
      		                      case "id_categoria": // Categoria
      		                               $sqlid_categoria = $value;
      				                        break;
 
      		                      case "cellulare": // Stampo il cellulare
      		                               $sqlcellulare = $value;
      		                               
      		                               if($sqlcellulare == 'C') // Stampo il cellulare
      		                                  $subInt[6] = 'Cellulare';
      				                        break;
 
      		                      case "fileCSV": // Nome file output
      		                              $csv_filename=$value;
      				                        break;
 
      		                      case "CSV": // Richiesta Output CSV
      		                              $outputCSV=true;
      				                        break;
 
      		                      case "CSV_x": // CSV_X (ignoring)
      				                        break;
 
      		                      case "CSV_y": // CSV_Y (ignoring)
      				                        break;

      		                      case "x": // X (ignoring)
      				                        break;

      		                      case "y": // Y (ignoring)
      				                        break;
      	
      		                       default: // OPS!
      				                        echo "UNKNOWN key = ".$key;
      				                        return;
      				                        break;
      				              }  
                  $index++;
                }
             $table_name = 'pellegrinaggi';
             $txtF = "Stampa elenco partecipanti (Rev 1.0) Generato il: " . date('d/m/Y H:i:s'); 
	
}


// SQL per sottosezione
$sqlsottosezione= "SELECT id, nome
                                 FROM   sottosezione";

if($sqlid_sottosezione > 0)
    $sqlsottosezione .= " WHERE id = " . $sqlid_sottosezione;
    
$sqlsottosezione .= " ORDER BY 2";

// SQL per stampa
$sqlP = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                            pellegrinaggi.id id_attpell,
                            CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                            SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                            SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                            SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                            pellegrinaggi.dal dal_order,
                            anagrafica.sesso,
                            anagrafica.n_biancheria,
                            anagrafica.cellulare,
                            anagrafica.id_disabilita,
                            gruppo_parrocchiale.descrizione des_par,
                            gruppo_parrocchiale.id id_par,
                            pellegrinaggi.id,
                            anagrafica.id ida,
                            anagrafica.ts,
                            SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) sdal,
                            SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) sal,
                            attivita_detail.id idd";
// SQL per CSV
$sqlC = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                            SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                            anagrafica.sesso,
                            CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                            SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                            SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                            gruppo_parrocchiale.descrizione des_par,
                            disabilita.descrizione,
                            anagrafica.n_biancheria,
                            anagrafica.cellulare
               FROM   anagrafica
                           LEFT JOIN disabilita
                           ON disabilita.id = anagrafica.id_disabilita,
                            attivita_detail,
                            pellegrinaggi,
                            descrizione_pellegrinaggio,
                            gruppo_parrocchiale
             WHERE anagrafica.id = attivita_detail.id_socio
             AND     attivita_detail.id_attpell = pellegrinaggi.id
             AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND     attivita_detail.tipo = 'V'
             AND     anagrafica.id_gruppo_par = gruppo_parrocchiale.id";

$sqlV = "SELECT CONCAT(anagrafica.cognome,' ',anagrafica.nome) nome,
                            pellegrinaggi.id id_attpell,
                            CONCAT(descrizione_pellegrinaggio.descrizione, ' ' ,IFNULL(CONCAT(' (' , pellegrinaggi.descrizione,')'),'')) desa,
                            SUBSTR(DATE_FORMAT(data_nascita, '" . $date_format . "'),1,10) dt_nas,
                            SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                            SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                            pellegrinaggi.dal dal_order,
                            anagrafica.sesso,
                            anagrafica.n_biancheria,
                            anagrafica.cellulare,
                            anagrafica.id_disabilita,
                            gruppo_parrocchiale.descrizione des_par,
                            gruppo_parrocchiale.id id_par,
                            pellegrinaggi.id,
                            anagrafica.id ida,
                            anagrafica.ts,
                            SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) sdal,
                            SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) sal,
                            attivita_detail.id idd
               FROM    anagrafica,
                            attivita_detail,
                            pellegrinaggi,
                          descrizione_pellegrinaggio,
                          gruppo_parrocchiale
             WHERE anagrafica.id = attivita_detail.id_socio
             AND     attivita_detail.id_attpell = pellegrinaggi.id
             AND     pellegrinaggi.id_attpell = descrizione_pellegrinaggio.id
             AND     attivita_detail.tipo = 'V'
             AND     anagrafica.id_gruppo_par = gruppo_parrocchiale.id";
 
if($outputCSV)
    $sqlExec = $sqlC;
else
    $sqlExec = $sqlV;

if($sqlid_attpell > 0) {
    $sqlExec .=  " AND pellegrinaggi.id = " . $sqlid_attpell;
    }

if($sqlanno > 0) {
    $sqlExec .= " AND pellegrinaggi.anno = " . $sqlanno;
    }

if($sqlid_gruppo > 0) {
    $sqlExec .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
    }

if($sqlsex != 'T') {
    $sqlExec .= " AND anagrafica.sesso = '" . $sqlsex ."'";
    }

if($sqlid_categoria > 0) {
    $sqlExec .= " AND anagrafica.id_categoria = " . $sqlid_categoria;
    $sql = "SELECT descrizione
                 FROM categoria
                 WHERE id = $sqlid_categoria";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $descat = $row["descrizione"];

    }

// SQL per intestazione
$sqlH = "SELECT CONCAT(sezione.nome,' - Sottosezione di ',sottosezione.nome) nome,
                            CONCAT(sottosezione.cap,' ', sottosezione.citta,
                                          ' (', province.sigla, ') - ', sottosezione.indirizzo,' - Tel. ',
                                          IFNULL(sottosezione.telefono,'*'), ' - Fax ', IFNULL(sottosezione.fax,'*')) indirizzo,
                                          sottosezione.id id
               FROM   sezione, sottosezione, province
               WHERE sottosezione.id_provincia = province.id
               AND     sezione.id = sottosezione.id_sezione";

if($sqlid_sottosezione > 0) {
    $sqlH .= " AND sottosezione.id = " . $sqlid_sottosezione;
    }
             
// create new PDF document
$pdf = new MYPDF($txtF, $subInt ,$subPos, $prn_format, PDF_UNIT, PDF_PAGE_FORMAT, false, 'UTF-8', false);

set_time_limit(120); // Questo per PDF che e' lento, la query e' una lippa!
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Luca Romano');
$pdf->SetTitle('Elenco partecipanti (pronto per la stampa)');
$pdf->SetSubject('Anagrafica, Attivita/Viaggi');
$pdf->SetKeywords('Anagrafica, Attivita/Viaggi');

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

// Accedo al DB (Dati della sottosezione)
if($debug)
    echo "$fname SQL Header: $sqlH<br>";
$resultH = $conn->query($sqlH);
$index=0;
$rowH = $resultH->fetch_assoc();

$pdfH = $rowH["nome"];
$pdfH1 = $rowH["indirizzo"];

$sqlq = $sqlExec; // . " AND " . $table_name . ".id_sottosezione = " . $rowH["id"];
          
if($suddGroup)
	$sqlq .= " ORDER BY des_par, nome";
else
	$sqlq .= " ORDER BY nome";

if($debug)
   echo "$fname SQL = $sqlq<br>";

if($outputCSV) {
	echo "<form name='CSV' action='" . $target_CSV . "' method='POST'>";
   echo "<input type='hidden' name='fileCSV' value='" . $csv_filename . "'>";
	echo "<input type='hidden' name='sqlsearch' value='" . htmlspecialchars($sqlq, $defCharsetFlags, $defCharset) . "'>";
   echo "<input type='hidden' name='extras' value='" . htmlentities(serialize($intestazioneCSV)) . "'>";
                	
	echo "</form>";
   echo "<script>this.document.CSV.submit();</script>";
   return;
}
	       
if($fromSearch)
   $sqlq = $sqlSearch;

$oldid_grp=0;
$desGrp=' ';
               
if($debug)
    echo "$fname: SQL Partecipanti $sqlq<br>";          

$result = $conn->query($sqlq);
// Accedo al DB (ciclo per le anagrafiche)
while($row = $result->fetch_assoc()) {
	       if($index == 0) { // Prima riga, intestazione
	       	 $oldid_grp = $row["id_par"];

	           if($suddGroup) { // Memorizzo il gruppo
	              $desGrp = $row["des_par"];
                }
	           $txt = "Partecipanti - " .$row["desa"] . "       Periodo: " . $row["dal"] . " - " . $row["al"];

	           if($descat)
	               $txt .= "     Categoria: $descat";
	               
              $pdf->setAdditionalH($txt);
              $pdf->SetHeaderData($logo, PDF_HEADER_LOGO_WIDTH, $pdfH, $pdfH1, array(0,0,0), array(0,0,0));
              $pdf->AddPage();
             }	

	       if($suddGroup && ($oldid_grp != $row["id_par"])) { // Cambio gruppo
	       // Totalizzo per gruppo
	           $pdf->writeHTML('<hr>');
        
              $sql = "SELECT COUNT(*) ctr,
                           SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                           SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                           attivita_detail.dal dal_order
                           FROM   anagrafica,
                                       attivita_detail
                           WHERE anagrafica.id = attivita_detail.id_socio
                           AND     attivita_detail.tipo = 'V'
                           AND     attivita_detail.id_attpell = $sqlid_attpell
                           AND     anagrafica.id_gruppo_par = $oldid_grp";

              if($sqlsex != 'T') {
                  $sql .= " AND anagrafica.sesso = '" . $sqlsex ."'";
                 }

              if($sqlid_categoria > 0) {
                 $sql .= " AND anagrafica.id_categoria = $sqlid_categoria";
                }

              $sql .= "  GROUP BY 2,3,4
                            ORDER BY 4";

              $rGrpTot = $conn->query($sql);
              while($rGrp = $rGrpTot->fetch_assoc()) {
                        $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
                        $pdf->Cell(100, 0,"Partecipanti " . $desGrp . " ". $rGrp["dal"] . " - " . $rGrp["al"], 0, 0, 'L');
                        $pdf->SetFont ('helvetica', 'B', '12px' , '', true );
                        $pdf->Cell(10, 0, $rGrp["ctr"],  0, 0, 'R');
                      }
              $rGrpTot->close();
	       	 $oldid_grp = $row["id_par"];
	           $desGrp = $row["des_par"];
              $pdf->AddPage();

	          }

	       $html .= "<td><p>" . $row["nome"] . " ";
	       $html .= "Nato il: " . substr($row["dt_nas"], 0, 10). "<br>";
	
	       if(($index % 2 == 0) && ($index > 0)) {
	            $html .=  "</tr><tr><td colspan=\"2\"><hr></td></tr><tr>";
               }
                         
          $textToPrint = substr($row["nome"], 0, 30);
          $sqldatacheck = $row["dal_order"];

           if(!ritorna_rinnovo($conn, $row["ida"], substr($sqldatacheck,0,4)))
               $textToPrint .= " (*)";

           $pdf->SetFont ('helvetica', 'B', '9px' , '', true );
           $pdf->MultiCell($subPos[0], 0,$textToPrint, 0, 'L', false,0, 10,$pdf->getY(), true);
                         
           $textToPrint = $row["sdal"] . " - " . $row["sal"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell($subPos[1], '',$textToPrint, 0, 'L', false,0, '','', true);

           $textToPrint = $row["sesso"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell($subPos[2], '',$textToPrint, 0, 'R', false,0, '','', true);

           $textToPrint = $row["des_par"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell($subPos[3], '',$textToPrint, 0, 'L', false,0, '','', true);
                     
           $textToPrint = $row["dt_nas"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell($subPos[4], '',$textToPrint, 0, 'L', false,0, '','', true);
           
           if($row["id_disabilita"] && $row["id_disabilita"] > 0) { // Seleziono tipo di disabilità
              $sql = "SELECT descrizione
                           FROM   disabilita
                           WHERE id = " . $row["id_disabilita"];
                           
               $r = $conn->query($sql);
               $rr = $r->fetch_assoc();
               $textToPrint = $rr["descrizione"];
              }
            else 
               $textToPrint = " ";
                     
           //$textToPrint = $row["ts"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell($subPos[5], '',$textToPrint, 0, 'L', false,0, '','', true);
                     
           $textToPrint = $row["n_biancheria"];
           if($sqlcellulare == 'C')
               $textToPrint = $row["cellulare"];
           $pdf->SetFont ('helvetica', 'N' , '9px' , '', '' );
           $pdf->MultiCell(0, '',$textToPrint, 0, 'L', false,1, '','', true);
           $ctrViaggio +=1;
           $index++;
 	     }

if($index > 0)
   if($debug)
      echo $html;
    else {
    	 // Se richiesto suddivisione stampo totali ultimo gruppo
    	 	  if($suddGroup) {
	           $pdf->writeHTML('<hr>');
        
              $sql = "SELECT COUNT(*) ctr,
                           SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                           SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                           attivita_detail.dal dal_order
                           FROM   anagrafica,
                                       attivita_detail
                           WHERE anagrafica.id = attivita_detail.id_socio
                           AND     attivita_detail.tipo = 'V'
                           AND     attivita_detail.id_attpell = $sqlid_attpell
                           AND     anagrafica.id_gruppo_par = $oldid_grp";

              if($sqlsex != 'T') {
                  $sql .= " AND anagrafica.sesso = '" . $sqlsex ."'";
                 }

              if($sqlid_categoria > 0) {
                 $sql .= " AND anagrafica.id_categoria = $sqlid_categoria";
                }

              $sql .= "  GROUP BY 2,3,4
                            ORDER BY 4";

              $rGrpTot = $conn->query($sql);
              while($rGrp = $rGrpTot->fetch_assoc()) {
                        $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
                        $pdf->Cell(100, 0,"Partecipanti " . $desGrp . " ". $rGrp["dal"] . " - " . $rGrp["al"], 0, 0, 'L');
                        $pdf->SetFont ('helvetica', 'B', '12px' , '', true );
                        $pdf->Cell(10, 0, $rGrp["ctr"],  0, 1, 'R');
                      }
              $rGrpTot->close();
	           $pdf->writeHTML('<br>');
             }  // Fine stampa totali gruppo se richiesto
// ---------------------------------------------------------
// Stampo i totali Generali
        $pdf->writeHTML('<hr>');
        
        $sql = "SELECT COUNT(*) ctr,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.dal,'" . $date_format ."'),1,10) dal,
                                 SUBSTRING(DATE_FORMAT(attivita_detail.al,'" . $date_format ."'),1,10) al,
                                 attivita_detail.dal dal_order,
                                 attivita_detail.al al_order
                     FROM   anagrafica,
                                 attivita_detail
                     WHERE anagrafica.id = attivita_detail.id_socio
                     AND     attivita_detail.tipo = 'V'
                     AND     attivita_detail.id_attpell = $sqlid_attpell";

        if($sqlid_gruppo > 0) {
            $sql .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo;
           }

        if($sqlsex != 'T') {
            $sql .= " AND anagrafica.sesso = '" . $sqlsex ."'";
           }

        if($sqlid_categoria > 0) {
            $sql .= " AND anagrafica.id_categoria = $sqlid_categoria";
           }

         $sql .= "  GROUP BY 2,3,4, 5
                     ORDER BY 4, 5";
                     
       $result = $conn->query($sql);
       while($row = $result->fetch_assoc()) {
                 $pdf->SetFont ('helvetica', 'B', '10px' , '', true );
                 $pdf->Cell(100, 0,"Totale partecipanti " . $row["dal"] . " - " . $row["al"], 0, 0, 'L');
                 $pdf->SetFont ('helvetica', 'B', '12px' , '', true );
                 $pdf->Cell(10, 0, $row["ctr"],  0, 1, 'R');
       	
               }
                    // $pdf->MultiCell(10, '',$textToPrint, 0, 'R', false,1, '','', true);

        $pdf->SetFont ('helvetica', 'B', '11px' , '', true );
        $pdf->Cell(100, 0,"Totale generale partecipanti", 0, 0, 'L');
        $pdf->SetFont ('helvetica', 'B', '12px' , '', true );
        $pdf->Cell(10, 0, $ctrViaggio, 0, 0, 'R');
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
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