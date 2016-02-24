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

class Twenga_Prestashop_AdminController extends \ModuleAdminController
{
    /**
     * Catch module exception to display an error json
     * @return bool
     */
    public function postProcess()
    {
        try {
            return parent::postProcess();
        } catch (Twenga_Exception $exception) {
            echo $this->returnException($exception);
            die;
        }
    }

    /**
     * Method to return json to send in case of exception
     * @param Twenga_Exception $exception
     * @return string
     */
    protected function returnException(Twenga_Exception $exception)
    {
        $output = array(
            'return_code' => 1,
            'errors' => array(
                'An error occured during the request'
            )
        );
        if (Twenga_Config::get('tws.debug', false)) {
            $output['exception'] = array(
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            );
        }
        return \Tools::jsonEncode($output);
    }

    /**
     * Get Webservice object
     * @return Twenga_Services_Webservice
     */
    protected function getWebservice()
    {
        if (!isset($this->webservice)) {
            $this->webservice = new Twenga_Services_Webservice(
                Twenga_Config::get('tws.webservice.domain'),
                Twenga_Config::get('tws.product_id'),
                \Configuration::get('TWENGA_GEOZONE')
            );
        }
        if (isset($this->context->cookie->tw_token)) {
            $this->webservice->setToken($this->context->cookie->tw_token);
        }
        return $this->webservice;
    }

    /**
     * Get smarty instance
     * @return \Smarty
     * @throws \SmartyException
     */
    protected function getSmarty()
    {
        $smarty = new \Smarty();
        $smarty->setTemplateDir(_TWENGA_SL_MODULE_DIR_ . '/views/templates');
        $smarty->assign('_basepath', rtrim($this->module->getPathUri(), '/'));
        $smarty->assign('urlLogoHeader', Twenga_Config::get('tws.url_logo_header'));

        $smarty->registerPlugin('block', 'tr', array('Twenga_Services_Lang', 'trans'));
        $smarty->registerPlugin('block', 'addUtm', array($this, 'addUtm'));

        return $smarty;
    }

    /**
     * @param array $params
     * @param \Smarty $smarty
     * @return string
     */
    public function translate($params, &$smarty)
    {
        return $this->module->l($params['s']);
    }

    /**
     * Add analytics parameters
     * @param array $params
     * @param string $content
     * @return string
     */
    public function addUtm($params, $content)
    {
        if ($content) {
            $trackingParameters = array(
                'utm_source' => 'prestashop',
                'utm_medium' => 'partner',
                'utm_campaign' => 'module_prestashop_smartleads'
            );
            $parsedUrl = \parse_url($content);
            if (!isset($parsedUrl['query']) || empty($parsedUrl['query'])) {
                $parsedUrl['query'] = \http_build_query($trackingParameters);
            } else {
                $parsedUrl['query'] .= '&' . \http_build_query($trackingParameters);
            }

            return $this->buildUrl($parsedUrl);
        }
        return $content;
    }

    /**
     * Build a parsed url
     * @param array $parsedUrl
     * @return string
     */
    private function buildUrl(array $parsedUrl)
    {
        $url = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : 'http://';
        $url .= isset($parsedUrl['user'])
            ? (isset($parsedUrl['pass'])
                ? $parsedUrl['user'] . ':' . $parsedUrl['pass'] . '@'
                : $parsedUrl['user'] . '@'
            )
            : '';
        $url .= isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $url .= isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $url .= isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $url .= isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $url .= isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        return $url;
    }

    /**
     * Prefix class name using product info
     * @param string $className
     * @return string
     */
    protected function prefixClass($className)
    {
        return Twenga_Config::get('tws.prestashop.class_prefix') . $className;
    }

    /**
     * Get shop feed url
     * @return string
     */
    protected function getFeedUrl()
    {
        return $this->context->link->getModuleLink($this->module->name, 'feed');
    }
}
