<?php

class Magmi_ValueParser
{
    
    public static function getParseInfo($pvalue,$dictarray)
    {
        $parseinfo=array();

        foreach ($dictarray as $key=>$v)
        {  
            if(preg_match("|\{\{\s*(.*?)\s*\}\}|s", $pvalue,$matches))
            {
                if(!isset($parseinfo["complex"])){
                    $parseinfo["complex"]=array();
                }
                for($i=1;$i<count($matches);$i++)
                {
                    $parseinfo["complex"][]=$matches[$i];
                }
            }

            if(preg_match("|\{$key\.(.*?)\}|", $pvalue,$matches))
            {
                if(!isset($parseinfo["simple"]))
                {
                    $parseinfo["simple"] = array("$key"=>array());
                }

                for($i=1;$i<count($matches);$i++)
                {
                    $parseinfo["simple"][$key]=$matches[$i];
                }
            }
        }
        return $parseinfo;
    }
    
    public static function parseValue($pvalue, $dictarray)
    {
        $matches = array();
        $rep = "";
        $renc = "<-XMagmi_Enc->";

        foreach ($dictarray as $key => $vals) {
            // Unsure of cause for NULL $vals, but this avoids messages in the error log
            if ($vals === null) {
                continue;
            }

            $ik = array_keys($vals);
            
            // replace base values
            while (preg_match("|\{$key\.(.*?)\}|", $pvalue, $matches)) {
                foreach ($matches as $match) {
                    if ($match != $matches[0]) {
                        if (in_array($match, $ik)) {
                            $rep = $renc . str_replace('"', '\\"', $dictarray[$key][$match]) . $renc;
                        } else {
                            $rep = "";
                        }
                        $pvalue = str_replace($matches[0], $rep, $pvalue);
                    }
                }
                unset($matches);
            }
        }

        // replacing expr values
        while (preg_match("|\{\{\s*(.*?)\s*\}\}|s", $pvalue, $matches)) {
            foreach ($matches as $match) {
                if ($match != $matches[0]) {
                    $code = trim($match);
                    $code = str_replace($renc, '"', $code);
                    $code = str_replace("'\"","'",$code);
                    $code = str_replace("\"'","'",$code);

                    $rep = eval("return ($code);");
                    // escape potential "{{xxx}}" values in interpreted target
                    // so that they won't be reparsed in next round
                    $rep = preg_replace("|\{\{\s*(.*?)\s*\}\}|s", "____$1____", $rep);
                    $pvalue = str_replace($matches[0], $rep, $pvalue);
                }
            }
        }

        // unescape matches
        $pvalue = preg_replace("|____(.*?)____|s", '{{$1}}', $pvalue);
        // single replaced values
        $pvalue = str_replace($renc, '', $pvalue);
        return "" . $pvalue;
    }
}
