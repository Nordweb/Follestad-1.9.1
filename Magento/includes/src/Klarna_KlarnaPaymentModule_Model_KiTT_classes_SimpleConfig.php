<?php
/**
 * Configuration management simple implementation
 *
 * PHP Version 5.3
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */

/**
 * A basic configuration implementation that does not do any persistance
 *
 * @category  Payment
 * @package   KiTT
 * @author    MS Dev <ms.modules@klarna.com>
 * @copyright 2012 Klarna AB (http://klarna.com)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2
 * @link      http://integration.klarna.com/
 */
class KiTT_SimpleConfig implements KiTT_Config
{
    private $_data = array();

    /**
     * Get a configuration value
     *
     * @param string $name the name of the configuration value to get
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!isset($this->_data[$name])) {
            throw new KiTT_MissingConfigurationException($name);
        }
        return $this->_data[$name];
    }

    /**
     * Set a configuration option
     * Internal use only all configuration should happen through the KiTT facade
     *
     * @param string $name  name of option to set
     * @param string $value value to set option to
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Magic getter used by Mustache
     * Wraps the get function.
     *
     * @param string $key the name of the configuration value to get
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic isset used by Mustache
     * Wraps array_key_exists
     *
     * @param string $key config value to check for
     *
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->_data);
    }
}
