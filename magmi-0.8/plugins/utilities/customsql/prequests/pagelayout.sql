-- force  page layout for all products that have wrong one;
INSERT INTO [[tn:catalog_product_entity_varchar]] (entity_type_id,attribute_id,store_id,entity_id,value)
SELECT cpe.entity_type_id,ea.attribute_id,0,cpe.entity_id,'' FROM [[tn:catalog_product_entity]] AS cpe
JOIN [[tn:eav_attribute]] as ea ON ea.attribute_code='page_layout' AND ea.entity_type_id=cpe.entity_type_id
JOIN [[tn:catalog_product_entity_varchar]] as cpev ON cpev.attribute_id=ea.attribute_id AND cpev.entity_id=cpe.entity_id
WHERE cpev.value='No layout update' 
ON DUPLICATE KEY UPDATE value=VALUES(`value`)