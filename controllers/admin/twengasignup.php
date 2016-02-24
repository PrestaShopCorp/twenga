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

include _PS_MODULE_DIR_ . 'twenga/classes/prestashop/admincontroller.php';

class TwengaSignUpController extends Twenga_Prestashop_AdminController
{
    public $id = 0;

    private $merchantInfo;

    private function displayTagManager()
    {
        return '<script type="text/javascript" src="' . Twenga_Config::get('tws.tag_manager_url') . '"></script>';
    }

    /**
     * Return html code to display configure page
     *
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    public function getConfigurePage()
    {
        $smarty = $this->getSmarty();

        try {
            $smarty->assign('formSignUpUrl', $this->getAjaxUrl('SignUp'));
            $smarty->assign('formLoginUrl', $this->getAjaxUrl('Login'));
            $smarty->assign('lostPasswordUrl', $this->getAjaxUrl('LostPassword'));
            $smarty->assign('twengaFeedUrl', $this->getFeedUrl());
            $smarty->assign('productStatus', 'ongoing');

            $smarty->assign('twsDomain', Twenga_Config::get('tws.domain'));

            $currentStep = \Configuration::get('TW_SMARTLEADS_CONFIGURE_STEP');
            $currentStep = $currentStep === false ? 1 : (int)$currentStep;
            $smarty->assign('stepClass', '');

            // Init account type (new or exist)
            if (false === ($currentAccountType = \Configuration::get('TW_SMARTLEADS_CONFIGURE_ACCOUNT_TYPE'))) {
                $currentAccountType = 'new';
            }

            // If already connected
            if (isset($this->context->cookie->tw_token) && false !== $this->context->cookie->tw_token) {

                try {
                    $this->loadMerchantInfo();
                    $smarty->assign('merchantInfo', $this->merchantInfo);
                    $currentStep = 3;

                    if (401 == $this->getWebservice()->getLastHttpCode()) {
                        $currentStep = 2;
                        $currentAccountType = 'exists';
                    } else {
                        $smarty->assign('productStatus', $this->getProductStatus());
                    }

                } catch (Twenga_Exception $exception) {
                }
            } elseif ($currentStep == 3) {
                // If not connected but already registered, redirect to step 2 for login
                $currentStep = 2;
                $currentAccountType = 'exist';
            }

            // If configuration is DONE, autocomplete forms


            $smarty->assign('currentStepDone', (int)$currentStep);
            $smarty->assign('currentAccountType', $currentAccountType);

            $this->loadForms($smarty);

            $bodyContent = $smarty->fetch(_TWENGA_SL_MODULE_DIR_ . '/views/templates/admin/configure.tpl');
            $smarty->assign('bodyContent', $bodyContent);


            return $smarty->fetch(_TWENGA_SL_MODULE_DIR_ . '/views/templates/admin/layout/layout.tpl');
        } catch (Twenga_Exception $exception) {
            if (true === Twenga_Config::get('tws.debug')) {
                $smarty->assign('debugError', $exception->__toString());
            }

            $bodyContent = $smarty->fetch(_TWENGA_SL_MODULE_DIR_ . '/views/templates/admin/error.tpl');
            $smarty->assign('bodyContent', $bodyContent);
            return $smarty->fetch(_TWENGA_SL_MODULE_DIR_ . '/views/templates/admin/layout/layout.tpl');
        }
    }

    /**
     * Method for ajax call with action SignUp
     */
    public function ajaxProcessSignUp()
    {
        $webservice = $this->getWebservice();
        $response = $webservice->postSignUp($_POST);

        if ($webservice->isLastHttpCode2xx()) {
            $this->storeMerchantConfig($_POST);
            \Configuration::updateValue('TW_SMARTLEADS_CONFIGURE_ACCOUNT_TYPE', 'new');

            if (isset($response['user']['AUTO_LOG_URL'])) {
                $response['user']['AUTO_LOG_URL'] = $this->addUtm(array(), $response['user']['AUTO_LOG_URL']);
            }

            try {
                $authResponse = $webservice->authenticate(
                    $response['merchant']['EXTRANET_SITE_ID'],
                    $response['merchant']['API_KEY']
                );
                if ($webservice->isLastHttpCode2xx()) {
                    $this->context->cookie->tw_token = $authResponse['auth']['token'];
                    $response = array_merge($response, $authResponse);
                    $this->loadTrackingScript();
                }
            } catch (Twenga_Exception $exception) {
            }
        }

        echo \Tools::jsonEncode($response);
        die;
    }

    /**
     * Method for ajax call with action Login
     */
    public function ajaxProcessLogin()
    {
        $webservice = $this->getWebservice();
        $response = $webservice->authenticateEmail($_POST);

        if (!$webservice->isLastHttpCode2xx()) {
            echo \Tools::jsonEncode($response);
            die;
        }

        $this->loadTrackingScript();
        $this->loadMerchantInfo();
        $response['product'] = array('status' => $this->getProductStatus());

        if (false == \Configuration::get('TW_SMARTLEADS_CONFIGURE_ACCOUNT_TYPE')) {
            \Configuration::updateValue('TW_SMARTLEADS_CONFIGURE_ACCOUNT_TYPE', 'exist');
        }

        $this->context->cookie->tw_token = $response['auth']['token'];
        \Configuration::updateValue('TW_SMARTLEADS_CONFIGURE_STEP', 3);

        unset($response['auth']);

        echo \Tools::jsonEncode(array_merge($response, $this->merchantInfo));
        die;
    }

    /**
     * Method for ajax call with action Login
     */
    public function ajaxProcessLostPassword()
    {
        $webservice = $this->getWebservice();
        $response = $webservice->postLostPassword(\Tools::getValue('EMAIL'));
        echo \Tools::jsonEncode($response);
        die;
    }

    /**
     * Store merchant config
     * @param array $data
     * @return $this
     */
    private function storeMerchantConfig(array $data)
    {
        \Configuration::updateValue('TW_MERCHANT_EMAIL', $data['EMAIL']);
        \Configuration::updateValue('TW_SMARTLEADS_CONFIGURE_STEP', 3);
        return $this;
    }

    /**
     *
     * @return $this
     * @throws Twenga_Exception
     */
    private function loadTrackingScript()
    {
        if (false === \Configuration::get('TW_TRACKING_SCRIPT')) {
            $webservice = $this->getWebservice();
            $response = $webservice->getTrackingScript();

            if ($webservice->isLastHttpCode2xx()) {
                \Configuration::updateValue('TW_TRACKING_SCRIPT', $response['tracker_script']['html'], true);
            } else {
                throw new Twenga_Exception('An error occured while retrieving tracking script');
            }
        }
        return $this;
    }

    private function loadMerchantInfo()
    {
        $webservice = $this->getWebservice();
        $response = $webservice->getAccountInfo();

        if ($webservice->isLastHttpCode2xx()) {
            if (isset($response['user']['AUTO_LOG_URL'])) {
                $response['user']['AUTO_LOG_URL'] = $this->addUtm(array(), $response['user']['AUTO_LOG_URL']);
            }
            $this->merchantInfo = $response;
            \Configuration::updateValue('TW_MERCHANT_EMAIL', $response['user']['EMAIL']);
        } else {
            throw new Twenga_Exception('An error occurred while retrieving merchant info');
        }
        return $this;
    }

    public function displayBadGeozone()
    {
        return 'Your country is not supported by Twenga-Solutions SmartLeads';
    }

    private function loadForms(\Smarty $smarty)
    {
        $webservice = $this->getWebservice();

        $response = $webservice->getFormSignUp();

        if (!$webservice->isLastHttpCode2xx()) {
            throw new Twenga_Exception(
                'An error occurred when retrieving signUp form. HTTP code : ' . $webservice->getLastHttpCode()
            );
        }
        $smarty->assign('formSignUp', $response['html']);

        $response = $webservice->getFormLogin();
        if (!$webservice->isLastHttpCode2xx()) {
            throw new Twenga_Exception('An error occurred when retrieving login form');
        }
        $smarty->assign('formLogin', $response['html']);

        return $this;
    }

    /**
     * Get url for ajax call
     * @param string $action
     * @return string
     */
    private function getAjaxUrl($action)
    {
        return $this->context->link->getAdminLink($this->prefixClass('SignUp')) . '&' . http_build_query(
            array(
                'configure' => $this->module->name,
                'action' => $action,
                'ajax' => 'true'
            )
        );
    }

    /**
     * Get product status
     * @return string
     */
    private function getProductStatus()
    {
        $productInfo = $this->getWebservice()->getProduct();
        return
            (isset($productInfo)
                && isset($productInfo['EXTRANET_STATUS'])
                && $productInfo['EXTRANET_STATUS'] === 'COMPLETED')
            ? 'completed'
            : 'ongoing';
    }
}
