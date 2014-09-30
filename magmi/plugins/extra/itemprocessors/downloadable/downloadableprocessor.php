<?php

class DownloadableProcessor extends Magmi_ItemProcessor
{
    protected $_filePath;

    public function initialize($params)
    {
        $this->_filePath = $this->getMagentoDir() . DIRSEP . "media" . DIRSEP . "downloadable" . DIRSEP . "files" .
             DIRSEP . "links" . DIRSEP;
    }

    public function getPluginInfo()
    {
        return array("name"=>"Downloadable products importer","author"=>"Tangkoko SARL","version"=>"1.0.0.1",
            "url"=>"http://store.tangkoko.com/fr/extensions-magento/magmi-downloadable-products-importer-plugin.html");
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        // if item is not configurable, nothing to do
        if ($item["type"] !== "downloadable")
        {
            return true;
        }

        return true;
    }

    public function processItemAfterId(&$item, $params = null)
    {
        // if item is not downloadable, nothing to do
        if ($item["type"] !== "downloadable")
        {
            return true;
        }

        $filename = $item["sku"] . ".zip";
        $sampleFilename = "sample_" . $item["sku"] . ".zip";
        // donnes à importer dans les liens des produits téléchargeable
        if (isset($item["links"]))
        {

            $this->log($item["links"], "debug");
            $links = array();
            $str_links = explode(";", $item["links"]);
            foreach ($str_links as $str_link)
            {
                $arr_link = explode(",", $str_link);
                $link = array();
                foreach ($arr_link as $str_link)
                {
                    $val = preg_split('/[\s:]+[\s]*/', $str_link, 2);
                    $link[$val[0]] = $val[1];
                }
                $links[] = $link;
            }

            $pid = $params["product_id"];

            $existingLinks = $this->getExistingLinks($pid);
            $nbupdate = count($existingLinks);
            $nbdiff = count($links) - count($existingLinks);
            $updateLinks = array();
            try
            {
                if ($nbdiff > 0) // update existing links and add new links (more in xml file than database)
                {
                    $addLinks = array();

                    for ($j = $nbupdate; $j < count($links); $j++)
                    {
                        $addLinks[] = $links[$j];
                    }

                    // ajoute les nouveaux liens
                    $i = 0;

                    foreach ($addLinks as $addLink)
                    {

                        if ($addLink["file"] = $this->copyFile($addLink))
                        {
                            $addLink["link_id"] = $this->addLink($addLink, $pid);
                            $addLink["price_id"] = $this->addLinkPrice($addLink);
                            $addLink["title_id"] = $this->addLinkTitle($addLink);
                            if ($addLink["sample"])
                            {
                                $addLink["sample"] = $this->copyFile($addLink);
                            }
                        }
                        $i++;
                    }
                }
                elseif ($nbdiff < 0)
                { // update existing and delete links (more in database than xml file)
                    $nbdiff = $nbdiff * -1; // number to delete
                    $nbupdate = count($existingLinks) - $nbdiff; // nb links to update = number of existing links - difference
                    $i = 0;
                    $deleteLinks = array();
                    if (count($existingLinks))
                    {

                        $i = 0;

                        $reverse = array_reverse($existingLinks);
                        foreach ($reverse as $deletelink)
                        {
                            if ($i >= $nbdiff)
                                break;
                            $deleteLinks[] = $deletelink["link_id"];
                            $i++;
                        }
                        $this->deleteLinks($deleteLinks);
                    }
                }

                if (count($existingLinks))
                {
                    $i = 0;
                    foreach ($existingLinks as $updatelink)
                    {
                        if ($i >= $nbupdate)
                            break;
                        $links[$i]["link_id"] = $updatelink["link_id"];
                        $updateLinks[] = $links[$i];
                        $i++;
                    }
                }

                if (count($updateLinks))
                {
                    foreach ($updateLinks as $updateLink)
                    {
                        if ($updateLink["file"] = $this->copyFile($updateLink, $filename))
                            $this->updateLink($updateLink);
                    }
                }
            }
            catch (Exception $e)
            {
                die($e->getMessage());
            }
        }
    }

    public function copyFile($link)
    {
        if (preg_match("|.*?://.*|", $link["file"]))
        {
            $filename = $this->getFilename($link["file"]);
        }
        else
        {
            $filename = basename($link["file"]);
        }

        if ($filename)
        {
            $destdir = $this->_filePath . $filename[0] . DIRSEP . $filename[1] . DIRSEP;
            $cpfilename = $destdir . $filename;

            @mkdir($this->_filePath . $filename[0], 0777);
            @mkdir($destdir, 0777);

            if (preg_match("|.*?://.*|", $link["file"]))
            {
                if (is_file($cpfilename))
                    unlink($cpfilename);
                $this->download($link["file"], $cpfilename);
            }
            else
            {
                $fileIdentical = false;

                if (file_exists($cpfilename))
                {
                    $sourceFileMTime = filemtime($link["file"]);
                    $sourceFileFSize = filesize($link["file"]);
                    $targetFileMTime = filemtime($cpfilename);
                    $targetFileFSize = filesize($cpfilename);

                    $fileIdentical = (($sourceFileMTime === $targetFileMTime) && ($sourceFileFSize === $targetFileFSize));
                }

                if (!$fileIdentical)
                {
                    if (!@copy($link["file"], $cpfilename))
                    {
                        unlink($cpfilename);
                        @copy($link["file"], $cpfilename);
                    }
                    // make sure the copied file has the same filemtime as the original file
                    if (file_exists($cpfilename)) {
                        touch($cpfilename, filemtime($link["file"]));
                    }
                }
                else
                {
                    $this->log("Files where identical: skipped copying " . $link["file"] . " to " . $cpfilename, "info");
                }
            }
        }
        else
        {
            $this->log("Le fichier n' pas été trouvé à l'emplacement " . $link["file"], "warning");
            return false;
        }
        return substr($cpfilename, strlen($this->_filePath) - 1);
    }

    /**
     * download files and return hash key content
     */
    public function download($url, $tmp_path)
    {
        $this->log("Téléchargement " . $url, "warning");
        $ch = curl_init($url);
        $fp = fopen($tmp_path, "w+");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $this->log("BEGIN Download " . $url, "warning");
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 404)
        {
            throw new Exception("File " . $url . " not found !");
        }
        curl_exec($ch);
        curl_close($ch);
        $this->log("End Download " . $url, "warning");
        fclose($fp);
    }

    private function getFilename($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $header = curl_exec($ch);
        curl_close($ch);

        return $this->extractCustomHeader('Content-Disposition: attachment; filename=', '\n', $header);
    }

    private function extractCustomHeader($start, $end, $header)
    {
        $pattern = '/' . $start . '(.*?)' . $end . '/';
        if (preg_match($pattern, $header, $result))
        {
            return $result[1];
        }
        else
        {
            return false;
        }
    }

    /**
     * retrieve existing product links
     */
    public function getExistingLinks($pid)
    {
        $dl = $this->tablename('downloadable_link');
        $sql = "select * from downloadable_link where product_id = ?";
        $links = $this->selectAll($sql, array($pid));
        return $links;
    }

    public function deleteLinks($lids)
    {
        $dl = $this->tablename('downloadable_link');
        $sql = "DELETE FROM $dl WHERE link_id in(" . implode(",", $lids) . ")";
        $this->delete($sql);
    }

    public function updateLink($link)
    {
        $dl = $this->tablename('downloadable_link');
        $dlt = $this->tablename('downloadable_link_title');

        $sql = "UPDATE $dl as dl
		JOIN $dlt as dlt ON dl.link_id=dlt.link_id AND dlt.store_id=0
		SET link_file = ?,
		title = ?
		WHERE dl.link_id = ?";

        $this->update($sql, array($link["file"],$link["title"],$link["link_id"]));
    }

    public function addLink($link, $pid)
    {
        if ($link["is_shareable"] == "config")
        {
            $link["is_shareable"] = 2;
        }
        $dl = $this->tablename('downloadable_link');
        $sql = "INSERT INTO $dl(product_id,sort_order, number_of_downloads,is_shareable, link_file,link_type) VALUES (?,?,?,?,?,?)";
        // insert links
        $data = array($pid,$link["sort_order"],$link["number_of_downloads"],$link["is_shareable"],$link["file"],"file");
        // insert in db
        return $this->insert($sql, $data);
    }

    public function addLinkPrice($link)
    {
        $dlp = $this->tablename('downloadable_link_price');
        $sql = "INSERT INTO $dlp(link_id,website_id,price) VALUES (?,?,?)";
        // insert links prices
        $data = array($link["link_id"],0,0);
        return $this->insert($sql, $data);
    }

    public function addLinkTitle($link)
    {
        $dlt = $this->tablename('downloadable_link_title');
        $sql = "INSERT INTO $dlt(link_id,store_id,title) VALUES (?,?,?)";
        // insert links titles
        $data = array($link["link_id"],0,$link["title"]);
        return $this->insert($sql, $data);
    }
}
