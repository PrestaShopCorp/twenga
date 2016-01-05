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

class TwengaSmartLeadsFeedModuleFrontController extends \ModuleFrontController
{
    private $dateHeader;

    private $preferedImageType;

    private $defaultLangId;

    private $defaultCountryId;

    private $globalDiscount;

    public function initContent()
    {

        try {
            $this->initExportDependencies();
            @ini_set('memory_limit', '300M');
            if (!ini_get('safe_mode')) {
                @set_time_limit(300);
            }

            $config = \Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_COUNTRY_DEFAULT'));

            $this->defaultLangId = $config['PS_LANG_DEFAULT'];
            $this->defaultCountryId = $config['PS_COUNTRY_DEFAULT'];


            $this->globalDiscount = \SpecificPrice::getSpecificPrice(0, 1, 0, $this->defaultCountryId, 0, 0);
            $products = Twenga_Prestashop_ProductExport::getProducts($this->defaultLangId);

            $this->displayFeedContent($products);
        } catch (\Exception $exception) {
            ob_end_clean();
            header('HTTP/1.1 503 Service Unavailable');
        }
        die;
    }

    private function initExportDependencies()
    {
        $this->dateHeader = gmdate('D, d M Y H:i:s');
        $this->preferedImageType = $this->getPreferredImageType();
    }

    private function displayFeedContent($products)
    {
        ob_start();
        $this->echoHeader();
        foreach ($products as $product) {
            $this->addDiscount($product);
            $this->addMerchantMargin($product);
            $this->echoProduct($product);
            ob_flush();
            flush();
        }
        $this->echoFooter();
        ob_end_flush();
    }

    private function addDiscount(&$product)
    {
        $taxRatio = (1 + $product['tax_rate'] / 100);
        $unitRatio = $product['unit_price'] / $product['price'];
        if ($this->globalDiscount['price'] > 0) {
            $product['price'] = ((float)$this->globalDiscount['price']) * $taxRatio;
        }

        if ($this->globalDiscount['reduction'] > 0) {
            $product['regular_price'] = $product['price'];
            if ($this->globalDiscount['reduction_type'] == 'amount') {
                $product['price'] -= ($this->globalDiscount['reduction_tax'])
                    ? $this->globalDiscount['reduction']
                    : $this->globalDiscount['reduction'] * $taxRatio;
            } elseif ($this->globalDiscount['reduction_type'] == 'percentage') {
                $product['price'] -= $product['price'] * $this->globalDiscount['reduction'];
            }
            $product['price'] = round($product['price'], 2);
            $product['regular_price'] = round($product['regular_price'], 2);
        }

        $product['unit_price'] = round($product['price'] * $unitRatio, 2);

        return $this;
    }

    private function addMerchantMargin(&$product)
    {
        if ($product['wholesale_price'] > 0) {
            $product['merchant_margin'] = round($product['price'] - $product['wholesale_price'], 2);
        }
        return $this;
    }

    private function echoProduct($product)
    {
        echo '<product>';
        echo $this->getPropertyXml('merchant_ref', $product['merchant_ref']);
        echo $this->getPropertyXml('merchant_id', $product['merchant_id']);
        echo $this->getPropertyXml('upc_ean', $product['upc_ean']);
        echo $this->getPropertyXml('product_url', $this->getProductLink($product));
        echo $this->getPropertyXml('image_url', $this->getImageLink($product));
        echo $this->getPropertyXml('designation', $product['designation']);
        echo $this->getPropertyXml('description', $product['description']);
        echo $this->getPropertyXml('brand', $product['brand']);
        echo $this->getPropertyXml('category', $product['category']);
        echo $this->getPropertyXml('manufacturer_id', $product['manufacturer_id']);
        echo $this->getPropertyXml('price', $product['price']);
        echo isset($product['regular_price']) ? $this->getPropertyXml('regular_price', $product['regular_price']) : '';
        echo $this->getPropertyXml(
            'merchant_margin',
            isset($product['merchant_margin']) ? $product['merchant_margin'] : ''
        );
        echo $this->getPropertyXml('unit_price', $product['unit_price']);
        echo $this->getPropertyXml('shipping_cost', $product['shipping_cost']);
        echo $this->getPropertyXml('ecotax', $product['ecotax']);
        echo $this->getPropertyXml('availability', $product['availability']);
        echo $this->getPropertyXml('in_stock', $product['in_stock']);
        echo $this->getPropertyXml('condition', $product['condition']);
        echo $this->getPropertyXml('energy_rating', '');
        echo $this->getPropertyXml('item_display', $product['item_display']);
        echo '</product>';
        return $this;
    }

    /**
     * Get product url using Prestashop link builder
     * @param array $product
     * @return mixed
     */
    private function getProductLink($product)
    {
        return static::$link->getProductLink(
            $product['id_product'],
            null,
            null,
            null,
            $this->defaultLangId,
            1
        ) . $product['product_url_anchor'];
    }

    /**
     * Get image url using Prestashop link builder
     * @param array $product
     * @return mixed
     */
    private function getImageLink($product)
    {
        return static::$link->getImageLink(
            $product['link_rewrite'],
            $product['id_product'] . '-' . $product['id_image'],
            $this->preferedImageType
        );
    }

    private function getPropertyXml($name, $value)
    {
        if (!empty($value) && !is_numeric($value)) {
            $value = '<![CDATA[' . htmlentities($value, ENT_QUOTES, 'UTF-8') . ']]>';
        }


        return '<' . $name . '>' . $value . '</' . $name . '>';
    }

    private function echoHeader()
    {
        //header( 'Last-Modified: ' . $this->dateHeader . ' GMT' );
        header("Content-type: text/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<products>';
    }

    private function echoFooter()
    {
        echo '</products>';
    }

    private function getPreferredImageType()
    {
        $imageTypes = \ImageType::getImagesTypes('products', true);
        return isset($imageTypes[0]) ? $imageTypes[0]['name'] : '';
    }
}
