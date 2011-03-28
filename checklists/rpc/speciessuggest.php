<?php
	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	$con = MySQLiConnectionFactory::getCon("readonly");
	$returnArr = Array();
	$queryString = $con->real_escape_string($_REQUEST['term']);
	$clid = $con->real_escape_string($_REQUEST['cl']);
	
	$sql = "SELECT DISTINCT t.tid, t.sciname ". 
		"FROM taxa t LEFT JOIN (SELECT tid FROM fmchklsttaxalink WHERE clid = $clid) cl ON t.tid = cl.tid ".
		"WHERE cl.tid IS NULL AND t.rankid > 140 AND t.sciname LIKE '".$queryString."%' ";
	//echo $sql;
	$result = $con->query($sql);
	while ($row = $result->fetch_object()) {
       	$returnArr[] = $row->sciname;
	}
	$con->close();
	echo json_encode($returnArr);
?>