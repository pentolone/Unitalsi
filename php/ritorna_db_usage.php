<?php
/***************************************************
*
* Ritorna lo spazio utilizzato dal DB
*
* 
*
****************************************************/
function ritorna_db_usage() {
  require_once('../php/unitalsi_include_common.php');

  $maxSpaceAvailable=104857600; // Massimo spazio disponibile (se non in file 100MB)
//
// DO NOT EDIT ANYTHING BELOW THIS LINE!
//
  $units = array('B', 'KB', 'MB', 'GB', 'TB');
  $pUsed = array('%' => '0',
                           'U' => '0',
                           'F' => '0'
                           );
  $spaceUsed=0;
  $spaceFree=0;
  $bytes=0;
  
  if(isset($_SESSION["spaceAvailable"]))
     $maxSpaceAvailable = $_SESSION["spaceAvailable"];
     
  $conn = DB_connect();

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT table_schema 'Data Base Name' , sum( data_length + index_length ) 
/ 1024 / 1024 'Data Base Size in MB', sum( data_free )/ 1024 / 1024 'Free Space in MB' 
FROM information_schema.TABLES GROUP BY table_schema";

  $sql = "SELECT table_schema 'Data Base Name' , sum( data_length + index_length ) 
 'Data Base Size in B', sum( data_free ) 'Free Space in B'
FROM information_schema.TABLES 
WHERE table_schema = DATABASE() GROUP BY table_schema";

  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()) {
//  	echo "DB = " . $row['Data Base Name'];
  	$spaceUsed = $row['Data Base Size in B'];
  	$spaceFree =  $row['Free Space in B'];
  	
/* Space free non affidabile (in alcuni casi ritorna 0)
  	if($spaceFree > 0) // Assuming 100MB
  	    $maxSpaceAvailable = $spaceFree+$spaceUsed;
  	else
  	    $spaceFree = $maxSpaceAvailable-$spaceUsed;
 */ 	
  	 $spaceFree = $maxSpaceAvailable-$spaceUsed;

// Used
  	$bytes = $spaceUsed;
  	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
   $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

   $size_u = round($bytes, 2) . ' ' . $units[$pow];  // Used
  	
// Free
  	$bytes = $spaceFree;
  	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
   $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

   $size_f = round($bytes, 2) . ' ' . $units[$pow];  // Used
  	
// Total
  	$bytes = $maxSpaceAvailable;
  	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
   $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

   $size_t = round($bytes, 2) . ' ' . $units[$pow];  // Used
/*
  	echo " <br>Used = " . $spaceUsed . " (" .$size_u . ")<br>";
  	echo " Free = " .$spaceFree . " (" .$size_f . ")<br>";
  	echo " Tot = " .$maxSpaceAvailable . " (" .$size_t . ")<br>";
  	
  	echo "Percentage Usage = " . round((($spaceUsed * 100)/ $maxSpaceAvailable), 2) . "%<br>";
  	echo "Percentage Free = " . round((($spaceFree * 100)/ $maxSpaceAvailable), 2) . "%<br>";
*/  	
  	$pUsed['%'] =round((($spaceUsed * 100)/ $maxSpaceAvailable), 2) . '%';
  	$pUsed['U'] = $size_u;
  	$pUsed['F'] = $size_f;
  	return($pUsed);
  	
 }
 $conn->close();
 }
 
?>