-- remove categories based on name;
DELETE ccp,cce,cur,ccev FROM [[tn:catalog_category_entity]] AS cce
LEFT JOIN [[tn:catalog_category_product]] AS ccp ON ccp.category_id=cce.entity_id
JOIN [[tn:eav_attribute]] AS ea ON ea.attribute_code='children' 
JOIN [[tn:eav_attribute]] AS ea2 ON ea2.attribute_code='name' AND ea2.entity_type_id=ea.entity_type_id
JOIN [[tn:catalog_category_entity_varchar]] AS ccev ON ccev.value REGEXP [[cname/category name regex/.*]] AND ccev.entity_id=cce.entity_id
JOIN [[tn:core_url_rewrite]] AS cur ON cur.category_id=cce.entity_id
WHERE cce.level>1
