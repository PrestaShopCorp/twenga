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

/**
 * Twenga Lang class
 */
class Twenga_Services_Lang
{
    /**
     * Default language
     * @var int
     */
    const DEFAULT_LANGUAGE_ID = 2;

    /**
     * Language id
     * @var int $languageId
     */
    protected static $languageId;

    /**
     * Directory where translation files are stored
     * @var string
     */
    protected static $translationDir;

    /**
     * If is debug mode
     * @var bool
     */
    protected static $isDebug = false;

    /**
     * Contains language file content if loaded
     * @var array
     */
    protected static $languageFile = array();

    /**
     * Contains config file content if loaded
     * @var array
     */
    protected static $configFile = null;

    /**
     * Set the current language
     * @param integer $languageId
     */
    public static function setLanguageId($languageId)
    {
        self::$languageId = $languageId;
        self::loadLanguageFile();
        self::loadLanguageConfig();
    }

    /**
     * Get the current language Id
     */
    public static function getLanguageId()
    {
        return self::$languageId;
    }

    /**
     * Set translation directory
     * @param string $translationDir
     */
    public static function setTranslationDir($translationDir)
    {
        self::$translationDir = $translationDir;
    }

    /**
     * Get translation directory
     * @return string
     */
    public static function getTranslationDir()
    {
        return self::$translationDir;
    }

    /**
     * Set if debug mode is enabled
     * @param bool $isDebug
     */
    public static function setDebugMode($isDebug)
    {
        self::$isDebug = $isDebug;
    }

    /**
     * Could be called in bootstrap to init a default language
     */
    public static function init()
    {
        self::setLanguageId(self::DEFAULT_LANGUAGE_ID);
    }

    /**
     * Includes the translation file and return its value
     * @return string
     */
    protected static function loadLanguageFile()
    {
        if (self::$languageId) {
            // load
            if (!isset(self::$languageFile[self::$languageId])) {
                self::$languageFile[self::$languageId] = require(
                    self::$translationDir . '/' . self::$languageId . '.php'
                );
            }
            self::$languageFile[self::$languageId];
        }
    }

    /**
     * Store the language configuration file in memory
     */
    protected static function loadLanguageConfig()
    {
        if (is_null(self::$configFile)) {
            try {
                self::$configFile = require(_TWENGA_SL_MODULE_DIR_ . 'classes/services/lang/config.php');
            } catch (\Exception $e) {
                throw new \Exception('Unable to load config language file');
            }
        }
    }

    /**
     * Return config for current language
     * @param $key
     * @return mixed
     */
    public static function getConfig($key = false)
    {
        if (is_array(self::$configFile) && isset(self::$configFile[self::$languageId])) {
            if ($key === false) {
                return self::$configFile[self::$languageId];
            } elseif (isset(self::$configFile[self::$languageId][$key])) {
                return self::$configFile[self::$languageId][$key];
            }
        }
        return false;
    }

    /**
     * Return the translated string
     * @param integer|array $params
     * @param string $content
     * @return string
     */
    public static function trans($params, $content)
    {
        $return = '';
        $isTranslated = false;

        // Called by smarty templates (via a register_block)
        if ($content) {
            // you can call this method with tr id directly
            if (is_int($params) && $params > 0) {
                $params = array(
                    '_id' => $params
                );
            }

            // Set default return
            $return = $content;

            // The tr_id exist
            if (isset($params['_id'])) {

                // Checking if this is a plural expression.
                if (isset($params['i'])) {
                    if (function_exists('__RULE_' . self::$languageId)) {
                        $ruleResult = call_user_func('__RULE_' . self::$languageId, $params['i']);
                    } else {
                        $ruleResult = $params['i'] > 1 ? 1 : 0;
                    }
                }

                // get translation
                $index = isset($ruleResult) ? ($ruleResult) : 0;
                if (
                    isset(self::$languageFile[self::$languageId]) &&
                    is_array(self::$languageFile[self::$languageId])
                ) {
                    if (
                        isset(self::$languageFile[self::$languageId][$params['_id']]) &&
                        isset(self::$languageFile[self::$languageId][$params['_id']][$index])
                    ) {
                        $return = self::$languageFile[self::$languageId][$params['_id']][$index];
                        if (!empty($return)) {
                            $isTranslated = true;
                        }
                    }
                }
            }

            // Replacing params into content
            if (is_array($params)) {
                if (isset($params['aReplaceParam'])) {
                    foreach ($params['aReplaceParam'] as $search => $value) {
                        $return = str_replace('%' . $search . '%', $value, $return);
                    }
                } else {
                    foreach ($params as $k => $v) {
                        if ($k == '_id') {
                            continue;
                        }
                        $return = str_replace('%' . $k . '%', $v, $return);
                    }
                }
            }

            // Replacing translation by constant language
            $return = str_replace('%TWENGA_LONG_URL%', self::getConfig('TR_FIRM_LONG_URL'), $return);
            $return = str_replace('%TWENGA_URL%', self::getConfig('TR_FIRM_URL'), $return);
            $return = str_replace('%TWENGA_SHORT_URL%', self::getConfig('TR_FIRM_SHORT_URL'), $return);
            $return = str_replace('%TWENGA_EMAIL%', self::getConfig('TR_FIRM_EMAIL'), $return);
            $return = str_replace('%TWENGA%', self::getConfig('TR_FIRM_NAME'), $return);

            //Returning content. If it has been translated return translation, otherwise if its dev put {tr} tags
            return ($isTranslated)
                ? $return
                : (self::$isDebug
                    ? (isset($params['_id']) ? '{tr}' . $return . '{/tr}' : $content)
                    : $return);
        }

        return $return;
    }
}
