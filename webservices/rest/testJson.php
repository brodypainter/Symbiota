<?php
echo "remote content:<br><br>";
$content=file_get_contents("http://db.herbarium.arizona.edu/testSymb/webservices/rest/getedits.php?requestType=edit&collid=102&timestart=2015-12-1%2011:44:41&timeend=2016-02-18+2011:44:31");

$data=json_decode($content, TRUE);

if($data['error']){
	echo 'error encountered';
}


echo count($data);
//do whatever with $data now
?>

<table width="50%">
	<tr><td>ocedid</td><td>occid</td><td>FieldName</td><td>FieldValueNew</td><td>FieldValueOld</td><td>ReviewStatus</td><td>AppliedStatus</td><td>uid</td><td>initialtimestamp</td></tr>
	<?php foreach($data as $i){
		echo '<tr>';
		foreach($i as $j){
			echo '<td style="border: 1px solid grey; padding: 5px;">';
			echo $j;
			echo '</td>';
		}
		echo '</tr>';
	}?>
</table>