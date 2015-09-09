<?php
if (!isset($dr))
{
    if (isset($_REQUEST['UTCSQL:queryfile']))
    {
        $dr = $_REQUEST['UTCSQL:queryfile'];
    }
}

if (isset($dr))
{
    $rparams = $this->getRequestParameters($dr, true);
}

foreach ($rparams as $plabel => $pinfo)
{
    ?>
<ul class="formline">
	<li class="label"><?php echo $plabel	?> </li>
	<li class="value"><input name="UTCSQL:<?php echo $pinfo["name"]?>"
		type="text"
		value="<?php echo $this->getParam("UTCSQL:{$pinfo["name"]}",$pinfo["default"]) ?>" /></li>
</ul>
<?php } ?>