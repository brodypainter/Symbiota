<?php
//Base folder containing herbarium folder ; read access needed
$sourcePathBase = '';
//Folder where images are to be placed; write access needed
$targetPathBase = '';
//Url base needed to build image URL that will be save in DB
$imgUrlBase = '';
//Path to where log files will be placed
$logPath = '';

//pmterm = Pattern matching terms used to locate primary key (PK) of specimen record
//ex: '/(ASU\d{7})/'; '/(UTC\d{8})/'
$collArr = array(
	'duke' => array('pmterm' => '/(^\d{7})/'),
	'mich' => array('pmterm' => '/(^\d{6})/'),
	'ny' => array('pmterm' => '/(NY\d{8})/'),
);

//If record matching PK is not found, should a new blank record be created?
$createNewRec = 1;
//Weather to copyover images with matching names (includes path) or rename new image and keep both		
$copyOverImg = 1;

$webPixWidth = 800;
$tnPixWidth = 130;
$lgPixWidth = 2000;

//Whether to use ImageMagick for creating thumbnails and web images. ImageMagick must be installed on server.
// 0 = use GD library (default), 1 = use ImageMagick  
$useImageMagick = 0;
//Value between 0 and 100
$jpgCompression = 80;

//Create thumbnail versions of image
$createTnImg = 1;		
//Create large version of image, given source image is large enough
$createLgImg = 1;		

//0 = write image metadata to file; 1 = write metadata to Symbiota database
$dbMetadata = 0;

//Variables below needed only if connecting directly with database
//Symbiota PK for collection; needed if run as a standalone script
$collId = 1;

//-------------------------------------------------------------------------------------------//
//End of variable assignment. Don't modify code below.
date_default_timezone_set('America/Phoenix');
$specManager = new SpecProcessorManager($dbMetadata);

//Set variables
$specManager->setCollArr($collArr);
$specManager->setSourcePathBase($sourcePathBase);
$specManager->setTargetPathBase($targetPathBase);
$specManager->setImgUrlBase($imgUrlBase);
$specManager->setWebPixWidth($webPixWidth);
$specManager->setTnPixWidth($tnPixWidth);
$specManager->setLgPixWidth($lgPixWidth);
$specManager->setJpgCompression($jpgCompression);
$specManager->setUseImageMagick($useImageMagick);

$specManager->setCreateTnImg($createTnImg);
$specManager->setCreateLgImg($createLgImg);
$specManager->setCreateNewRec($createNewRec);
$specManager->setCopyOverImg($copyOverImg);

$specManager->setLogPath($logPath);

if($dbMetadata){
	if(!$collId) exit("ABORTED: variable set to write to database but 'collid' variable has not been set"); 
	$specManager->setCollId($collId);
}

//Run process
$specManager->batchLoadImages();

class SpecProcessorManager {

	private $conn;
	private $collArr = array();
	private $collId = 0;
	private $title;
	private $collectionName;
	private $managementType;
	private $patternMatchingTerm;
	private $sourcePathBase;
	private $targetPathBase;
	private $imgUrlBase;
	private $webPixWidth = 1200;
	private $tnPixWidth = 130;
	private $lgPixWidth = 2400;
	private $jpgCompression= 80;
	private $createWebImg = 1;
	private $createTnImg = 1;
	private $createLgImg = 1;
	
	private $createNewRec = true;
	private $copyOverImg = true;
	private $dbMetadata = 0;			//Only used when run as a standalone script
	private $processUsingImageMagick = 0;

	private $logPath;
	private $logFH;
	private $logErrFH;
	private $mdOutputFH;
	
	private $sourceGdImg;
	private $sourceImagickImg;
	private $exif;
	private $errArr = array();

	function __construct($dbMetadata){
		$this->dbMetadata = $dbMetadata;
		if($this->dbMetadata){
			$this->conn = MySQLiConnectionFactory::getCon("write");
		}
	}

	function __destruct(){
		if($this->dbMetadata){
	 		if(!($this->conn === false)) $this->conn->close();
		}
	}

	public function batchLoadImages(){

		foreach($collArr as $acro => $termArr){
			
			//Create log Files
			if($this->logPath && file_exists($this->logPath)){
				if(substr($this->logPath,-1) != '/') $this->logPath .= '/'; 

				$logFile = $this->logPath.$acro."_log_".date('Ymd').".log";
				$errFile = $this->logPath.$acro."_logErr_".date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logErrFH = fopen($errFile, 'a');
				if($this->logFH) fwrite($this->logFH, "\nDateTime: ".date('Y-m-d h:i:s A')."\n");
				if($this->logErrFH) fwrite($this->logErrFH, "\nDateTime: ".date('Y-m-d h:i:s A')."\n");
				}
			}

			//If output is to go out to file, create file for output
			if(!$this->dbMetadata){
				$mdFileName = $this->logPath.$acro."_urldata_".time().'.csv';
				$this->mdOutputFH = fopen($mdFileName, 'w');
				fwrite($this->mdOutputFH, '"collid","dbpk","url","thumbnailurl","originalurl"'."\n");
				if($this->mdOutputFH){
					echo "Image Metadata written out to CSV file: '".$mdFileName."' (same folder as script)\n";
				}
				else{
					//If unable to create output file, abort upload procedure
					if($this->logFH){
						fwrite($this->logFH, "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n\n");
						fclose($this->logFH);
					}
					if($this->logErrFH){
						fwrite($this->logErrFH, "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n\n");
						fclose($this->logErrFH);
					}
					echo "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n";
					return;
				}
			}
adfgadf	
			//Lets start processing folder
			echo "Starting Image Processing\n";
			$this->processFolder();
			echo "Image upload complete\n";
	
			//Now lets start closing things up
			if(!$this->dbMetadata){
				fclose($this->mdOutputFH);
			}
			if($this->logFH){
				fwrite($this->logFH, "Image upload complete\n");
				fwrite($this->logFH, "----------------------------\n\n");
				fclose($this->logFH);
			}
			if($this->logErrFH){
				fwrite($this->logErrFH, "----------------------------\n\n");
				fclose($this->logErrFH);
			}
		}
	}

	private function processFolder($pathFrag = ''){
		set_time_limit(2000);
		if(!$this->sourcePathBase) $this->sourcePathBase = './';
		//Read file and loop through images
		if($imgFH = opendir($this->sourcePathBase.$pathFrag)){
			while($fileName = readdir($imgFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($this->sourcePathBase.$pathFrag.$fileName)){
						if(stripos($fileName,'_tn.jpg') === false && stripos($fileName,'_lg.jpg') === false){
							$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
							if($fileExt == ".tif"){
								//Do something, like convert to jpg
							}
							if($fileExt == ".jpg"){
								
								$this->processImageFile($fileName,$pathFrag);
								
	        				}
							else{
								//echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
								if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, not a supported image file: ".$fileName." \n");
								//fwrite($this->logFH, "\tERROR: File skipped, not a supported image file: ".$file." \n");
							}
						}
					}
					elseif(is_dir($this->sourcePathBase.$pathFrag.$fileName)){
						$this->processFolder($pathFrag.$fileName."/");
					}
        		}
			}
			if($this->dbMetadata && $this->conn){
				$sql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'SET i.tid = o.tidinterpreted '.
					'WHERE i.tid IS NULL and o.tidinterpreted IS NOT NULL';
				$this->conn->query($sql);
			}
		}
   		closedir($imgFH);
	}

	private function processImageFile($fileName,$pathFrag = ''){
		echo "Processing image ".$fileName."\n";
		if($this->logFH) fwrite($this->logFH, "Processing image (".date('Y-m-d h:i:s A')."): ".$fileName."\n");
		//ob_flush();
		flush();
		//Grab Primary Key from filename
		$specPk = $this->getPrimaryKey($fileName);
		if($specPk){
			//Get occid (Symbiota occurrence record primary key)
        }
		$occId = 0;
		if($this->dbMetadata){
			$occId = $this->getOccId($specPk);
		}
        //If Primary Key is found, continue with processing image
        if($specPk){
        	if($occId || !$this->dbMetadata){
	        	//Setup path and file name in prep for loading image
				$targetFolder = '';
	        	if($pathFrag){
					$targetFolder = $pathFrag;
				}
				else{
					$targetFolder = substr($specPk,0,strlen($specPk)-3).'/';
				}
				$targetPath = $this->targetPathBase.$targetFolder;
				if(!file_exists($targetPath)){
					mkdir($targetPath);
				}
	        	$targetFileName = $fileName;
				//Check to see if image already exists at target, if so, delete or rename
	        	if(file_exists($targetPath.$targetFileName)){
					if($this->copyOverImg){
	        			unlink($targetPath.$targetFileName);
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg");
	        			}
					}
					else{
						//Rename image before saving
						$cnt = 1;
				 		while(file_exists($targetPath.$targetFileName)){
				 			$targetFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
				 			$cnt++;
				 		}
					}
				}
				//Start the processing procedure
				list($width, $height) = getimagesize($this->sourcePathBase.$pathFrag.$fileName);
				echo "Loading image\n";
				if($this->logFH) fwrite($this->logFH, "\tLoading image (".date('Y-m-d h:i:s A').")\n");
				//ob_flush();
				flush();
				
				//Create web image
				$webImgCreated = false;
				if($this->createWebImg && $width > $this->webPixWidth){
					$webImgCreated = $this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$targetFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height);
				}
				else{
					$webImgCreated = copy($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$targetFileName);
				}
				if($webImgCreated){
	        		//echo "<li style='margin-left:10px;'>Web image copied to target folder</li>";
					if($this->logFH) fwrite($this->logFH, "\tWeb image copied to target folder (".date('Y-m-d h:i:s A').") \n");
					$tnUrl = "";$lgUrl = "";
					//Create Large Image
					$lgTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg";
					if($this->createLgImg){
						if($width > ($this->webPixWidth*1.3)){
							if($width < $this->lgPixWidth){
								if(copy($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$lgTargetFileName)){
									$lgUrl = $lgTargetFileName;
								}
							}
							else{
								if($this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$lgTargetFileName,$this->lgPixWidth,round($this->lgPixWidth*$height/$width),$width,$height)){
									$lgUrl = $lgTargetFileName;
								}
							}
						}
					}
					else{
						$lgSourceFileName = substr($fileName,0,strlen($fileName)-4).'_lg'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePathBase.$pathFrag.$lgSourceFileName)){
							rename($this->sourcePathBase.$pathFrag.$lgSourceFileName,$targetPath.$lgTargetFileName);
						}
					}
					//Create Thumbnail Image
					$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg";
					if($this->createTnImg){
						if($this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$tnTargetFileName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
							$tnUrl = $tnTargetFileName;
						}
					}
					else{
						$tnFileName = substr($fileName,0,strlen($fileName)-4).'_tn'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePathBase.$pathFrag.$tnFileName)){
							rename($this->sourcePathBase.$pathFrag.$tnFileName,$targetPath.$tnTargetFileName);
						}
					}
					if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
					if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
					if($this->recordImageMetadata(($this->dbMetadata?$occId:$specPk),$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
						if(file_exists($this->sourcePathBase.$pathFrag.$fileName)) unlink($this->sourcePathBase.$pathFrag.$fileName);
						echo "Image processed successfully!\n";
						if($this->logFH) fwrite($this->logFH, "\tImage processed successfully (".date('Y-m-d h:i:s A').")!\n");
					}
				}

				if($this->sourceGdImg){
					imagedestroy($this->sourceGdImg);
					$this->sourceGdImg = null;
				}
				if($this->sourceImagickImg){
					$this->sourceImagickImg->clear();
					$this->sourceImagickImg = null;
				}
        	}
		}
		else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			echo "File skipped, unable to extract specimen identifier\n";
		}
		//ob_flush();
		flush();
	}

	private function createNewImage($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		global $useImageMagick;
		$status = false;
		
		if($this->processUsingImageMagick) {
			// Use ImageMagick to resize images 
			$status = $this->createNewImageImagick($sourcePathBase,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		} 
		elseif(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working 
			$status = $this->createNewImageGD($sourcePathBase,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		}
		else{
			// Neither ImageMagick nor GD are installed 
			$this->errArr[] = 'No appropriate image handler for image conversions';
		}
		return $status;
	}
	
	private function createNewImageImagick($sourceImg,$targetPath,$newWidth){
		$status = false;
		$ct;
		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.($newWidth*1.5).($this->jpgCompression?' -quality '.$this->jpgCompression:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		return $status;
	}
	
	private function createNewImageGD($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		$status = false;
	   	if(!$this->sourceGdImg){
	   		$this->sourceGdImg = imagecreatefromjpeg($sourcePathBase);
			if(class_exists('PelJpeg')){
				$inputJpg = new PelJpeg($sourcePathBase);
				$this->exif = $inputJpg->getExif();
			}

	   	}

		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($this->jpgCompression){
			$status = imagejpeg($tmpImg, $targetPath, $this->jpgCompression);
			if($this->exif && class_exists('PelJpeg')){
				$outputJpg = new PelJpeg($targetPath);
				$outputJpg->setExif($this->exif);
				$outputJpg->saveFile($targetPath);
			}
		}
		else{
			if($this->exif && class_exists('PelJpeg')){
				$outputJpg = new PelJpeg($tmpImg);
				$outputJpg->setExif($this->exif);
				$status = $outputJpg->saveFile($targetPath);
			}
			else{
				$status = imagejpeg($tmpImg, $targetPath);
			}
		}
		
		if(!$status){
			if($this->logErrFH) fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
			echo "Error: Unable to resize and write file: ".$targetPath."\n";
		}
		
		imagedestroy($tmpImg);
		return $status;
	}
	
	public function setCollId($id){
		$this->collId = $id;
		if($this->collId && is_numeric($this->collId) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, managementtype FROM omcollections WHERE (collid = '.$this->collId.')';
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$this->collectionName = $row->collectionname;
					$this->managementType = $row->managementtype;
				}
				else{
					exit('ABORTED: unable to locate collection in data');
				}
				$rs->close();
			}
			else{
				exit('ABORTED: unable run SQL to obtain collectionName');
			}
		}
	}

	private function getPrimaryKey($str){
		$specPk = '';
		if(preg_match($patternMatchingTerm,$str,$matchArr)){
			if(array_key_exists(1,$matchArr) && $matchArr[1]){
				$specPk = $matchArr[1];
			}
			else{
				$specPk = $matchArr[0];
			}
		}
		return $specPk;
	}

	private function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences WHERE (catalognumber = "'.$specPk.'") AND (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->close();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber'.(stripos($this->managementType,'Live')!==false?'':',dbpk').',processingstatus) '.
				'VALUES('.$this->collId.',"'.$specPk.'"'.(stripos($this->managementType,'Live')!==false?'':',"'.$specPk.'"').',"unprocessed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				if($this->logFH) fwrite($this->logFH, "\tSpecimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") \n");
				echo "Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.")\n";
			} 
		}
		if(!$occId){
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") \n");
			echo "File skipped, unable to locate specimen record ".$specPk."\n";
		}
		return $occId;
	}
	
	private function recordImageMetadata($specID,$webUrl,$tnUrl,$oUrl){
		$status = false;
		if($this->dbMetadata){
			$status = $this->databaseImage($specID,$webUrl,$tnUrl,$oUrl);
		}
		else{
			$status = $this->writeMetadataToFile($specID,$webUrl,$tnUrl,$oUrl);
		}
		return $status;
	}
	
	private function databaseImage($occId,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($occId && is_numeric($occId)){
	        //echo "<li style='margin-left:20px;'>Preparing to load record into database</li>\n";
			if($this->logFH) fwrite($this->logFH, "\tPreparing to load record into database\n");
			//Check to see if image url already exists for that occid
			$imgId = 0;
			$sql = 'SELECT imgid '.
				'FROM images WHERE (occid = '.$occId.') AND (url = "'.$this->imgUrlBase.$webUrl.'")';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgId = $r->imgid;
			}
			$rs->close();
			$sql1 = 'INSERT images(occid,url';
			$sql2 = 'VALUES ('.$occId.',"'.$this->imgUrlBase.$webUrl.'"';
			if($imgId){
				$sql1 = 'REPLACE images(imgid,occid,url';
				$sql2 = 'VALUES ('.$imgId.','.$occId.',"'.$this->imgUrlBase.$webUrl.'"';
			}
			if($tnUrl){
				$sql1 .= ',thumbnailurl';
				$sql2 .= ',"'.$this->imgUrlBase.$tnUrl.'"';
			}
			if($oUrl){
				$sql1 .= ',originalurl';
				$sql2 .= ',"'.$this->imgUrlBase.$oUrl.'"';
			}
			$sql1 .= ',imagetype,owner) ';
			$sql2 .= ',"specimen","'.$this->collectionName.'")';
			if(!$this->conn->query($sql1.$sql2)){
				$status = false;
				if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql1.$sql2."\n");
			}
			if($imgId){
				if($this->logErrFH) fwrite($this->logErrFH, "\tWARNING: Existing image record replaced; occid: $occId \n");
				echo "Existing image database record replaced\n";
			}
			else{
				echo "Image record loaded into database\n";
				if($this->logFH) fwrite($this->logFH, "\tSUCCESS: Image record loaded into database\n");
			}
		}
		else{
			$status = false;
			if($this->logErrFH) fwrite($this->logErrFH, "ERROR: Missing occid (omoccurrences PK), unable to load record \n");
	        echo "ERROR: Unable to load image into database. See error log for details\n";
		}
		//ob_flush();
		flush();
		return $status;
	}

	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->collId.',"'.$specPk.'","'.$this->imgUrlBase.$webUrl.'","'.$this->imgUrlBase.$tnUrl.'","'.$this->imgUrlBase.$oUrl.'"'."\n");
		}
		return $status;
	}

	//Set and Get functions
	public function setCollArr($cArr){
		$this->collArr = $cArr;
	}
	
	public function setTitle($t){
		$this->title = $t;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setCollectionName($cn){
		$this->collectionName = $cn;
	}

	public function getCollectionName(){
		return $this->collectionName;
	}

	public function setManagementType($t){
		$this->managementType = $t;
	}

	public function getManagementType(){
		return $this->managementType;
	}

	public function setSourcePathBase($p){
		$this->sourcePathBase = $p;
	}

	public function getSourcePathBase(){
		return $this->sourcePathBase;
	}

	public function setTargetPathBase($p){
		$this->targetPathBase = $p;
	}

	public function getTargetPathBase(){
		return $this->targetPathBase;
	}

	public function setImgUrlBase($u){
		if(substr($u,-1) != '/') $u = '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setWebPixWidth($w){
		$this->webPixWidth = $w;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setLgPixWidth($lg){
		$this->lgPixWidth = $lg;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function setJpgCompression($jc){
		$this->jpgCompression = $jc;
	}

	public function getJpgCompression(){
		return $this->jpgCompression;
	}

	public function setCreateWebImg($c){
		$this->createWebImg = $c;
	}

	public function getCreateWebImg(){
		return $this->createWebImg;
	}

	public function setCreateTnImg($c){
		$this->createTnImg = $c;
	}

	public function getCreateTnImg(){
		return $this->createTnImg;
	}

	public function setCreateLgImg($c){
		$this->createLgImg = $c;
	}

	public function getCreateLgImg(){
		return $this->createLgImg;
	}
	
	public function setCreateNewRec($c){
		$this->createNewRec = $c;
	}

	public function getCreateNewRec(){
		return $this->createNewRec;
	}
	
	public function setCopyOverImg($c){
		$this->copyOverImg = $c;
	}

	public function getCopyOverImg(){
		return $this->copyOverImg;
	}
	
	public function setDbMetadata($v){
		$this->dbMetadata = $v;
	}

 	public function setUseImageMagick($useIM){
 		$this->processUsingImageMagick = $useIM;
 	}
 	
 	public function setLogPath($p){
 		$this->logPath = $p;
 	}

	//Misc functions
	private function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
}

class MySQLiConnectionFactory {
	static $SERVERS = array(
		array(
			'type' => 'readonly',
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => ''
		),
		array(
			'type' => 'write',
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => ''
		)
	);

	public static function getCon($type) {
		// Figure out which connections are open, automatically opening any connections
		// which are failed or not yet opened but can be (re)established.
		for($i = 0, $n = count(MySQLiConnectionFactory::$SERVERS); $i < $n; $i++) {
			$server = MySQLiConnectionFactory::$SERVERS[$i];
			if($server['type'] == $type){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database']);
				if(mysqli_connect_errno()){
					//throw new Exception('Could not connect to any databases! Please try again later.');
					exit('ABORTED: could not connect to database');
				}
				return $connection;
			}
		}
	}
}
?>