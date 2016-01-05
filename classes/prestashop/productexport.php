<?php
/**
 * Copyright (c) 2015 Twenga 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions: 
 * 
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software. 
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE 
 * OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * @author    Twenga
 * @copyright 2016 Twenga
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class Twenga_Prestashop_ProductExport extends \ModuleAdminController
{
    public static function getProducts($langId)
    {
        $config = \Configuration::getMultiple(array('PS_STOCK_MANAGEMENT', 'PS_ORDER_OUT_OF_STOCK'));

        if ($config['PS_ORDER_OUT_OF_STOCK'] == 1) {
            $inStockDefault = "R";
        } else {
            $inStockDefault = "N";
        }

        $query = 'SELECT
    p.id_product as id_product,
    pa.id_product_attribute as id_product_attribute,
    p.id_product as merchant_ref,
    IF( pa.id_product_attribute,
        CONCAT(p.id_product,"D",pa.id_product_attribute),
        p.id_product ) as merchant_id,
    COALESCE(pa.ean13, pa.upc, p.ean13, p.upc, null) as upc_ean,
    pl.NAME as designation,
    CONCAT(pl.description_short," ",attr.product_attribute) as description,
    m.name as brand,
    cat.cat_tree as category,
    IF(pa.supplier_reference!="",pa.supplier_reference,s.name) as manufacturer_id,
    ROUND(
        IF(
            pa.price is not null AND pa.price > 0,
            p.price+pa.price,
            p.price
        ) *
        IF (tax.rate is not null, (1 + tax.rate/100), 1)
        ,2
    ) as price,
 ROUND(
        IF(
            p.unit_price_ratio > 0 AND (p.unity <> "" OR p.unity is not null) ,
            (IF(pa.price is not null, p.price+pa.price, p.price)
            + IF(pa.unit_price_impact , pa.unit_price_impact , 0)) * IF(tax.rate is not null, (1 + tax.rate/100), 1)
            / p.unit_price_ratio,
            IF(pa.price is not null, p.price+pa.price, p.price) * IF(tax.rate is not null, (1 + tax.rate/100), 1)
        ), 2
    ) as unit_price,
    IF(p.additional_shipping_cost > 0, ROUND(p.additional_shipping_cost, 2), NULL) as shipping_cost,
    IF(p.ecotax > 0, ROUND(p.ecotax, 2), NULL) as ecotax,
    IF(
        ' . bqSQL($config['PS_STOCK_MANAGEMENT']) . ' = 1,
        IF(stockattr.quantity>0, stockattr.quantity, stock.quantity),
        1) AS availability,
    IF( ' . bqSQL($config['PS_STOCK_MANAGEMENT']) . ' = 1,
        IF(stockattr.quantity is null,
            IF(stock.quantity>0,
                "Y",
                CASE stock.out_of_stock
                    WHEN 2 THEN "' . bqSQL($inStockDefault) . '"
                    WHEN 1 THEN "R"
                    ELSE "N"
                END
            )
            ,
            IF(stockattr.quantity>0,
                "Y",
                CASE stockattr.out_of_stock
                    WHEN 2 THEN "' . bqSQL($inStockDefault) . '"
                    WHEN 1 THEN "R"
                    ELSE "N"
                END
            )
        ),
    "Y") AS in_stock,
    IF(p.`condition` = "new", 0, 1) as `condition`,

    tax.rate as tax_rate,
    attr.product_url_anchor,
    IF(p.visibility = "none", 0, 1) as item_display,
    p.unit_price_ratio as unit_price_ratio,
    pa.unit_price_impact as unit_price_impact,
    IF(pa.wholesale_price>0, pa.wholesale_price, p.wholesale_price) *
        IF(tax.rate is not null, (1 + tax.rate/100), 1) as wholesale_price,
    pl.link_rewrite,
    COALESCE(pai.id_image, i.id_image) as id_image
FROM ' . _DB_PREFIX_ . 'product p
INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_shop = 1 )
INNER JOIN (
    select cl.id_category, IF(pcl.name IS NULL, cl.name, CONCAT(pcl.name, " > ", cl.name)) as cat_tree
    from ' . _DB_PREFIX_ . 'category c
    inner join ' . _DB_PREFIX_ . 'category_lang cl on cl.id_category = c.id_category
    left join ' . _DB_PREFIX_ . 'category pc on pc.id_category = c.id_parent
    left join ' . _DB_PREFIX_ . 'category_lang pcl on pcl.id_category = pc.id_category
    where cl.id_lang=1 and (pcl.id_lang=1 OR pcl.id_lang is null)
) cat ON cat.id_category = p.id_category_default
LEFT JOIN ' . _DB_PREFIX_ . 'stock_available stock
    ON (stock.id_product = p.id_product AND stock.id_product_attribute = 0)
LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa
    ON (pa.id_product = p.id_product)
LEFT JOIN (
    SELECT rate, id_tax_rules_group
    FROM ' . _DB_PREFIX_ . 'tax
    INNER JOIN ' . _DB_PREFIX_ . 'tax_rule
    USING(id_tax) LIMIT 1) tax ON tax.id_tax_rules_group = p.id_tax_rules_group
LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_image pai ON pai.id_product_attribute = pa.id_product_attribute
LEFT JOIN ' . _DB_PREFIX_ . 'image i ON i.id_product = p.id_product
LEFT JOIN ' . _DB_PREFIX_ . 'supplier s ON (s.id_supplier = p.id_supplier)
LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (m.id_manufacturer = p.id_manufacturer)
LEFT JOIN (
    SELECT
        pa.id_product_attribute,
        GROUP_CONCAT( CONCAT(agl.public_name, ": ", al.name) SEPARATOR ", ") as product_attribute,
        LCASE(
            CONCAT("#/", GROUP_CONCAT( CONCAT(a.id_attribute, "-", agl.public_name, "-", al.name) SEPARATOR "/"))
        ) as product_url_anchor
    FROM ' . _DB_PREFIX_ . 'product_attribute pa
    INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac
        ON pac.id_product_attribute = pa.id_product_attribute
    INNER JOIN ' . _DB_PREFIX_ . 'attribute a
        ON a.id_attribute = pac.id_attribute
    INNER JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl
        ON a.id_attribute_group = agl.id_attribute_group AND agl.id_lang=1
    INNER JOIN ' . _DB_PREFIX_ . 'attribute_lang al
        ON a.id_attribute = al.id_attribute AND al.id_lang=1
    GROUP BY pa.id_product_attribute
) attr ON attr.id_product_attribute = pa.id_product_attribute
LEFT JOIN ' . _DB_PREFIX_ . 'stock_available stockattr ON stockattr.id_product_attribute = pa.id_product_attribute
WHERE pl.id_lang = ' . pSQL($langId) . '
AND p.active = 1
AND p.available_for_order = 1
AND p.visibility IN ("none", "both", "catalog", "search")
GROUP BY p.id_product, pa.id_product_attribute
ORDER BY pl.name';

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }
}
