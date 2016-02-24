<?php

	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');

	// The type of request, current supported types: edit,
	$requestType = (isset($_REQUEST['requestType'])) ? $_REQUEST['requestType'] : NULL ;
	$collid = (isset($_REQUEST['collid'])) ? $_REQUEST['collid'] : NULL ;
	
	// timestart & timeend in mysql time format
	$timestart = (isset($_REQUEST['timestart'])) ? $_REQUEST['timestart'] : NULL ;
	$timeend = (isset($_REQUEST['timeend'])) ? $_REQUEST['timeend'] : NULL ;

	if(!$requestType || !$collid || !$timestart || !$timeend){
		$status = ['error'=>'Required fields not provided'];
		echo json_encode($status);
		exit;
	}

	//optional arguments, search terms
	$limit = (isset($_REQUEST['limit'])) ? $_REQUEST['limit'] : NULL ;
	$reviewstatus = (isset($_REQUEST['reviewstatus'])) ? $_REQUEST['reviewstatus'] : NULL ;
	$editor = (isset($_REQUEST['editor'])) ? $_REQUEST['editor'] : NULL ;
	$catalognumber = (isset($_REQUEST['catalognumber'])) ? $_REQUEST['catalognumber'] : NULL ;
	$occid = (isset($_REQUEST['occid'])) ? $_REQUEST['occid'] : NULL ;

	// Database interface code
	$conn = MySQLiConnectionFactory::getCon("readonly");
	$query = 'SELECT e.* FROM omoccuredits e INNER JOIN omoccurrences o ON o.occid = e.occid WHERE e.initialtimestamp > "' . $timestart . '" AND e.initialtimestamp < "' . $timeend . '" ';
	$query .= ($limit) ? 'limit '.$limit : 'limit 100';
	

	$rs = mysqli_query($conn, $query);
	$resultArray[] = null;
	while(($temp = mysqli_fetch_assoc($rs)) != null){
		array_push($resultArray, $temp);
	}

	//echo $query;
	//echo json_encode($_REQUEST);
	echo json_encode($resultArray);

?>