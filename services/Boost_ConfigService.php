<?php
/**
 * @copyright 2018 Imarc LLC
 * @author Jeff Turcotte [jt] <jeff@imarc.com>
 * @license Apache (see LICENSE file)
 */

namespace Craft;

/**
 * Boost_ConfigServer is a Craft plugin config convenience wrapper that
 * can merge db-based 'settings' with craft/config-based 'config' files
 *
 * Anything found in a config file will overload the settings
 */
class Boost_ConfigService extends BaseApplicationComponent
{
    public function __get($key)
    {
        if (craft()->config->get($key, 'boost') !== null) {
            return craft()->config->get($key, 'boost');
        }

        return craft()->plugins->getPlugin('boost')->getSettings()->__get($key);
    }
}
