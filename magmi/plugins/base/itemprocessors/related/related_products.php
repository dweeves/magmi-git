<?php

class RelatedProducts extends Magmi_ItemProcessor
{

    public function getPluginInfo()
    {
        return array("name"=>"Product relater","author"=>"Dweeves,jwtechniek","version"=>"1.0.3",
            "url"=>$this->pluginDocUrl("Product_relater"));
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
        $related = isset($item["re_skus"]) ? $item["re_skus"] : null;
        $xrelated = isset($item["xre_skus"]) ? $item["xre_skus"] : null;
        $srelated = isset($item["*re_skus"]) ? $item["*re_skus"] : null;
        $pid = $params["product_id"];
        $new = $params["new"];
        
        if (isset($related) && trim($related) != "")
        {
            $rinf = $this->getRelInfos($related);
            if ($new == false)
            {
                $this->deleteRelatedItems($item, $rinf["del"]);
            }
            $this->setRelatedItems($item, $rinf["add"]);
        }
        if (isset($xrelated) && trim($xrelated) != "")
        {
            $rinf = $this->getRelInfos($xrelated);
            if ($new == false)
            {
                $this->deleteXRelatedItems($item, $rinf["del"]);
            }
            $this->setXRelatedItems($item, $rinf["add"]);
        }
        
        if (isset($srelated) && trim($srelated) != "")
        {
            $rinf = $this->getRelInfos($srelated);
            if ($new == false)
            {
                $this->deleteXRelatedItems($item, $rinf["del"], true);
            }
            $this->setXRelatedItems($item, $rinf["add"], true);
        }
    }

    public function deleteRelatedItems($item, $inf)
    {
        $joininfo = $this->buildJoinCond($item, $inf, "cpe2.sku");
        $j2 = $joininfo["join"]["cpe2.sku"];
        if ($j2 != "")
        {
            $sql = "DELETE cplai.*,cpl.*
 		  FROM " . $this->tablename("catalog_product_entity") . " as cpe
 		  JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='relation'
 		  JOIN " . $this->tablename("catalog_product_link") . " as cpl ON cpl.product_id=cpe.entity_id AND cpl.link_type_id=cplt.link_type_id
 		  JOIN " . $this->tablename("catalog_product_link_attribute_int") . " as cplai ON cplai.link_id=cpl.link_id
		  JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.sku!=cpe.sku AND $j2
		  
		  WHERE cpe.sku=?";
            $this->delete($sql, array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"])));
        }
    }

    public function deleteXRelatedItems($item, $inf, $fullcross = false)
    {
        $joininfo = $this->buildJoinCond($item, $inf, "cpe2.sku,cpe.sku");
        $j2 = $joininfo["join"]["cpe2.sku"];
        $j = $joininfo["join"]["cpe.sku"];
        if ($j2 != "")
        {
            
            $sql = "DELETE cplai.*,cpl.*
 		  FROM " . $this->tablename("catalog_product_entity") . " as cpe
 		  JOIN " . $this->tablename("catalog_product_link") . " as cpl ON cpl.product_id=cpe.entity_id
 		  JOIN " . $this->tablename("catalog_product_link_attribute_int") . " as cplai ON cplai.link_id=cpl.link_id
		  JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.sku!=cpe.sku AND (cpe2.sku=? OR $j2)
		  JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='relation'
		  WHERE cpe.sku=? OR $j";
            $this->delete($sql, 
                array_merge(array($item["sku"]), $joininfo["data"]["cpe2.sku"], array($item["sku"]), 
                    $joininfo["data"]["cpe.sku"]));
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
                    $joinconds[$key][] = "$key REGEXP ?";
                    $data[$key][] = $rinf;
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

    public function setRelatedItems($item, $rinfo)
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
			JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.sku!=cpe.sku AND $jinf
			JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='relation'
			WHERE cpe.sku=?";
                $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link") .
                     " (link_type_id,product_id,linked_product_id)  $bsql";
                $data = array_merge($joininfo["data"]["cpe2.sku"], array($item["sku"]));
                $this->insert($sql, $data);
                $this->updateLinkAttributeTable($item["sku"], $joininfo);
            }
        }
    }

    public function setXRelatedItems($item, $rinfo, $fullrel = false)
    {
        if ($this->checkRelated($rinfo) > 0)
        {
            $joininfo = $this->buildJoinCond($item, $rinfo, "cpe.sku,cpe2.sku");
            $j2 = $joininfo["join"]["cpe2.sku"];
            $j = $joininfo["join"]["cpe.sku"];
            if ($j2 != "")
            {
                // insert into link table
                $bsql = "SELECT cplt.link_type_id,cpe.entity_id as product_id,cpe2.entity_id as linked_product_id 
				FROM " . $this->tablename("catalog_product_entity") . " as cpe
				JOIN " . $this->tablename("catalog_product_entity") . " as cpe2 ON cpe2.entity_id!=cpe.entity_id AND (cpe2.sku=? OR $j2)
				JOIN " . $this->tablename("catalog_product_link_type") . " as cplt ON cplt.code='relation'
				WHERE cpe.sku=? OR $j ";
                if (!$fullrel)
                {
                    $bsql .= " AND NOT($j AND $j2)";
                }
                $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link") .
                     " (link_type_id,product_id,linked_product_id)  $bsql";
                $data = array_merge(array($item["sku"]), $joininfo["data"]["cpe2.sku"], array($item["sku"]), 
                    $joininfo["data"]["cpe.sku"]);
                if (!$fullrel)
                {
                    $data = array_merge($data, $joininfo["data"]["cpe.sku"], $joininfo["data"]["cpe.sku"]);
                }
                $this->insert($sql, $data);
                $this->updateLinkAttributeTable($item["sku"], $joininfo);
            }
        }
    }

    public function updateLinkAttributeTable($sku, $joininfo)
    {
        // insert into attribute link attribute int table,reusing the same relations
        $ji = $joininfo["join"];
        $data = array($sku);
        $addcond = "";
        if (isset($ji["cpe.sku"]))
        {
            $addcond = "OR " . $joininfo["join"]["cpe.sku"];
            $data = array_merge($data, $joininfo["data"]["cpe.sku"]);
        }
        // this enable to mass add forcing posution to 0
        $bsql = "SELECT cpl.link_id,cpla.product_link_attribute_id,0 as value
	   	   FROM " . $this->tablename("catalog_product_entity") . " AS cpe
		   JOIN " . $this->tablename("catalog_product_entity") . " AS cpe2 ON cpe2.entity_id!=cpe.entity_id
		   JOIN " . $this->tablename("catalog_product_link_type") . " AS cplt ON cplt.code='relation'
		   JOIN " . $this->tablename("catalog_product_link_attribute") . " AS cpla ON cpla.product_link_attribute_code='position' AND cpla.link_type_id=cplt.link_type_id
		   JOIN " . $this->tablename("catalog_product_link") . " AS cpl ON cpl.link_type_id=cplt.link_type_id AND cpl.product_id=cpe.entity_id AND cpl.linked_product_id=cpe2.entity_id
		   WHERE cpe.sku=? $addcond";
        
        $sql = "INSERT IGNORE INTO " . $this->tablename("catalog_product_link_attribute_int") .
             " (link_id,product_link_attribute_id,value) $bsql";
        $this->insert($sql, $data);
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