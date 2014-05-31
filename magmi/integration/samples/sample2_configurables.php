<?php
require_once ("../../inc/magmi_defs.php");
require_once ("../inc/magmi_datapump.php");

/**
 * Define a logger class that will receive all magmi logs *
 */
class TestLogger
{

    /**
     * logging methos
     *
     * @param string $data
     *            : log content
     * @param string $type
     *            : log type
     */
    public function log($data, $type)
    {
        echo "$type:$data\n";
    }
}
/**
 * create a Product import Datapump using Magmi_DatapumpFactory
 */
$dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");
/**
 * Start import session
 * with :
 * - profile : test_ptj
 * - mode : create
 * - logger : an instance of the class defined above
 */

/**
 * FOR THE SAMPLE TO WORK CORRECTLY , YOU HAVE TO DEFINE A test_ptj profile with :
 * UPSELL/CROSS SELL, ITEM RELATER, CATEGORIES IMPORTER/CREATOR selected
 * ON THE FLY INDEXER IS RECOMMENDED (better endimport performance)
 * Reindexer needed also to have products show up on front : select all but "catalog_category_product" & "url_rewrite" (both are handled by on the fly indexer)
 */
$dp->beginImportSession("default", "create", new TestLogger());

/*
 * Create 5000 items , with every 100 : upsell on last 100 even cross sell on last 100 odd related on last 100 every 5 cross sell on last 100 every 10 categories named catX/even or catX/odd with X is thousand of item (using categories plugin)
 */
for ($sku = 0; $sku <= 200; $sku++)
{
    // price : random between $1 & $500
    $item = array("store"=>"admin","type"=>"simple","sku"=>str_pad($sku, 5, "0", STR_PAD_LEFT),"name"=>"item" . $sku,
        "description"=>"test" . $sku,"price"=>rand(1, 500),"min_qty"=>3,"qty"=>"+7");
    // color : radom c0/c10
    $item["color"] = "c" . strval(rand(0, 10));
    
    // now some fun, every 100 items, create some relations
    if ($sku > 99 && $sku % 100 == 0)
    {
        // first, we'll remove all existing relations (upsell/cross sell / related)
        $subskus = array();
        for ($i = $sku - 99; $i < $sku; $i++)
        {
            // related item sku
            $subskus[] = str_pad($i, 5, "0", STR_PAD_LEFT);
        }
        $item["simples_skus"] = implode(",", $subskus);
        $item["type"] = "configurable";
        $item["configurable_attributes"] = "color";
        // cross relate with all skus ending by 2
        $item["xre_skus"] = "re::.*2$";
        // star relate all skus ending with 1
        $item["*re_skus"] = "re::.*1$";
    }
    
    /* import current item */
    $dp->ingest($item);
}
/* end import session, will run post import plugins */
$dp->endImportSession();
 