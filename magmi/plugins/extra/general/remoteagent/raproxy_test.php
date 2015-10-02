<?php
require_once('magmi_remoteagent_proxy.php');

$rap = new Magmi_RemoteAgent_Proxy('ftp://seb:dweeves@localhost/magmi_svn',
    'http://localhost:8301/plugins/extra/general/remoteagent/magmi_remoteagent.php');
echo $rap->getVersion();
