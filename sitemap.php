<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/SiteMapManager.php');
include_once($SERVER_ROOT.'/content/lang/sitemap.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:''; 

$smManager = new SiteMapManager();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?><?php echo $LANG['SITEMAP'];?></title>
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function submitTaxaNoImgForm(f){
			if(f.clid.value != ""){
				f.submit();
			}
			return false;
		}
	</script>
	<script type="text/javascript" src="js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($sitemapMenu)?$sitemapMenu:"true");
	include($SERVER_ROOT.'/header.php');
	if(isset($sitemapCrumbs)){
		echo "<div class='navpath'>";
		echo '<a href="index.php">'.$LANG['HOME'].'</a> &gt; ';
		echo $sitemapCrumbs;
		echo " <b>".$LANG['SITEMAP']."</b>";
		echo "</div>";
	}
		
	?> 
	<!-- This is inner text! --> 
	<div id="innertext">
		<h1><?php echo $LANG['SITEMAP']; ?></h1>
		<div style="margin:10px;">
			<h2><?php echo $LANG['COLLECTIONS']; ?></h2>
			<ul>
				<li><a href="collections/index.php"><?php echo $LANG['SEARCHENGINE'];?></a><?php echo $LANG['SEARCH_COLL'];?></li>
				<li><a href="collections/misc/collprofiles.php"><?php echo $LANG['COLLECTIONS'];?></a><?php echo $LANG['LISTOFCOLL'];?></li>
				<li><a href="collections/misc/collstats.php"><?php echo $LANG['COLLSTATS'];?></a></li>
				<li><a href="collections/exsiccati/index.php"><?php echo $LANG['EXSICC'];?></a></li>
				<li><a href="collections/datasets/datapublisher.php"><?php echo $LANG['DARWINCORE'];?></a><?php echo $LANG['PUBDATA'];?></li>
				<?php 
				if(file_exists('webservices/dwc/rss.xml')){
					echo '<li style="margin-left:15px;"><a href="webservices/dwc/rss.xml">'.$LANG['RSS'].'</a></li>';
				}
				?>
				<li><a href="collections/misc/rarespecies.php"><?php echo $LANG['RARESPEC'];?></a><?php echo $LANG['LISTOFTAXA'];?></li>
				
			</ul>
				
			<div style="margin-top:10px;"><h2><?php echo $LANG['IMGLIB'];?></h2></div>
			<ul>
				<li><a href="imagelib/index.php"><?php echo $LANG['IMGLIB'];?></a></li>
				<li><a href="imagelib/photographers.php"><?php echo $LANG['CONTRIB'];?></a></li>
				<li><a href="misc/usagepolicy.php"><?php echo $LANG['USAGEPOLICY'];?></a></li>
			</ul>

			<?php 
			$clList = $smManager->getChecklistList($isAdmin,(array_key_exists('ClAdmin',$userRights)?$userRights['ClAdmin']:0));
			$projList = $smManager->getProjectList();
			if($projList){
				echo '<div style="margin-top:10px;"><h2>'.$LANG['BIOINV'].'</h2></div><ul>';
				foreach($projList as $pid => $pArr){
					echo "<li><a href='projects/index.php?pid=".$pid."'>".$pArr["name"]."</a></li>\n";
					echo "<ul><li>Manager: ".$pArr["managers"]."</li></ul>\n";
				}
				echo '</ul>';
			}
			?>

			<div style="margin-top:10px;"><h2><?php echo $LANG['DYNAMIC'];?></h2></div>
			<ul>
				<li>
					<a href="checklists/dynamicmap.php?interface=checklist">
                        <?php echo $LANG['CHECKLIST'];?>
					</a>
                    <?php echo $LANG['BUILDCHECK'];?>
				</li>
				<li>
					<a href="checklists/dynamicmap.php?interface=key">
                        <?php echo $LANG['DYNAMICKEY'];?>
					</a>
                    <?php echo $LANG['BUILDDKEY'];?>
				</li>
			</ul>

			<fieldset style="margin:30px 0px 10px 10px;padding-left:25px;padding-right:15px;">
				<legend><b><?php echo $LANG['MANAGTOOL'];?></b></legend>
				<?php 
				if($symbUid){
					if($isAdmin){
						?>
						<h3><?php echo $LANG['ADMIN'];?></h3>
						<ul>
							<li>
								<a href="profile/usermanagement.php"><?php echo $LANG['USERPERM'];?></a>
							</li>
							<li>
								<a href="profile/usertaxonomymanager.php"><?php echo $LANG['TAXINTER'];?></a>
							</li>  
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/misc/collmetadata.php">
                                    <?php echo $LANG['CREATENEWCOLL'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/admin/guidmapper.php">
                                    <?php echo $LANG['GUIDMAP'];?>
								</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/specprocessor/salix/salixhandler.php">
                                    <?php echo $LANG['SALIX'];?>
								</a>
							</li>
						</ul>
						<?php
					}
					
					if($keyModIsActive || array_key_exists("KeyAdmin",$userRights)){ 
						$clActive = array();
						if($clList && isset($userRights['ClAdmin'])){
							$clActive = array_intersect_key($clList,array_flip($userRights['ClAdmin']));
						}
						?>
						<h3><?php echo $LANG['IDKEYS'];?></h3>
						<?php 
						if(!$keyModIsActive && array_key_exists("KeyAdmin",$userRights)){
							?>
							<div style="color:red;margin-left:10px;">
                                <?php echo $LANG['KEYMODULE'];?>
							</div>
							<?php 
						}
						?>
						<ul>
							<?php 
							if($isAdmin || array_key_exists("KeyAdmin",$userRights)){
								?>
								<li>
                                    <?php echo $LANG['AUTHOKEY'];?><a href="<?php echo $clientRoot; ?>/ident/admin/index.php"><?php echo $LANG['CHARASTATES'];?></a>
								</li>
								<?php 
								if($clActive){
									asort($clActive);
									?>
									<li id="testkeyhead" style="margin-left:15px;">
										<a href="#" onclick="toggle('testkey');toggle('testkeyhead');return false;"><?php echo $LANG['DISPLAYTESTK'];?></a>
									</li>
									<div id="testkey" style="display:none;margin-left:15px;">
										<?php 
										foreach($clActive as $clKey => $clValue){
											echo '<li>';
											echo '<a href="'.$clientRoot.'/ident/key.php?cl='.$clKey.'&taxon=All+Species">'.$clValue.'</a>';
											echo '</li>';
										}
										?>
									</div>
									<?php
								}
							}
							if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){ 
								?>
								<li>
                                    <?php echo $LANG['YAATE'];?>
								</li>
								<li>
                                    <?php echo $LANG['CODINGCHARA'];?>
									<a href="<?php echo $clientRoot; ?>/ident/tools/massupdate.php"><?php echo $LANG['MASSUPDATEED'];?></a><br/>
									<?php 
									if($projList){
										//Show Checklists that user has explicit editing rights
										if($clActive){
											echo '<div style="margin:5px 0px 0px 10px;"><b>'.$LANG['EDITBYCHECK'].'</b></div>';
											echo '<ul>';
											foreach($clActive as $vClid => $name){
												echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?clf=".$vClid."'>".$name."</a></li>";
											}
											echo '</ul>';
										}
										//Show projects for access to a greater scope of checklist 
										echo '<div style="margin:5px 0px 0px 10px;"><b>'.$LANG['EDITBYINV'].'</b></div>';
										foreach($projList as $pid => $pArr){
											echo '<ul>';
											echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?pid=".$pid."'>".$pArr["name"]."</a></li>";
											echo '</ul>';
										}
									}
									elseif($clList){
										echo '<div style="margin:5px 0px 0px 10px;"><b>'.$LANG['EDITBYCHECK'].'</b></div>';
										echo '<ul>';
										foreach($clList as $clidKey => $clName){
											echo "<li><a href='".$clientRoot."/ident/tools/massupdate.php?clf=".$clidKey."'>".$clName."</a></li>";
										}
										echo '</ul>';
									}
									?>
								</li>
								<?php
							}
							else{
								?>
								<li><?php echo $LANG['NOTAUTHIDKEY'];?></li>
								<?php 
							}
							?>
						</ul>
						<?php
					}
					?>
					<h3><?php echo $LANG['IMAGES'];?></h3>
					<div style="margin:10px;">
                        <?php echo $LANG['SEESYMBDOC'];?>
						<a href="http://symbiota.org/docs/image-submission-2/"><?php echo $LANG['IMGSUB'];?></a>
                        <?php echo $LANG['FORANOVERVIEW'];?>
					</div>
					<ul>
						<?php 
						if($isAdmin || array_key_exists('TaxonProfile',$userRights)){ 
							?>
							<li>
								<a href="taxa/admin/tpeditor.php?tabindex=1" target="_blank">
                                    <?php echo $LANG['BASICFIELD'];?>
								</a>
							</li>
							<?php
						}
						if($isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("CollEditor",$userRights)){
						?>
						<li>
							<a href="collections/editor/observationsubmit.php">
                                <?php echo $LANG['IMGOBSER'];?>
							</a>
						</li>
						<?php 
						}
						if($isAdmin || array_key_exists('TaxonProfile',$userRights)){ 
							?>
							<li>
								<?php if($submitAction == 'taxanoimages') echo '<a name="taxanoimages"><a/>'; ?>
								<b><?php echo $LANG['TAXANOIMG'];?></b>
								<form name="taxanoimg" action="sitemap.php#taxanoimages" method="post" style="display:inline;"> 
									<select name="clid" style="width:450px;" onchange="submitTaxaNoImgForm(this.form);">
										<option value=""><?php echo $LANG['SELECTCHECK'];?></option>
										<option value="">-------------------------------</option>
										<?php 
											$clArr = $smManager->getChecklistList($isAdmin,(array_key_exists('ClAdmin',$userRights)?$userRights['ClAdmin']:0));
											foreach($clArr as $clid => $clname){
												echo '<option value="'.$clid.'">'.$clname."</option>\n";
											}
										?>
									</select>
									<input type="hidden" name="submitaction" value="taxanoimages" />
								</form>
								<?php 
								if($submitAction == 'taxanoimages'){
									$tArr = $smManager->getTaxaWithoutImages($_REQUEST['clid']);
									echo '<fieldset style="margin:10px;width:400px;">';
									echo '<div style="margin:10px;"><b>'.$clArr[$_REQUEST['clid']].':</b> '.count($tArr).' taxa without images</div>';
									echo "<ul style='margin:10px'>\n";
									foreach($tArr as $tid => $sn){
										echo "<li><a href='taxa/admin/tpeditor.php?tid=".$tid."&category=imageadd&tabindex=3' target='_blank'>".$sn."</a></li>\n";
									}
									echo "</ul>\n";
									echo '</fieldset>';
								}
								?>
							</li>
							<li>
								<?php if($submitAction == 'taxanofieldimages') echo '<a name="taxanofieldimages"><a/>'; ?>
								<b><?php echo $LANG['TAXANOFIELD'];?></b>
								<form name="taxanofieldimg" action="sitemap.php#taxanofieldimages" method="post" style="display:inline;"> 
									<select name="clid" style="width:450px;" onchange="submitTaxaNoImgForm(this.form);">
										<option value=""><?php echo $LANG['SELECTCHECK'];?></option>
										<option value="">--------------------------------</option>
										<?php 
											foreach($clArr as $clid => $clname){
												echo '<option value="'.$clid.'">'.$clname."</option>\n";
											}
										?>
									</select>
									<input type="hidden" name="submitaction" value="taxanofieldimages" />
								</form>
								<?php 
								if($submitAction == 'taxanofieldimages'){
									$tArr = $smManager->getTaxaWithoutImages($_REQUEST['clid'],true);
									echo '<fieldset style="margin:10px;width:400px;">';
									echo '<div style="margin:10px;"><b>'.$clArr[$_REQUEST['clid']].':</b> '.count($tArr).' taxa without field images</div>';
									echo "<ul>";
									foreach($tArr as $tid => $sn){
										echo '<li>';
										echo '<a href="taxa/admin/tpeditor.php?tid='.$tid.'&category=imageadd&tabindex=3" target="_blank">'.$sn.'</a>';
										echo "</li>";
									}
									echo "</ul>";
									echo '</fieldset>';
								}
								?>
							</li>
						<?php }?>
					</ul>

					<h3><?php echo $LANG['BIOINV'];?></h3>
					<ul>
						<?php 
						if($isAdmin){
							echo '<li><a href="projects/index.php?newproj=1">'.$LANG['ADDNEWPROJ'].'</a></li>';
							if($projList){
								echo '<li><b>'.$LANG['LISTOFCURR'].'</b>'.$LANG['CLICKEDIT'].'</li>';
								echo '<ul>';
								foreach($projList as $pid => $pArr){
									echo '<li><a href="'.$clientRoot.'/projects/index.php?pid='.$pid.'&emode=1">'.$pArr['name'].'</a></li>';
								}
								echo '</ul>';
							}
							else{
								echo '<li>'.$LANG['NOPROJ'].'</li>';
							}
						}
						else{
							echo '<li>'.$LANG['NOEDITPROJ'].'</li>';
						}
						?>
					</ul>

					<h3><?php echo $LANG['TAXONPROF'];?></h3>
					<?php 
					if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
						?>
						<div style="margin:10px;">
                            <?php echo $LANG['THEFOLLOWINGSPEC'];?>
						</div>
						<ul>
							<li><a href="taxa/admin/tpeditor.php?taxon="><?php echo $LANG['SYN_COM'];?></a></li>
							<li><a href="taxa/admin/tpeditor.php?taxon=&tabindex=4"><?php echo $LANG['TEXTDESC'];?></a></li>
							<li><a href="taxa/admin/tpeditor.php?taxon=&tabindex=1"><?php echo $LANG['EDITIMG'];?></a></li>
							<li style="margin-left:15px;"><a href="taxa/admin/tpeditor.php?taxon=&category=imagequicksort&tabindex=2"><?php echo $LANG['IMGSORTORD'];?></a></li>
							<li style="margin-left:15px;"><a href="taxa/admin/tpeditor.php?taxon=&category=imageadd&tabindex=3"><?php echo $LANG['ADDNEWIMG'];?></a></li>
						</ul>
						<?php 
					}
					else{
						?>
						<ul>
							<li><?php echo $LANG['NOTAUTHOTAXONPAGE'];?></li>
						</ul>
						<?php 
					}
					?>
					<h3><?php echo $LANG['TAXONOMY'];?></h3>
					<ul>
						<?php 
						if($isAdmin || array_key_exists("Taxonomy",$userRights)){
							?>
							<li><a href="taxa/admin/taxonomydisplay.php"><?php echo $LANG['TAXTREE'];?></a></li>
							<li><a href="taxa/admin/taxonomydynamicdisplay.php"><?php echo $LANG['DYNTAXTREE'];?></a></li>
							<li><?php echo $LANG['EDITTAXPL'];?><a href="taxa/admin/taxonomydisplay.php"><?php echo $LANG['TAXTREEVIEW'];?></a></li>
							<li><a href="taxa/admin/taxonomyloader.php"><?php echo $LANG['ADDTAXANAME'];?></a></li>
							<li><a href="taxa/admin/taxaloader.php"><?php echo $LANG['BATCHTAXA'];?></a></li>
							<?php 
							if($isAdmin || array_key_exists("Taxonomy",$userRights)){
								?>
								<li><a href="taxa/admin/eolmapper.php"><?php echo $LANG['EOLLINK'];?></a></li>
								<?php 
							}
						}
						else{
							echo '<li>'.$LANG['NOTEDITTAXA'].'</li>';
						}
						?>
					</ul>

					<h3><?php echo $LANG['CHECKLISTS'];?></h3>
					<div style="margin:10px;">
                        <?php echo $LANG['TOOLSFORMANAGE'];?>
					</div>
					<ul>
						<?php 
						if($clList){
							foreach($clList as $k => $v){
								echo "<li><a href='".$clientRoot."/checklists/checklist.php?cl=".$k."&emode=1'>$v</a></li>";
							}
						}
						else{
							echo "<li>".$LANG['NOTEDITCHECK']."</li>";
						}
						?>
					</ul>

					<?php 
					if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
						?>
						<h3><?php echo $LANG['EXSICCATII'];?></h3>
						<div style="margin:10px;">
                            <?php echo $LANG['ESCMOD'];?>
						</div>
						<ul>
							<li><a href="collections/exsiccati/index.php"><?php echo $LANG['EXSICC'];?></a></li>
						</ul>
						<?php 
					}
					?>

					<h3><?php echo $LANG['COLLECTIONS'];?></h3>
					<div style="margin:10px;">
                        <?php echo $LANG['PARA1'];?>
					</div>
					<div style="margin:10px;">
						<div style="font-weight:bold;">
                            <?php echo $LANG['COLLLIST'];?>
						</div>
						<ul>
						<?php 
						$smManager->setCollectionList();
						if($collList = $smManager->getCollArr()){
							foreach($collList as $k => $cArr){
								echo '<li>';
								echo '<a href="'.$clientRoot.'/collections/misc/collprofiles.php?collid='.$k.'&emode=1">';
								echo $cArr['name'];
								echo '</a>';
								echo '</li>';
							}
						}
						else{
							echo "<li>".$LANG['NOTEDITCOLL']."</li>";
						}
						?>
						</ul>
					</div>

					<h3><?php echo $LANG['OBSERV'];?></h3>
					<div style="margin:10px;">
                        <?php echo $LANG['PARA2'];?>
						<a href="http://symbiota.org/docs/specimen-data-management/" target="_blank"><?php echo $LANG['SYMBDOCU'];?></a><?php echo $LANG['FORMOREINFO'];?>
					</div>
					<div style="margin:10px;">
						<?php 
						$obsList = $smManager->getObsArr();
						$genObsList = $smManager->getGenObsArr();
						$obsManagementStr = '';
						?>
						<div style="font-weight:bold;">
                            <?php echo $LANG['OIVS'];?>
						</div>
						<ul>
							<?php 
							if($obsList){
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
								}
								foreach($obsList as $k => $oArr){
									?>
									<li>
										<a href="collections/editor/observationsubmit.php?collid=<?php echo $k; ?>">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php
									if($oArr['isadmin']) $obsManagementStr .= '<li><a href="collections/misc/collprofiles.php?collid='.$k.'&emode=1">'.$oArr['name']."</a></li>\n";
								}
							}
							else{
								echo "<li>".$LANG['NOOBSVPROJ']."</li>";
							}
							?>
						</ul>
						<?php
						if($genObsList){ 
							?>
							<div style="font-weight:bold;">
                                <?php echo $LANG['PERSONAL'];?>
							</div>
							<ul>
								<?php 
								foreach($genObsList as $k => $oArr){
									?>
									<li>
										<a href="collections/misc/collprofiles.php?collid=<?php echo $k; ?>&emode=1">
											<?php echo $oArr['name']; ?>
										</a>
									</li>
									<?php 
								}
								?>
							</ul>
							<?php
						}
						if($obsManagementStr){
							?>
							<div style="font-weight:bold;">
                                <?php echo $LANG['OPM'];?>
							</div>
							<ul>
								<?php echo $obsManagementStr; ?>
							</ul>
						<?php 
						}
					?>
					</div>
					<?php 
				}
				else{
					echo ''.$LANG['PLEASE'].' <a href="'.$clientRoot.'/profile/index.php?refurl=../sitemap.php">'.$LANG['LOGIN'].'</a>'.$LANG['TOACCESS'].'<br/>'.$LANG['CONTACTPORTAL'].'';
				}
			?>
			</fieldset>

			<h2><?php echo $LANG['ABOUT'];?></h2>
			<ul>
				<li>
                    <?php echo $LANG['SCHEMA'];?><?php echo $smManager->getSchemaVersion(); ?>
				</li>
			</ul>
		</div>
	</div>
	<?php
		include($SERVER_ROOT.'/footer.php');
	?> 
</body>
</html>