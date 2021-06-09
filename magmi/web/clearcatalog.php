<?php

require_once('security.php');
ini_set('display_errors', 1);
try {
    require_once(dirname(dirname(__FILE__)) . '/inc/maintenance/clearproducts.php');
    $ccutil = new CatalogClearUtility();
    $ccutil->connect();
    $ccutil->clearProducts();
    $ccutil->disconnect();
    echo "Catalog cleared!";
} catch (Exception $e) {
    echo $e;
}
