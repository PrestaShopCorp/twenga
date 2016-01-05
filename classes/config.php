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

class Twenga_Config
{
    /**
     * @var array
     */
    private static $data = array();

    /**
     * @var string
     */
    private static $path;

    /**
     * Add data to config
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public static function set($key, $value)
    {
        $keyComponents = explode('.', $key);
        static::load($keyComponents[0]);
        $lastKey = array_pop($keyComponents);
        $node = &static::getNodeFromPath(static::$data, $keyComponents, true);
        $node[$lastKey] = $value;
    }

    /**
     * Get data from config
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function get($key, $defaultValue = null)
    {
        $keyComponents = explode('.', $key);
        static::load($keyComponents[0]);
        $lastKey = array_pop($keyComponents);
        if (array() !== ($node = &static::getNodeFromPath(static::$data, $keyComponents)) && isset($node[$lastKey])) {
            return $node[$lastKey];
        }
        return $defaultValue;
    }

    /**
     * Load configuration data from file
     * @param string $mainKey
     * @throws \Exception
     */
    private static function load($mainKey)
    {
        if (!isset(static::$path) || empty(static::$path)) {
            throw new \Exception('You need to set configuration directory path using Config::setConfigPath().');
        }

        if (!isset(static::$data[$mainKey])) {
            $filePath = static::$path . DIRECTORY_SEPARATOR . $mainKey . '.php';
            if (!file_exists($filePath)) {
                throw new \Exception(
                    printf(
                        'No file %s found in configuration folder %s',
                        $mainKey . '.php',
                        static::$path
                    )
                );
            }
            static::$data[$mainKey] = require_once($filePath);
        }
    }

    /**
     * Get node of container using path
     * @param array $rootContainer
     * @param array $keyComponents
     * @param bool $createNodesIfNotExists
     * @return array|null
     */
    protected static function &getNodeFromPath(
        array &$rootContainer,
        array $keyComponents,
        $createNodesIfNotExists = false
    ) {
        if (empty($keyComponents)) {
            return $rootContainer;
        }

        $aData = &$rootContainer;
        foreach ($keyComponents as $sKey) {
            if (!isset($aData[$sKey])) {
                if ($createNodesIfNotExists) {
                    $aData[$sKey] = array();
                } else {
                    $t = array();
                    return $t;
                }
            }
            $aData = &$aData[$sKey];
        }
        return $aData;
    }

    /**
     * Set configuration directory path
     * @param string $path
     */
    public static function setConfigPath($path)
    {
        static::$path = rtrim($path, DIRECTORY_SEPARATOR);
    }
}
