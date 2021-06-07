DELETE * FROM catalog_product_attribute JOIN (SELECT cpsa.product_id,cpsa.attribute_id,ea.attribute_code
FROM catalog_product_super_attribute as cpsa 
JOIN eav_attribute as ea ON ea.attribute_id=cpsa.attribute_id
JOIN catalog_product_super_link AS cpsl ON cpsl.parent_id=cpsa.product_id
JOIN catalog_product_entity as cpe ON cpe.entity_id=cpsl.product_id
WHERE NOT exists
(SELECT * FROM catalog_product_entity_int as cpei WHERE cpei.entity_id=cpe.entity_id AND cpei.attribute_id=cpsa.attribute_id)
GROUP BY cpsa.product_id,cpsa.attribute_id) as s1
WHERE cpsa.product_id=s1.product_id AND cpsa.attribute_id=s1.attribute_id