<?php
$stats = $this->getStatistics();
?>
<div class="col">
	<h3>Current EAV Status</h3>
</div>
<div>
	<table width="100%">
		<thead>
			<tr>
				<td></td>
				<td>EAV table</td>
				<td>NULL values</td>
				<td>Total values</td>
				<td>% NULL</td>
			</tr>
		</thead>
		<tbody>
<?php
foreach ($stats as $type => $data) {
    ?>
	<?php

    $style = "";
    if ($data["pc"] == 0) {
        $style = "background-color:#88ff88";
    } elseif ($data["pc"] < 15) {
        $style = "background-color:#ffff88";
    } else {
        $style = "background-color:#ff8888";
    }
    ?>
	<tr>
				<td style="<?php echo $style?>">&nbsp;</td>
				<td><?php echo $type?></td>
				<td><?php echo $data["empty"]?></td>
				<td><?php echo $data["total"]?></td>
				<td><?php echo $data["pc"]?></td>
			</tr>
<?php

}
?>
</tbody>
	</table>
</div>
