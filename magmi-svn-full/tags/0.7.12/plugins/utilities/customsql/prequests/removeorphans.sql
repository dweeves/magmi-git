-- remove orphaned values from select/multiselect attribute;
DELETE FROM [[tn:eav_attribute_option]] WHERE option_id IN (SELECT candidates.option_id FROM (SELECT eao.option_id,eaov.value,cpei.entity_id,ea.attribute_id FROM [[tn:eav_attribute]] as ea
JOIN [[tn:eav_attribute_option]] as eao ON eao.attribute_id=ea.attribute_id 
JOIN [[tn:eav_attribute_option_value]] as eaov ON eaov.option_id=eao.option_id
LEFT JOIN [[tn:catalog_product_entity_int]] as cpei ON cpei.attribute_id=ea.attribute_id  AND cpei.value=eao.option_id
WHERE ea.attribute_code=[[attcode/attribute code]]
HAVING cpei.entity_id IS NULL) as candidates);
