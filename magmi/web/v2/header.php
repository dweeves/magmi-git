<?php
require_once("menuclass.php");?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <img alt="Magmi" src="../images/logo.png">
      </a>
    </div>
      <div class="navbar-header menu-body" >
        <ul class="nav nav-tabs">
        <li><a href="javascript:void(0)" id="baseconf">Setup</a></li>
        <?php if (file_exists("../../conf/magmi.ini")):?>
            <li><a href="javascript:void(0)" id="profiles">Profiles</a></li>
        <?php endif; ?>
        </ul>
        </div>
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