-- clear images for a given sku regex;
DELETE cpev.* FROM [[tn:catalog_product_entity]] as cpe 
JOIN [[tn:eav_attribute]] as ea ON ea.attribute_code IN ('image','small_image','thumbnail','media_gallery')
JOIN [[tn:catalog_product_entity_varchar]] as cpev ON cpev.entity_id=cpe.entity_id AND cpev.attribute_id=ea.attribute_id
WHERE  cpe.sku REGEXP [[sku_regexp/target sku regexp/.*]];
-- clear images for a given sku regex;
DELETE cpemg.* FROM [[tn:catalog_product_entity]] as cpe 
JOIN [[tn:eav_attribute]] as ea ON ea.attribute_code IN ('image','small_image','thumbnail','media_gallery')
JOIN [[tn:catalog_product_entity_media_gallery]] as cpemg ON cpemg.entity_id=cpe.entity_id AND cpemg.attribute_id=ea.attribute_id
WHERE cpe.sku REGEXP [[sku_regexp/target sku regexp/.*]];