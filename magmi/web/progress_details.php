<?php
require_once("security.php");
session_start();
$key = $_REQUEST["key"];
$data = $_SESSION["log_$key"];
session_write_close();
?>
<script type="text/javascript">
 showtrace=function(traceid)
 {

	 if($('trace_'+traceid).visible())
	 {
		 $('trace_'+traceid).update('');
		 $('trace_'+traceid).hide();
	}
	else
	{
		 new Ajax.Updater('trace_'+traceid,'trace_details.php',{parameters:{'traceid':traceid},onComplete:function(){$('trace_'+traceid).show()}});
 	}
 }
</script>
<ul>
 <?php

foreach ($data as $line) {
    if ($key == "error" && preg_match("|\d+:|", $line)) {
        $inf = explode(":", $line, 2);
        $errnum = $inf[0];
        $xdata = $inf[1];
    } else {
        $errnum = null;
        $xdata = $line;
    }
    ?>
 <li>
 <?php

    if ($errnum != null) {
        ?>
 		<a name="trace_<?php echo $errnum?>"
		href="#trace_<?php echo $errnum?>"
		onclick="showtrace('<?php echo $errnum?>')"><?php echo $errnum?></a>
 	<?php

    }
    ?><span><?php echo $xdata?></span>
 <?php if ($errnum!=null) {
    ?>
 	<div style="display: none" class="trace"
			id="trace_<?php echo $errnum?>"></div>
	</li>
 <?php

}
}
?>
 </ul>