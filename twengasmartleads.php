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

if (!defined('_PS_VERSION_')) {
    exit;
}

class TwengaSmartLeads extends Module
{
    /**
     * @var array
     */
    protected $geozoneInfo = array();

    public $limited_countries = array('fr', 'de', 'gb', 'uk', 'it', 'es', 'nl');

    /**
     * Module constructor
     */
    public function __construct()
    {
        $this->name = 'twengasmartleads';
        $this->tab = 'market_place';
        $this->version = '3.0.0';
        $this->author = 'Twenga';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        //$this->warning = 'Configuration needed';

        parent::__construct();

        if (!defined('_TWENGA_SL_MODULE_DIR_')) {
            define('_TWENGA_SL_MODULE_DIR_', _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR);
        }

        spl_autoload_register(array($this, 'autoload'));

        Twenga_Config::setConfigPath(_TWENGA_SL_MODULE_DIR_ . '/config');

        $this->loadGeozone();

        Twenga_Services_Lang::setTranslationDir(_TWENGA_SL_MODULE_DIR_ . '/config/translations');
        Twenga_Services_Lang::init();
        Twenga_Services_Lang::setDebugMode(Twenga_Config::get('tws.debug'));
        Twenga_Services_Lang::setLanguageId($this->geozoneInfo['language_id']);

        $this->displayName = Twenga_Services_Lang::trans(
            array('_id' => '86117'),
            'Acquisition et ciblage d\'audience avec le Module Twenga'
        );
        $this->description = Twenga_Services_Lang::trans(
            array('_id' => '86127'),
            'Vous disposez d\'un petit budget, Twenga vous propose une offre de référencement adaptée qui boostera immédiatement votre chiffre d\'affaire.'
        );

        $this->confirmUninstall = Twenga_Services_Lang::trans(
            array('_id' => '0'),
            'Are you sure you want to uninstall?'
        );
    }

    /**
     * Install module
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
        && $this->registerHook('header')
        && $this->installModuleTab()
        && $this->initConfig();
    }

    /**
     * Uninstall module
     * @return bool
     */
    public function uninstall()
    {
        return
            parent::uninstall()
            && $this->unregisterHook('header')
            && $this->uninstallModuleTab()
            && $this->resetConfig();
    }

    /**
     * Header hook
     * Allow us to add tracking script
     * @return string
     */
    public function hookHeader()
    {
        $scripts = '';
        // Only display tracking script if no other twenga module did it
        if (!isset($this->context->commonHeaderInstalled) || !$this->context->commonHeaderInstalled) {
            $scripts .= $this->displayTrackingScript();
            $this->context->commonHeaderInstalled = true;
        }

        return $scripts;
    }

    private function displayTrackingScript()
    {
        if (false != ($script = \Configuration::get('TW_TRACKING_SCRIPT'))) {
            return $script;
        }
        return '';
    }

    /**
     * Get configuration page content
     * @return mixed
     */
    public function getContent()
    {
        $this->context->controller->addJquery('1.11.3');

        $this->context->controller->addCSS($this->getPathUri() . 'views/css/main.css');
        $this->context->controller->addCSS(
            '//fonts.googleapis.com/css?' .
            'family=Roboto:100italic,100,300italic,300,400italic,400,500italic,500,700italic,700,900italic,900'
        );
        $this->context->controller->addJS($this->getPathUri() . 'views/js/configure.js');
        $this->context->controller->addJS(Twenga_Config::get('tws.tag_manager_url'));

        require_once _TWENGA_SL_MODULE_DIR_ . 'controllers/admin/twengasmartleadssignup.php';
        $oConfigureController = new \TwengaSmartLeadsSignUpController();
        return $oConfigureController->getConfigurePage();
    }

    /**
     * Initialize module configuration
     * @return bool
     */
    private function initConfig()
    {
        $config = array();
        if (false !== ($configJson = \Configuration::get('TWENGA_CONFIG'))) {
            $config = \Tools::jsonDecode($configJson, true);
        }

        $productId = Twenga_Config::get('tws.product_id');
        $config[$productId] = true;

        return \Configuration::updateValue('TWENGA_CONFIG', \Tools::jsonEncode($config), true)
        && \Configuration::updateValue('TWENGA_GEOZONE', $this->geozoneInfo['tw_code'])
        && \Configuration::updateValue('TW_SMARTLEADS_CONFIGURE_STEP', 1);
    }

    /**
     * Remove configuration
     * @return bool
     */
    private function resetConfig()
    {
        $config = array();
        if (false !== ($configJson = \Configuration::get('TWENGA_CONFIG'))) {
            $config = \Tools::jsonDecode($configJson, true);
            $productId = Twenga_Config::get('tws.product_id');
            unset($config[$productId]);
        }

        $commonConfReturn =
            \Configuration::deleteByName('TW_SMARTLEADS_CONFIGURE_ACCOUNT_TYPE') &&
            \Configuration::deleteByName('TW_SMARTLEADS_CONFIGURE_STEP');

        if (empty($config)) {
            $this->context->cookie->tw_token = false;
            // No more twenga solutions modules installed => remove all configurations
            return \Configuration::deleteByName('TWENGA_CONFIG')
            && \Configuration::deleteByName('TWENGA_GEOZONE')
            && \Configuration::deleteByName('TW_TRACKING_SCRIPT')
            && \Configuration::deleteByName('TW_MERCHANT_EMAIL')
            && $commonConfReturn;
        } else {
            return
                \Configuration::updateValue('TWENGA_CONFIG', \Tools::jsonEncode($config), true) &&
                $commonConfReturn;
        }
    }

    /**
     * Install module tab to allow the use of admin controllers
     * @return bool
     */
    private function installModuleTab()
    {
        $tab = new Tab();
        $tab->name = array(1 => 'Acquisition et ciblage d\'audience avec le Module Twenga');
        $tab->class_name = 'TwengaSmartLeadsSignUp';
        $tab->module = $this->name;
        $tab->id_parent = -1;
        $tab->active = 0;
        return $tab->save();
    }

    /**
     * Uninstall module tab
     * @return bool
     */
    private function uninstallModuleTab()
    {
        if (false !== ($tab = new Tab((int)Tab::getIdFromClassName('TwengaSmartLeadsSignUp')))) {
            return $tab->delete();
        }
        return true;
    }

    /**
     * Autoload for module classes
     * @param string $className
     */
    public function autoload($className)
    {
        if (0 === strpos($className, 'Twenga_')) {
            $filePath = _TWENGA_SL_MODULE_DIR_ . 'classes' . DIRECTORY_SEPARATOR .
                \Tools::strtolower(str_replace('_', '/', \Tools::substr($className, 7))) . '.php';
            require_once($filePath);
        }
    }

    /**
     * Load twenga geozones based on iso_code
     * @return $this
     */
    private function loadGeozone()
    {
        $geozoneConfigs = Twenga_Config::get('geozone');
        if (isset($geozoneConfigs[$this->context->country->iso_code])) {
            $this->geozoneInfo = $geozoneConfigs[$this->context->country->iso_code];
        }
        return $this;
    }
}
