<?php
require_once("../php/unitalsi_include_common.php");
// Database connect
$conn = DB_connect();

$sql = "SELECT id FROM attivita_detail
             WHERE id_attpell = 6
             AND     id NOT IN(SELECT costi_detail.id_parent
                                          FROM  costi_detail
                                          WHERE principale = 0)";
                                          
$r = $conn->query($sql);

$index = 0;
while($rs = $r->fetch_assoc()) {
	       $id = $rs["id"];
	       $sql = "DELETE FROM costi_detail WHERE id_parent = $id AND principale=1";
	       echo $sql;
	       echo "<br>";
	       
	       $conn->query($sql);

	       $sql = "INSERT INTO costi_detail (id_parent, id_costo, qta, costo, principale, tessera)
	                    VALUES($id, 33, 1,0,1,2)";

	       echo $sql;
	       echo "<br>";
	       
	       $conn->query($sql);

	       $sql = "INSERT INTO costi_detail (id_parent, id_costo, qta, costo, principale, tessera)
	                    VALUES($id, 92, 1,20,0,2)";

	       echo $sql;
	       echo "<br>";
	       
	       $conn->query($sql);
	       
	       $index++;
         }
echo $index;

?>