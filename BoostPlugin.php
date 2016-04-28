<?php
/**
 * @copyright 2016 Imarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.com>
 * @author Jeff Turcotte [jt] <jeff@imarc.com>
 * @license Apache (see LICENSE file)
 */

namespace Craft;

/**
 * BoostPlugin is a Craft plugin that enables a very simplified deployment
 * system based on our [typical process](http://handbook.imarc.net/deployment).
 *
 * While this software is functional, it should be treated as beta at best. It
 * has strict requirements on how Craft is configured as well as the server
 * environment.
 */
class BoostPlugin extends BasePlugin
{
    public function getName()
    {
        return 'Boost';
    }

    public function getVersion()
    {
        return '0.3.x';
    }

    public function getDeveloper()
    {
        return 'Imarc';
    }

    public function getDeveloperUrl()
    {
        return 'https://www.imarc.com';
    }

    public function defineSettings()
    {
        return [
            'vcsUrl' => [AttributeType::String, 'default' => ''],
            'vcsCache' => [AttributeType::String, 'default' => ''],
            'dbName' => [AttributeType::String, 'default' => ''],
            'envRoot' => [AttributeType::String, 'default' => ''],
            'vcsDirs' => [AttributeType::String, 'default' => ''],
            'canonicalEnv' => [AttributeType::String, 'default' => 'prod'],
        ];
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('boost/settings', ['settings' => $this->getSettings()]);
    }
}
