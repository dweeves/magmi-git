-- test orphaned values from select/multiselect attribute;
SELECT ea.attribute_code,eaov.value,ea.frontend_input as field_type
FROM [[tn:eav_attribute]] as ea
JOIN [[tn:eav_attribute_option]] as eao ON eao.attribute_id=ea.attribute_id 
JOIN [[tn:eav_attribute_option_value]] as eaov ON eaov.option_id=eao.option_id
LEFT JOIN [[tn:catalog_product_entity_int]] as cpei ON cpei.attribute_id=ea.attribute_id  AND cpei.value=eao.option_id
WHERE ea.frontend_input='select' AND ea.entity_type_id=4  AND ea.is_user_defined=1
GROUP BY eao.option_id
HAVING COUNT(cpei.entity_id)=0
UNION
SELECT ea.attribute_code,eaov.value,ea.frontend_input as field_type
FROM [[tn:eav_attribute]] as ea
JOIN [[tn:eav_attribute_option]] as eao ON eao.attribute_id=ea.attribute_id 
JOIN [[tn:eav_attribute_option_value]] as eaov ON eaov.option_id=eao.option_id
LEFT JOIN [[tn:catalog_product_entity_varchar]] as cpev 
ON cpev.attribute_id=ea.attribute_id  AND cpev.value REGEXP CONCAT('.*,?',eao.option_id,',?.*')
WHERE ea.frontend_input='multiselect' AND ea.entity_type_id=4 AND  ea.is_user_defined=1
GROUP BY eao.option_id
HAVING COUNT(cpev.entity_id)=0
ORDER BY attribute_code,value;
