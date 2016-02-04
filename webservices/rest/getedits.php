<?php
	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');

	// The type of request, current supported types: edit,
	$requestType = $_REQUEST['requestType'];
	$collid = $_REQUEST['collid'];
	// timestart & timeend in mysql time format
	$timestart = $_REQUEST['timestart'];
	$timeend = $_REQUEST['timeend'];

	if(!$requestType && !$collid && !$timestart && !$timeend){
		echo 'one of the required arguments was not provided!';
	}

	//optional arguments, search terms
	$reviewstatus = $_REQUEST['reviewstatus'];
	$editor = $_REQUEST['editor'];
	$catalognumber = $_REQUEST['catalognumber'];
	$occid = $_REQUEST['occid'];

	echo $requestType;

?>