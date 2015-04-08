<?php
require_once("menuclass.php");
$current=basename(dirname($_SERVER["REQUEST_URI"]));
$entries=array("config"=>"Setup",
                "profiles"=>"Profiles");
function checkProfiles()
{
    $conf=getSessionConfig();
    return $conf->getMagentoDir()!="";
}
?>


<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <img alt="Magmi" src="<?php echo BASE_URL?>/images/logo.png">
      </a>
    </div>
    <?php if($_SESSION['IS_SECURE']){?>
      <div class="navbar-header menu-body" >
        <ul class="nav nav-tabs">
            <?php foreach($entries as $basedir=>$label){
                $checkfunck="check".ucfirst($basedir);

                $show=(function_exists($checkfunck) && $checkfunck() || !function_exists($checkfunck));
                if($show)
                {?>
                    <li class="<?php $current==$basedir?'active':''?>"><a href="<?php echo BASE_URL."/$basedir/index.php"?>" id="<?php echo $basedir."_link"?>"><?php echo $label?></a></li>

                <?php } }?>

        </ul>
        </div>
    <?php } ?>
  </div>
</nav>
