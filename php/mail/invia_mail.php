<?php
/******************************************
*
*	Invia mail da
*
*	History: prima versione Agosto 2016
*
*
*******************************************/
function invia_mail($to, $bccList, $subject, $text, $toDisplay=null, $att=null, $attName='Allegato') {
	require_once('phpmailer.php');
	require_once('class.smtp.php');
	require_once('../php/DB_connect.php');
	require_once('../php/config_timezone.php');
	
   $fname=basename(__FILE__);
	$debug=false;
	$success=false;
	
	$from_d='';
	$from_u='';
	$reply_to='';
	$cc='';
	$bcc='';
	$smtpServer='';
	$port=0;
	$auth=false;
	$user='';
	$password='';
	$encrypt=false;
	
	$senderRecognized=false; // Utente o Tecnico, gli altri ciccia
	
	error_reporting(E_ALL ^ E_NOTICE); // evitare a display Notice!.	
	config_timezone();

	$conn = DB_connect();

  if(session_status() == PHP_SESSION_NONE)      
      session_start();
       
   if(isset($_SESSION["mail_from_d"])) { // Dati SMTP in sessione, non accedo al DB
      if($debug)
          echo "$fname: Dati SMTP in sessione, NON accedo al DB<br>";
      $from_d = $_SESSION["mail_from_d"];
      $from_u = $_SESSION["mail_from_u"];
      $reply_to = $_SESSION["mail_reply_to"];
      $cc = $_SESSION["mail_cc"];
      $bcc = $_SESSION["mail_bcc"];
      $smtpServer = $_SESSION["mail_smtp"];
      $port = $_SESSION["mail_port"];
      $auth = $_SESSION["mail_auth"];
      $user = $_SESSION["mail_user"];
      $password = $_SESSION["mail_password"];
      $encrypt = $_SESSION["mail_encrypt"];
      }
    else {  
	// Carico i dati di configurazione dal DB, se non in sessione
   // Check DB connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

      if($debug)
          echo "$fname: Dati SMTP NON in sessione, accedo al DB<br>";
	   $sql = "SELECT from_d, from_u, reply_to, cc, bcc, smtp, 
	                port, auth, user, password, encrypt
	                 FROM mailparam";
	   $result = $conn->query($sql);
	
	   if($result->num_rows == 0) 
	        return($success);
      $row = $result->fetch_assoc();
      $from_d = $row["from_d"];   
      $from_u = $row["from_u"];   
      $reply_to = $row["reply_to"];   
      $cc = $row["cc"];   
      $bcc = $row["bcc"];   
      $smtpServer = $row["smtp"];   
      $port = $row["port"];   
      $auth = $row["auth"];   
      $user = $row["user"];   
      $password = $row["password"];   
      $encrypt = $row["encrypt"];   

      $conn->close();
      // Carico i parametri in sessione
      $_SESSION["mail_from_d"] = $from_d;
      $_SESSION["mail_from_u"] = $from_u;
      $_SESSION["mail_reply_to"] = $reply_to;
      $_SESSION["mail_cc"] = $cc;
      $_SESSION["mail_bcc"] = $bcc;
      $_SESSION["mail_smtp"] = $smtpServer;
      $_SESSION["mail_port"] = $port;
      $_SESSION["mail_auth"] = $auth;
      $_SESSION["mail_user"] = $user;
      $_SESSION["mail_password"] = $password;
      $_SESSION["mail_encrypt"] = $encrypt;
	}

    if($encrypt)
       $password = md5($password);    
    $from = $from_u;
    $mittente = $from_d;
	 $text .=  "<br><br><hr>Inviato da (" . $_SESSION["nomeuser"]. ") messaggio generato automaticamente dall'applicativo (Rev 1.0)";

//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//

  // Richiamo la classe e creo un'istanza
   $smtp = new PHPMailer();
   $smtp->IsSMTP();
   $smtp->SMTPDebug  = $debug;
   $smtp->Host       = $smtpServer; // SMTP server
   $smtp->SMTPAuth   = $auth;                  // enable SMTP authentication
   $smtp->Port       = $port;                    // set the SMTP port for the GMAIL server
   $smtp->Username   = $user; // SMTP account username example
   $smtp->Password   = $password;        // SMTP account password example
   $smtp->SetFrom($from_u, $mittente); // SMTP From mail address
   $smtp->Subject = $subject; // SMTP subject
   $smtp->MsgHTML($text);   // SMTP body of message

   	if($debug) {
   	   echo "$fname: Adding Subject: $subject<br>";
   	   echo "$fname: Adding Text: $text<br>";
   	}

   if(strlen($to) > 6) {
   	   if($debug)
   	      echo "$fname: Adding To: $to<br>";
      $smtp->AddAddress($to, $toDisplay); // SMTP To
       }
 
   if(strlen($reply_to) > 6) {
   	   if($debug)
   	      echo "$fname: Adding Reply-to (from DB): $reply_to<br>";
      $smtp->AddReplyTo($reply_to,''); // SMTP Reply To if not null
       }
 
   if(strlen($cc) > 6) {
   	   if($debug)
   	      echo "$fname: Adding Cc (from DB): $cc<br>";
      $smtp->AddCC($cc); // SMTP CC if not null
       }
 
   if(strlen($bcc) > 6) {
   	   if($debug)
   	      echo "$fname: Adding Bcc (from DB): $bcc<br>";
      $smtp->AddBCC($bcc); // SMTP BCC if not null
       }
 
   for($i = 0; $i < count($bccList); $i++) {
   	   if($debug)
   	      echo "$fname: Adding Bcc: $bccList[$i]<br>";
      $smtp->AddBCC($bccList[$i]); // SMTP List
   }

   if(strlen($att) > 6) // Add attachment
      $smtp->AddAttachment($att, $attName); // SMTP add File
      
   if(!$smtp->Send()) {
      echo "Mailer Error: " . $smtp->ErrorInfo;
      $success=false;
     } 
   else {
      $success=true;
   } 
	return($success);
}
?>
