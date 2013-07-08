-- reset stock for a given sku regex;
UPDATE [[tn:cataloginventory_stock_item]] as csi 
JOIN [[tn:catalog_product_entity]] as cpe ON csi.product_id=cpe.entity_id AND cpe.sku REGEXP [[sku_regexp/target sku regexp/.*]]
SET csi.qty=[[qty/quantity/0]];
