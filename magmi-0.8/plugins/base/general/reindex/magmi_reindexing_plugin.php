<?php
class Magmi_ReindexingPlugin extends Magmi_GeneralImportPlugin
{
	protected $_reindex;
	protected $_indexlist="catalog_product_attribute,catalog_product_price,catalog_product_flat,catalog_category_flat,catalog_category_product,cataloginventory_stock,catalog_url,catalogsearch_fulltext";
	
	public function getPluginInfo()
	{
		return array("name"=>"Magmi Magento Reindexer",
					 "author"=>"Dweeves",
					 "version"=>"1.0.5",
					 "url"=>"http://sourceforge.net/apps/mediawiki/magmi/index.php?title=Magmi_Magento_Reindexer");
	}
	
	public function afterImport()
	{
		$this->log("running indexer","info");
		$this->updateIndexes();
		return true;
	}
	
	public function getPluginParamNames()
	{
		return array("REINDEX:indexes","REINDEX:phpcli");
	}
	
	public function getIndexList()
	{
		return $this->_indexlist;
	}
	
	public function updateIndexes()
	{
		//make sure we are not in session
		if(session_id()!=="")
		{
			session_write_close();
		}
		$magdir=Magmi_Config::getInstance()->getMagentoDir();
		$cl=$this->getParam("REINDEX:phpcli")." $magdir/shell/indexer.php";
		$idxlstr=$this->getParam("REINDEX:indexes","");
		$idxlist=explode(",",$idxlstr);
		if(count($idxlist)==0)
		{
			$this->log("No indexes selected , skipping reindexing...","warning");
			return true;
		}
		foreach($idxlist as $idx)
		{
			$tstart=microtime(true);
			$this->log("Reindexing $idx....","info");
			$out = shell_exec("$cl --reindex $idx");
			$this->log($out,"info");
			$tend=microtime(true);
			$this->log("done in ".round($tend-$tstart,2). " secs","info");
			if(Magmi_StateManager::getState()=="canceled")
			{
				exit();
			}			
			flush();
		}
	}
			
	static public function getCompatibleEngines()
	{
		return "Magmi_ProductImportEngine";	
	}
	
	public function isRunnable()
	{
		return array(true,"");
	}
	
	public function initialize($params)
	{
		
	}
}