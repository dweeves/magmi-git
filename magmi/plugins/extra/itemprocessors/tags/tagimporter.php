<?php

/**
 * Class Tier price processor
 * @author dweeves
 *
 * This imports tier prices for columns names called "tags:"
 */
class TagProcessor extends Magmi_ItemProcessor
{
    protected $_useridcache = array();
    protected $_tagidcache = array();

    public function getPluginInfo()
    {
        return array("name"=>"Product Tags Importer","author"=>"Dweeves,Pawel Kazakow",
            "sponsorinfo"=>array("name"=>"Pawel Kazakow","url"=>"http://xonu.de"),"version"=>"0.0.3",
            "url"=>$this->pluginDocUrl("Tag_importer"));
    }

    public function createTag($taginfo)
    {
        $sql = "INSERT INTO " . $this->tablename("tag") . " (name,status) VALUES (?,?)
				  ON DUPLICATE KEY UPDATE status=values(status) ";
        $tagid = $this->insert($sql, array($taginfo["name"], $taginfo["status"]));
        return $tagid;
    }

    public function getTagId($taginfo, $create = true)
    {
        // cache key = tag name + status
        $tagid = null;
        $ck = $taginfo["name"] . "/" . $taginfo["status"];
        if (isset($this->_tagidcache[$ck])) {
            $tagid = $this->_tagidcache[$ck];
        } else {
            // find lowercase
            $sql = "SELECT tag_id FROM " . $this->tablename("tag") . " WHERE LOWER(name)=LOWER(?)";
            $tagid = $this->selectone($sql, $taginfo["name"], "tag_id");
            if ($tagid == null && $create) {
                $tagid = $this->createTag($taginfo);
            }
            // add to cache
            $this->_tagidcache[$ck] = $tagid;
            // limit tag cache size
            if (count($this->_tagidcache) > 2000) {
                array_shift($this->_tagidcache);
            }
        }
        return $tagid;
    }

    // clearing tags associated to item
    public function clearItemTags($item, $pid, $sids)
    {
        // if we don't have any relative, clear tags for item
        $sql = "DELETE FROM " . $this->tablename("tag_relation") . " WHERE product_id=? AND store_id IN (" .
             $this->arr2values($sids) . ")";
        $this->delete($sql, array_merge(array($pid), $sids));
    }

    // handleItemTags
    public function handleItemTags($item, $pid, $sids, $addtags, $remtags, $hasrel)
    {
        $tr = $this->tablename("tag_relation");
        // no relative notation, clear existing tags from item
        if (!$hasrel) {
            $this->clearItemTags($item, $pid, $sids);
        }
        // inserts with user bound tag
        // iterate on tag adding
        foreach ($addtags as $taginf) {
            $tagid = $this->getTagId($taginf, true);
            foreach ($sids as $sid) {
                $uid = isset($taginf["user"]) ? $taginf["user"] : 1;
                $tdata = array($tagid,$pid,$sid,$uid);
                $sql = "INSERT IGNORE INTO $tr (tag_id,product_id,store_id,customer_id) VALUES (?,?,?,?)";
                $this->insert($sql, $tdata);
            }
        }

        // iterate on tag removal
        $tids = array();
        $uids = array();
        foreach ($remtags as $taginf) {
            $tagid = $this->getTagId($taginf, false);
            if (isset($tagid)) {
                $tids[] = $tagid;
                $uids[] = $taginf["user"];
            }
        }
        // perform delete
        if (count($tids) > 0) {
            $sql = "DELETE FROM $tr  WHERE tag_id IN (" . $this->arr2values($tids) . ") AND customer_id IN(" .
                 $this->arr2values($uids) . ") AND store_id IN (" . $this->arr2values($sids) . ")";
            $this->delete($sql, array_merge($tids, $uids, $sids));
        }
    }

    public function endImport()
    {
        $this->log("Cleaning orphan tags", "info");
        $tr = $this->tablename("tag_relation");
        $ta = $this->tablename("tag");
        $sql = "DELETE ta.* FROM $ta as ta
					LEFT JOIN $tr as tr ON tr.tag_id=ta.tag_id
					WHERE tr.tag_id IS NULL";
        $this->delete($sql);
    }

    public function getUserIdFromEmail($email, $default)
    {
        // check in cache
        $ak = array_keys($this->_useridcache);
        if (in_array($email, $ak)) {
            $id = $this->_useridcache[$email];
        } else {
            // perform search
            $sql = "SELECT entity_id FROM " . $this->tablename("customer_entity") . " WHERE email=?";
            $id = $this->selectone($sql, $email, "entity_id");
            if (!isset($id)) {
                $id = $default;
            }
            $this->_useridcache[$email] = $id;
            // limit cache size
            if (count($this->_useridcache) > 500) {
                array_shift($this->_useridcache);
            }
        }
        return $id;
    }

    public function getUserId($userinf)
    {
        // if id provided , use it
        $userid = is_int($userinf) ? $userinf : $this->getUserIdFromEmail($userinf, 1);
        return $userid;
    }

    public function parseTag($tag)
    {
        $taginfo = array("name"=>null,"status"=>1,"user"=>1);
        $tagparts = explode("::", $tag);
        $tn = $tagparts[0];
        // matching pending status name, remove whitespaces on capture
        if (preg_match("|^\(\s*(.*)\s*\)$|", $tn, $matches)) {
            $tn = $matches[1];
            $taginfo["status"] = 0;
        }
        $taginfo["name"] = $tn;
        if (count($tagparts) > 1) {
            $taginfo["user"] = $this->getUserId($tagparts[1]);
        }
        return $taginfo;
    }

    public function processItemAfterId(&$item, $params = null)
    {
        // if no tags column, do nothing
        if (!isset($item["tags"])) {
            return;
        }
        $hasrel = false;
        // tags separator is ,
        $taglist = explode(",", $item["tags"]);
        foreach ($taglist as $tag) {
            if ($tag[0] == "+" || $tag[0] == "-") {
                $hasrel = true;
                break;
            }
        }

        // iterate on tags
        $addtags = array();
        $remtags = array();
        foreach ($taglist as $tag) {
            $tag = trim($tag);
            $dir = getRelative($tag);
            $taginfo = $this->parseTag($tag);

            if ($dir == "+") {
                $addtags[] = $taginfo;
            } else {
                $remtags[] = $taginfo;
            }
        }

        $sids = $this->getItemStoreIds($item);
        // we need a real store id , not admin so let's find one
        if (count($sids) == 1 && $sids[0] == 0) {
            $sql = "SELECT store_id FROM " . $this->tablename("core_store") .
                 " WHERE website_id>0 AND is_active=1 ORDER BY website_id LIMIT 1 ";
            $sid = $this->selectone($sql, null, "store_id");
            if ($sid !== null) {
                $sids = array($sid);
            }
        }
        $pid = $params["product_id"];

        $this->handleItemTags($item, $pid, $sids, $addtags, $remtags, $hasrel);
    }
}
