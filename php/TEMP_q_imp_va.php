<?php
require_once('../php/unitalsi_include_common.php');
$defCharset = ritorna_charset(); 
$defCharsetFlags = ritorna_default_flags(); 

$conn = DB_connect();

$result = $conn->query("SELECT id_ori, des, anno FROM temporary_pell ORDER BY 2,3 DESC");

echo "<html><body><form action='../php/TEMP_imp_va.php' METHOD='POST'><table>";
echo "<th colspan=4>Source</th>";
echo "<tr>";

$index=0;
while($row = $result->fetch_assoc()) {
	       echo "<td><input id='cb_source' type='checkbox' name='cb_source[]' value='" . $row["id_ori"] . "'>
	                <label for='cb_source'>" . htmlentities($row["des"], $defCharsetFlags, $defCharset) . " " .$row["anno"] . "</label></td>";
	       $index++;
	       if($index/4 == 1){
	       	 echo "</tr><tr>";
	       	 $index=0;
	       }
	}
echo "<th colspan=4>Target</th>";
echo "<tr>";

$index=0;
$result = $conn->query("SELECT attivita.id, CONCAT(attivita.descrizione, 'A') des
                                          FROM   attivita
                                          WHERE  id_sottosezione = 17
                                          UNION
                                          SELECT descrizione_pellegrinaggio.id, CONCAT(descrizione_pellegrinaggio.descrizione, 'V') des
                                          FROM   descrizione_pellegrinaggio
                                          WHERE  id_sottosezione = 17 ORDER BY 2");
while($row = $result->fetch_assoc()) {
	       echo "<td><input id='cb_target' type='checkbox' name='cb_target[]' value=" . $row["id"] . ">
	                <label for='cb_source'>" . htmlentities($row["des"], $defCharsetFlags, $defCharset) . "</label></td>";
	       $index++;
	       if($index/4 == 1){
	       	 echo "</tr><tr>";
	       	 $index=0;
	       }
	}
echo "</tr>";
echo "<tr><td colspan='4'>Tipologia&nbsp;<select name='tipo'>";
echo "<option value='A'>Attivit&agrave;</option>";
echo "<option value='V'>Viaggio/Pellegrinaggio</option>";
echo "</select></td></tr>";
echo "<tr><td colspan=4><input type='submit'></td></tr>";
echo "</table></form></html>";

?>