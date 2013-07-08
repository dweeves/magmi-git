<?php
class Magmi_ReindexingPlugin extends Magmi_GeneralImportPlugin
{
	protected $_reindex;
	protected $_indexlist="catalog_product_attribute,catalog_product_price,catalog_product_flat,catalog_category_flat,catalog_category_product,cataloginventory_stock,catalog_url,catalogsearch_fulltext";
	
	public function getPluginInfo()
	{
		return array("name"=>"Magmi Magento Reindexer",
					 "author"=>"Dweeves",
					 "version"=>"1.0.2");
	}
	
	public function afterImport()
	{
		$this->log("running indexer","info");
		$this->updateIndexes($this->_reindex);
		return true;
	}
	
	public function getPluginParamNames()
	{
		return array("REINDEX:indexes");
	}
	
	public function getIndexList()
	{
		return $this->_indexlist;
	}
	
	public function updateIndexes($idxlist)
	{
		$indexer=realpath("{$this->_mmi->magdir}/shell/indexer.php");
		if(file_exists($indexer))
		{
			if($idxlist=="")
			{
				$this->log("No indexes set to reindex","info");
				return;
			}
			$idxlist=explode(",",$idxlist);
			//reindex using magento command line
			chdir($this->_mmi->magdir);
			$cur=getcwd();
			
			foreach($idxlist as $idx)
			{
				$tstart=microtime(true);
				$this->log("Reindexing $idx....","info");
				shell_exec("php $indexer --reindex $idx");
				$tend=microtime(true);
				$this->log("done in ".round($tend-$tstart,2). " secs","info");
				if(Magmi_StateManager::getState()=="canceled")
				{
					chdir($cur);
					exit();
				}
				
				flush();
			}
			chdir($cur);
		}
		else
		{
			$this->log("Magento indexer not found, you should reindex manually using magento admin","warning");
		}
	}
	
	public function initialize($params)
	{
		$this->_reindex=$this->getParam("REINDEX:indexes",$this->_indexlist);
		
	}
}