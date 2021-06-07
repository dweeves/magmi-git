<?php
require_once("../../inc/magmi_defs.php");
require_once("../inc/magmi_datapump.php");

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
$dp->beginImportSession("test_ptj", "create", new TestLogger());

/*
 * Create 5000 items , with every 100 : upsell on last 100 even cross sell on last 100 odd related on last 100 every 5 cross sell on last 100 every 10 categories named catX/even or catX/odd with X is thousand of item (using categories plugin)
 */
for ($sku = 0; $sku < 5000; $sku++) {
    // create item category path array
    // catX/even or catX/odd, X being the 1000's of the item
    $cats = array("cat" . strval(intval($sku / 1000)));
    if ($sku % 2 == 0) {
        $cats[] = "even";
    } else {
        $cats[] = "odd";
    }
    // create item to import
    // sku : XXXXX , 5 numbers , padded left with current loop counter as sku
    // name : itemXXXXX
    // description : testXXXXX
    // price : random between $1 & $500
    // categories : the ones built above
    $item = array("sku"=>str_pad($sku, 5, "0", STR_PAD_LEFT),"name"=>"item" . $sku,"description"=>"test" . $sku,
        "price"=>rand(1, 500),"categories"=>implode("/", $cats));
    // now some fun, every 100 items, create some relations
    if ($sku > 99 && $sku % 100 == 0) {
        // first, we'll remove all existing relations (upsell/cross sell / related)
        $upsell = array("-re::.*");
        $csell = array("-re::.*");
        $re = array("-re::.*");
        $xre = array();
        for ($i = $sku - 99; $i < $sku; $i++) {
            // related item sku
            $rsku = str_pad($i, 5, "0", STR_PAD_LEFT);
            // add upselling on each odd item in the 100 before the current
            if ($i % 2 == 1) {
                $upsell[] = $rsku;
            } else {
                // add cross sell on each even item in the 100 before the current

                $csell[] = $rsku;
            }

            // on each 10 before, cross relate
            if ($i % 10 == 0) {
                $xre[] = "-$rsku";
            } else {
                // on each 5 before , single relate
                if ($i % 5 == 0) {
                    $re[] = $rsku;
                }
            }
        }
        // fill upsell with the computed skus from rules above
        $item["us_skus"] = implode(",", $upsell);
        // fill cross sell with the computed skus from rules above
        $item["cs_skus"] = implode(",", $csell);
        // fill single related with the computed skus from rules above
        $item["re_skus"] = implode(",", $re);
        // fill cross related with the computed skus from rules above
        $item["xre_skus"] = implode(",", $xre);
    }
    /* import current item */
    $dp->ingest($item);
}
/* end import session, will run post import plugins */
$dp->endImportSession();
