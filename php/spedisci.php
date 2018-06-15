<script type="text/javascript" src="../js/messaggi.js"></script>
<?php 
/****************************************************************************************************
*
*  Invia mail agli utenti
*
*  @file spedisci.php
*  @abstract Invia mail ai soci
*  @author Luca Romano
*  @version 1.0
*  @since 2017-03-17
*  @where Monza
*
*
****************************************************************************************************/
require_once('../php/unitalsi_include_common.php');
require_once('../php/check_email_valid.php');
require_once('../php/mail/invia_mail.php');

config_timezone();

if(($userid = session_check()) == 0)
    return;

$sott_app = ritorna_sottosezione_pertinenza();

$debug=false;
$fname=basename(__FILE__);
$sqlid_sottosezione=0;
$sqlid_gruppo_par=0;
$sqlid_socio=0;

$sqleffettivo=false;
$sqltipo='N';
$sqlid_attpell=0;
$catID=0;
$claID=0;
$index=0;
$info = array();
$to='';
$toDisplay=null;
$attach=null;
$attachName=null;

$bcc = array();
$ixValid=0;

$conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }


  if ($_POST) { // se post allora fase di modifica
      $update = true;
      $kv = array();
      foreach ($_POST as $key => $value) {
   //                 $kv[] = "$key=$value";
                    if( is_array( $value ) ) {
                        foreach( $value as $thing ) {
                                echo $thing;
                                $info[$key] = $thing;
                               }
                  } else {
                    switch($key) {
      		                      case "redirect": // redirect
      				                        $redirect = $value;
      				                        break;

      		                      case "id_sottosezione": // id_sottosezione
      				                        $sqlid_sottosezione = $value;
      				                        break;

      		                      case "id_socio": // id_socio
      				                        $sqlid_socio = $value;
      				                        break;

      		                       case "id_gruppo_par": // gruppo parrocchiale
      					                      $sqlid_gruppo_par = $value;
      					                       break;

      		                      case "tipo": // Tipo (A,V)
      					                    $sqltipo = $value;
      				                        break;

      		                      case "id_attpell": // id attivita'/pellegrinaggio
      					                    $sqlid_attpell = $value;
      				                        break;

      		                      case "effettivo": // richiesto effettivo
      					                    $sqleffettivo = true;
      				                        break;

      		                      case "id_categoria": // richiesto categoria specifica
      					                    $catID = $value;
      				                        break;

      		                      case "id_classificazione": // richiesto classificazione specifica
      					                    $claID = $value;
      				                        break;

      		                      case "sub": // Subject e-mail
      					                    $subMail = $value;
      				                        break;

      		                      case "corpo": // Testo email
      					                    $txtMail = $value;
      				                        break;
                    }
                  }
             $index++;
         }
// SQL per anagrafica
$sql = "SELECT email, CONCAT(cognome, ' ', nome) nome
             FROM   anagrafica 
             WHERE email IS NOT NULL
             AND     anagrafica.id_sottosezione = $sqlid_sottosezione" ; // email IS NOT NULL";

if($sqlid_socio > 0)
    $sql .= " AND anagrafica.id= " . $sqlid_socio;

if($sqlid_gruppo_par > 0)
    $sql .= " AND anagrafica.id_gruppo_par = " . $sqlid_gruppo_par;

if($sqleffettivo)
    $sql .= " AND anagrafica.socio_effettivo = 1";

if($catID > 0)
    $sql .= " AND anagrafica.id_categoria = " . $catID;

if($claID > 0)
    $sql .= " AND (anagrafica.id_classificazione = $claID
                   OR    anagrafica.id_classificazione1 = $claID)";

 if($sqlid_attpell > 0) {
  	  $sql .=" AND anagrafica.id IN(SELECT id_socio
  	                                                 FROM  attivita_detail
  	                                                 WHERE tipo = '" . $sqltipo . "'
  	                                                 AND    id_attpell = $sqlid_attpell)";
  	                                }

$sql .= " ORDER BY 2";
if($debug)
    echo "$fname: SQL = $sql<br>";

$index=0;
$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
	       if(validateEMAIL($row["email"])){
	       	 switch($sqlid_socio) {
	       	 	case 0:
          	          $bcc[$ixValid] = $row["email"];
          	          break;

	       	 	default: // Singolo socio
          	          $to = $row["email"];
          	          $toDisplay  = $row["nome"];
          	          break;
	       	 	
	       	 }
	          $ixValid++;
	          }
	       $index++;
        }
        
if($ixValid == 0) {
	echo "<script>avviso('Nessuna mail valida trovata!', '" . $redirect . "');</script>";
	return;
   }

$txt = 'Soci elaborati = ' . $index . ', con e-mail valida = '. $ixValid;

if(isset($_FILES['upload'])) { // Allegato
   $attach = $_FILES['upload']['tmp_name'];
   $fileType = $_FILES['upload']['type'];
   $size = $_FILES['upload']['size'];
   $attachName = $_FILES['upload']['name'];
}

if($debug) {
    echo var_dump($bcc);

echo "ATTACH = " .$attach;
}
if($ixValid > 0) {
   if(invia_mail($to, $bcc, $subMail, $txtMail, $toDisplay, $attach, $attachName))
      $txt .= ". E-mail inviata/e correttamente";
   else
      $txt .= ". E-mail NON inviata/e";
   }
if(!$debug)
    echo "<script>avviso('" . $txt . "', '" . $redirect . "');</script>";
}
?>