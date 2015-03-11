<?php
/**
 * @copyright 2015 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
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
    function getName()
    {
        return 'Boost';
    }

    function getVersion()
    {
        return '0.2';
    }

    function getDeveloper()
    {
        return 'iMarc';
    }

    function getDeveloperUrl()
    {
        return 'http://www.imarc.net';
    }

    function defineSettings()
    {
        return [
            'vcsUrl'       => [AttributeType::String, 'default' => ''],
            'vcsCache'     => [AttributeType::String, 'default' => ''],
            'dbName'       => [AttributeType::String, 'default' => ''],
            'envRoot'      => [AttributeType::String, 'default' => ''],
            'vcsDirs'      => [AttributeType::String, 'default' => ''],
            'canonicalEnv' => [AttributeType::String, 'default' => 'prod'],
        ];
    }

    function getSettingsHtml()
    {
        return craft()->templates->render('boost/settings', ['settings' => $this->getSettings()]);
    }
}
