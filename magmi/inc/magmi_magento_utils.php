<?php
    function getDBInfoFromLocalXML($localxml)
   {
       $entries=array("host"=>null,"username"=>null,"password"=>null,"dbname"=>null);
       if(file_exists($localxml)) {
           $doc = simplexml_load_file($localxml);
           $cnxp = $doc->xpath("//default_setup/connection");
           if (count($cnxp) > 0) {
               $cnx = $cnxp[0];
               foreach ($cnx->children() as $entry) {
                   $en = $entry->getName();
                   if (in_array($en, array_keys($entries))) {

                       $entries[$en] = "".$entry;
                   }
               }
           }
           $entry=$doc->xpath('//db/table_prefix');
           $prefix="".$entry[0];
           $entries["table_prefix"]=$prefix;

       }
       return $entries;
   }