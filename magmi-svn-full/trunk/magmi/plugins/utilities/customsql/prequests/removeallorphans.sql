-- remove orphaned values from select/multiselect attribute;
DELETE FROM [[tn:eav_attribute_option]] WHERE option_id IN (SELECT candidates.option_id FROM (SELECT eao.option_id,eaov.value,COUNT(cpei.entity_id) as ce,ea.attribute_id,ea.attribute_code 
FROM [[tn:eav_attribute]] as ea
JOIN [[tn:eav_attribute_option]] as eao ON eao.attribute_id=ea.attribute_id 
JOIN [[tn:eav_attribute_option_value]] as eaov ON eaov.option_id=eao.option_id
LEFT JOIN [[tn:catalog_product_entity_int]] as cpei ON cpei.attribute_id=ea.attribute_id  AND cpei.value=eao.option_id
WHERE ea.frontend_input='select' AND ea.entity_type_id=4  AND ea.is_user_defined=1
GROUP BY eao.option_id
HAVING ce=0
UNION
SELECT eao.option_id,eaov.value,COUNT(cpev.entity_id) as ce,ea.attribute_id,ea.attribute_code 
FROM [[tn:eav_attribute]] as ea
JOIN [[tn:eav_attribute_option]] as eao ON eao.attribute_id=ea.attribute_id 
JOIN [[tn:eav_attribute_option_value]] as eaov ON eaov.option_id=eao.option_id
LEFT JOIN [[tn:catalog_product_entity_varchar]] as cpev 
ON cpev.attribute_id=ea.attribute_id  AND cpev.value REGEXP CONCAT('.*,?',eaov.option_id,',?.*')
WHERE ea.frontend_input='multiselect' AND ea.entity_type_id=4 AND  ea.is_user_defined=1
GROUP BY eao.option_id
HAVING ce=0
) as candidates);