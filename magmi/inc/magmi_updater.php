<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 09/04/15
 * Time: 18:20
 */
require_once("properties.php");

class Magmi_Git_Updater
{
    protected $_rooturl="https://api.github.com/repos/dweeves/magmi-git";
    protected $_lastcheckini;
    protected $_conf;

    public function __construct()
    {
        $this->_lastcheckini=__DIR__."/updater.ini";
        $this->_conf=new Properties();
        if(!file_exists($this->_lastcheckini))
        {
            $f=fopen($this->_lastcheckini,"w");
            fclose($f);

        }
        $this->_conf->load($this->_lastcheckini);

    }

    public function callGitHub($fn,&$meta,$extra_hdrs=array())
    {
        $result=array();
        $url=$this->_rooturl."/$fn";
        if(function_exists("curl_init"))
        {
               $req=curl_init($url);
            $options=array(
                CURLOPT_HTTPHEADER=>array_merge(array('User-Agent: CURL'),$extra_hdrs),
                CURLOPT_HEADER=>1,
                  CURLOPT_URL            => $url, // Url cible (l'url la page que vous voulez télécharger)
                  CURLOPT_RETURNTRANSFER => true, // Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
            );
            curl_setopt_array($req,$options);
            $response=curl_exec($req);
            $header_size = curl_getinfo($req, CURLINFO_HEADER_SIZE);
            $code=curl_getinfo($req,CURLINFO_HTTP_CODE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($req);
            $result=json_decode($body);
            $meta=$this->get_headers_from_curl_response($header);
            $meta=$meta[0];
            $meta["HTTP_CODE"]=$code;
           }
        return $result;
    }

    public function get_headers_from_curl_response($headerContent)
    {

        $headers = array();

        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);

        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) -1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
            {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else
                {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }

    public function getLastRelease()
    {
      $lastcheck=$this->_conf->get("RELEASES","lastcheck",null);
      $name=$this->_conf->get("RELEASES","latest",null);
      $meta=array();
      $extra_hdrs=$name==null?array():array($lastcheck);
      $result = $this->callGitHub("releases",$meta,$extra_hdrs);
      $latest = $result[0];
      if($meta["HTTP_CODE"]==200) {
          if (isset($meta["ETag"])) {
              $data = 'If-None-Match: ' . $meta["ETag"];
          }
          else
              if (isset($meta["Last-Modified"])) {
                  $data = 'If-Modified-Since: ' . $meta["Last-Modified"];
              }
          $this->_conf->set("RELEASES", "lastcheck", $data);
          $this->_conf->set("RELEASES", "latest", $latest->name);
          $this->_conf->save();
      }
      return $this->_conf->get("RELEASES","latest");
    }

    public function getLastCommit()
    {
        $meta=array();
        $this->callGitHub("/commits",$meta);
    }
}