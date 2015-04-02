<?php
require_once("security.php");
require_once("menuclass.php");
require_once("../../inc/magmi_defs.php");
require_once("utils.php");
$conf=getSessionConfig();
?>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <img alt="Magmi" src="../images/logo.png">
      </a>
    </div>
    <?php if($_SESSION['IS_SECURE']){?>
      <div class="navbar-header menu-body" >
        <ul class="nav nav-tabs">
        <li><a href="javascript:void(0)" id="config">Setup</a></li>
        <?php if($conf->get("MAGENTO","magentodir")):?>
            <li><a href="javascript:void(0)" id="profiles">Profiles</a></li>
        <?php endif; ?>
        </ul>
        </div>
    <?php } ?>
  </div>
</nav>
<script>
    $('.menu-body li a').click(function()
    {
        $('#main_content').load($(this).attr('id')+'/content.php');
        $(this).parent().parent().find('li').removeClass('active');
        $(this).parent().addClass('active');
    });
</script>