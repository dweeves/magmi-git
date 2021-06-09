<?php require_once("magmi_plugin_info.php"); ?>
							<div class="plugininfohover">
								<div class="plugindata">
									<ul>
									<?php

                    $sp = isset($pinfo["sponsorinfo"]);
                    foreach ($pinfo as $pik => $piv) {
                        ?>

										<li <?php if (isset($sp)) {
                            ?> class='sponsored' <?php
                        } ?>><?php
                        if ($pik == "url") {
                            ?>
												<span><?php echo $pik?></span>:<span><a
												href="<?php echo $piv?>" target="_blank">Wiki entry</a></span>
											<?php
                        } elseif ($pik == "sponsorinfo") {
                            ?>
													<span class="sponsor">Sponsored By</span>: <span>
													<?php if (isset($piv['url'])) {
                                ?>
													<a href="<?php echo $piv['url']?>" target="_blank">
													<?php
                            }
                            echo $piv["name"];
                            if (isset($piv['url'])) {
                                ?>
													</a>
													<?php
                            } ?>
													</span>
											<?php
                        } else {
                            ?>
												<span><?php echo $pik?></span>:<span><?php echo $piv ?></span>
											<?php
                        } ?>
										</li>
								<?php
                    }
                    ?>
										</ul>
									<div class="minidoc">
											<?php echo $info?>
										</div>
								</div>
								<?php if (!$plrunnable[0]) {
                        ?>
									<div class="error">
									<pre><?php echo $plrunnable[1]?></pre>
								</div>
								<?php
                    }
                    ?>
								</div>