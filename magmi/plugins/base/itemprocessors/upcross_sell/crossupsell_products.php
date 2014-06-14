<?php

class CrossUpsellProducts extends Magmi_ItemProcessor
{

    public function getPluginInfo()
    {
        return array("name"=>"Cross/Upsell Importer","author"=>"Dweeves","version"=>"1.0.3",
            "url"=>$this->pluginDocUrl("Cross/Upsell_Importer"));
    }

    public function checkRelated(&$rinfo)
    {
        if (count($rinfo["direct"]) > 0)
        {
            $sql = "SELECT testid.sku,cpe.sku as esku FROM " . $this->arr2select($rinfo["direct"], "sku") . " AS testid
  	LEFT JOIN " . $this->tablename("catalog_product_entity") . " as cpe ON cpe.sku=testid.sku
  	WHERE testid.sku NOT LIKE '%re::%'
  	HAVING esku IS NULL";
            $result = $this->selectAll($sql, $rinfo["direct"]);
            
            $to_delete = array();
            foreach ($result as $row)
            {
                $this->log("Unknown related sku " . $row["sku"], "warning");
                $to_delete[] = $row["sku"];
            }
            $rinfo["direct"] = array_diff($rinfo["direct"], $to_delete);
        }
        return count($rinfo["direct"]) + count($rinfo["re"]);
    }

    public function processItemAfterId(&$item, $params = null)
    {
        $usell = isset($item["us_skus"]) ? $item["us_skus"] : null;
        $csell = isset($item["cs_skus"]) ? $item["cs_skus"] : null;
        $pid = $params["product_id"];
        $new = $params["new"];
        
        if (isset($usell) && trim($usell) != "")
        {
            $rinf = $this->getRelInfos($usell);
            if ($new == false)
            {
                $this->deleteUsellItems($item, $rinf["del"]);
            }
            $this->setUsellItems($item, $rinf["add"]);
        }
        if (isset($csell) && trim($csell) != "")
        {
            $rinf = $this->getRelInfos($csell);
            if ($new == false)
            {
                $this->deleteCSellItems($item, $rinf["del"]);
            }
            $this->setCSellItems($item, $rinf["add"]);
        }
    }

    public function deleteUSellItems($item, $inf)
    {
        $joininfo = $this->buildJoinCond($item, $inf, "cpe2.sku");
        $j2 = $joininfo["join"]["cpe2.sku"];
        if ($j2 != "")
        {
            $sql = "DELETE cplai.*,cpl.*
 		  FROM " . $this->tablename("catalog_product_entity") . " as cpe
 		  JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='up_sell'
		  JOIN " . $this->tablename("catalog_product_link") . " as cpl ON cpl.product_id=cpe.entity_id AND cpl.link_type_id=cplt.link_type_id
 		  JOIN " . $this->tablename("catalog_product_link_attribute_int") . " as cplai ON cplai.link_id=cpl.link_id
		  JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.entity_id!=cpe.entity_id AND $j2
		  WHERE cpe.sku=?";
            $this->delete($sql, array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"])));
        }
    }

    public function deleteCSellItems($item, $inf)
    {
        $joininfo = $this->buildJoinCond($item, $inf, "cpe2.sku");
        $j2 = $joininfo["join"]["cpe2.sku"];
        if ($j2 != "")
        {
            
            $sql = "DELETE cplai.*,cpl.*
 		  FROM " . $this->tablename("catalog_product_entity") . " as cpe
		  JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='cross_sell'
 		  JOIN " . $this->tablename("catalog_product_link") . " as cpl ON cpl.product_id=cpe.entity_id AND cpl.link_type_id=cplt.link_type_id
 		  JOIN " . $this->tablename("catalog_product_link_attribute_int") . " as cplai ON cplai.link_id=cpl.link_id
		  JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.entity_id!=cpe.entity_id AND $j2
		  WHERE cpe.sku=?";
            $this->delete($sql, array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"])));
        }
    }

    public function getDirection(&$inf)
    {
        $dir = "+";
        if ($inf[0] == "-" || $inf[0] == "+")
        {
            $dir = $inf[0];
            $inf = substr($inf, 1);
        }
        return $dir;
    }

    public function getRelInfos($relationdef)
    {
        $relinfos = explode(",", $relationdef);
        $relskusadd = array("direct"=>array(),"re"=>array());
        $relskusdel = array("direct"=>array(),"re"=>array());
        foreach ($relinfos as $relinfo)
        {
            $rinf = explode("::", $relinfo);
            if (count($rinf) == 1)
            {
                if ($this->getDirection($rinf[0]) == "+")
                {
                    $relskusadd["direct"][] = $rinf[0];
                }
                else
                {
                    $relskusdel["direct"][] = $rinf[0];
                }
            }
            
            if (count($rinf) == 2)
            {
                $dir = $this->getDirection($rinf[0]);
                if ($dir == "+")
                {
                    switch ($rinf[0])
                    {
                        case "re":
                            $relskusadd["re"][] = $rinf[1];
                            break;
                    }
                }
                else
                {
                    switch ($rinf[0])
                    {
                        case "re":
                            $relskusdel["re"][] = $rinf[1];
                            break;
                    }
                }
            }
        }
        
        return array("add"=>$relskusadd,"del"=>$relskusdel);
    }

    public function buildJoinCond($item, $rinfo, $keys)
    {
        $joinconds = array();
        $joins = array();
        $klist = explode(",", $keys);
        foreach ($klist as $key)
        {
            $data[$key] = array();
            $joinconds[$key] = array();
            if (count($rinfo["direct"]) > 0)
            {
                $joinconds[$key][] = "$key IN (" . $this->arr2values($rinfo["direct"]) . ")";
                $data[$key] = array_merge($data[$key], $rinfo["direct"]);
            }
            if (count($rinfo["re"]) > 0)
            {
                foreach ($rinfo["re"] as $rinf)
                {
                    if ($rinf != ".*")
                    {
                        $joinconds[$key][] = "$key REGEXP ?";
                        $data[$key][] = $rinf;
                    }
                    else
                    {
                        $joinconds[$key][] = "?";
                        $data[$key][] = 1;
                    }
                }
            }
            $joins[$key] = implode(" OR ", $joinconds[$key]);
            if ($joins[$key] != "")
            {
                $joins[$key] = "({$joins[$key]})";
            }
        }
        return array("join"=>$joins,"data"=>$data);
    }

    public function setUSellItems($item, $rinfo)
    {
        if ($this->checkRelated($rinfo) > 0)
        
        {
            $joininfo = $this->buildJoinCond($item, $rinfo, "cpe2.sku");
            $jinf = $joininfo["join"]["cpe2.sku"];
            if ($jinf != "")
            {
                // insert into link table
                $bsql = "SELECT cplt.link_type_id,cpe.entity_id as product_id,cpe2.entity_id as linked_product_id 
			FROM " . $this->tablename("catalog_product_entity") . " as cpe
			JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.entity_id!=cpe.entity_id AND $jinf
			JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='up_sell'
			WHERE cpe.sku=?";
                $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link") .
                     " (link_type_id,product_id,linked_product_id)  $bsql";
                $data = array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"]));
                $this->insert($sql, $data);
                $this->updateLinkAttributeTable($item["sku"], $joininfo, 'up_sell');
            }
        }
    }

    public function setCSellItems($item, $rinfo)
    {
        if ($this->checkRelated($rinfo) > 0)
        
        {
            $joininfo = $this->buildJoinCond($item, $rinfo, "cpe2.sku");
            $j2 = $joininfo["join"]["cpe2.sku"];
            if ($j2 != "")
            {
                // insert into link table
                $bsql = "SELECT cplt.link_type_id,cpe.entity_id as product_id,cpe2.entity_id as linked_product_id 
			FROM " . $this->tablename("catalog_product_entity") . " as cpe
			JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.entity_id!=cpe.entity_id AND $j2
			JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='cross_sell'
			WHERE cpe.sku=?";
                $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link") .
                     " (link_type_id,product_id,linked_product_id)  $bsql";
                $data = array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"]));
                $this->insert($sql, $data);
                $this->updateLinkAttributeTable($item["sku"], $joininfo, 'cross_sell');
            }
        }
    }

    public function updateLinkAttributeTable($sku, $joininfo, $reltype)
    {
        // insert into attribute link attribute int table,reusing the same relations
        // this enable to mass add
        $bsql = "SELECT cpl.link_id,cpla.product_link_attribute_id,0 as value
	   	   FROM " . $this->tablename("catalog_product_entity") . " AS cpe
		   JOIN " . $this->tablename("catalog_product_entity") . " AS cpe2 ON cpe2.entity_id!=cpe.entity_id
		   JOIN " . $this->tablename("catalog_product_link_type") . " AS cplt ON cplt.code=?
		   JOIN " . $this->tablename("catalog_product_link_attribute") . " AS cpla ON cpla.product_link_attribute_code='position' AND cpla.link_type_id=cplt.link_type_id
		   JOIN " . $this->tablename("catalog_product_link") . " AS cpl ON cpl.link_type_id=cplt.link_type_id AND cpl.product_id=cpe.entity_id AND cpl.linked_product_id=cpe2.entity_id
		   WHERE cpe.sku=?";
        $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link_attribute_int") .
             " (link_id,product_link_attribute_id,value) $bsql";
        $this->insert($sql, array($reltype,$sku));
    }

    public function afterImport()
    {
        // remove maybe inserted doubles
        $cplai = $this->tablename("catalog_product_link_attribute_int");
        $sql = "DELETE cplaix FROM $cplai as cplaix 
 		  WHERE cplaix.value_id IN 
 		  (SELECT s1.value_id FROM 
 		  	(SELECT cplai.link_id,cplai.value_id,MAX(cplai.value_id) as latest 
 		  		FROM $cplai as cplai 
 		  		GROUP BY cplai.link_id
				HAVING cplai.value_id!=latest) 
			as s1)";
        $this->delete($sql);
    }

    static public function getCategory()
    {
        return "Related Products";
    }
}