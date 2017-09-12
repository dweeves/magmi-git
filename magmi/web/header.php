<?php require_once("head.php"); ?>
<header class="row sticky-top mb-4">
	<div class="container">
		<nav class="navbar navbar-expand navbar-dark">
			<a href="magmi.php" title="Home" class="navbar-brand logo mx-auto">
				<img src="images/logo_mini.png" alt="Magmi - Logo Design by dewi" class="d-inline-block align-top">
			</a>
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTop" aria-controls="navbarTop" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse ml-2" id="navbarTop">
				<ul class="navbar-nav mr-auto">
					<li class="nav-item">
						<a class="nav-link" href="#magmi-run">Run</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#magmi-parameters">Parameters</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#magmi-profile">Profile</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#saveprofile_form">Datasources</a>
					</li>
				</ul>
			</div>
			<span class="version">version: <?php echo Magmi_Version::$version; ?></span>
		</nav>
	</div>
</header>
