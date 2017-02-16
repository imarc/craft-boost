<?php
/**
 * @copyright 2017 Imarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.com>
 * @author Jeff Turcotte [jt] <jeff@imarc.com>
 * @author Dan Collins [dc] <dan@imarc.com>
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
        return '2.3.1';
    }

    public function getSchemaVersion()
    {
        return '1';
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
        return array(
            'canonicalEnv' => array(AttributeType::String, 'default' => 'prod'),
            'devDbName' => array(AttributeType::String, 'default' => ''),
            'stageDbName' => array(AttributeType::String, 'default' => ''),
            'dbName' => array(AttributeType::String, 'default' => ''),
            'devDbUser' => array(AttributeType::String, 'default' => ''),
            'stageDbUser' => array(AttributeType::String, 'default' => ''),
            'dbUser' => array(AttributeType::String, 'default' => ''),
            'devDbPass' => array(AttributeType::String, 'default' => ''),
            'stageDbPass' => array(AttributeType::String, 'default' => ''),
            'dbPass' => array(AttributeType::String, 'default' => ''),
            'devDbHost' => array(AttributeType::String, 'default' => ''),
            'stageDbHost' => array(AttributeType::String, 'default' => ''),
            'dbHost' => array(AttributeType::String, 'default' => 'localhost'),
            'envRoot' => array(AttributeType::String, 'default' => ''),
            'vcsCache' => array(AttributeType::String, 'default' => ''),
            'vcsDirs' => array(AttributeType::String, 'default' => ''),
            'vcsUrl' => array(AttributeType::String, 'default' => ''),
            'preDeploymentHooks' => array(AttributeType::String, 'default' => ''),
            'postDeploymentHooks' => array(AttributeType::String, 'default' => ''),
            'deleteLeftoverFiles' => array(AttributeType::Bool, 'default' => false),
            'protectFromDeletion' => array(AttributeType::String, 'default' => ''),

            // For Imarc, should be www-data:web
            'resetOwnership' => array(AttributeType::String, 'default' => 'www-data:web'),

            // For Imarc, should be g+rw
            'resetPermissions' => array(AttributeType::String, 'default' => 'g+rw'),

            // For Imarc, should be false
            'keepDatabase' => array(AttributeType::Bool, 'default' => false),

            // For Imarc, should be web@localhost
            'resetDbPermissions' => array(AttributeType::String, 'default' => 'web@localhost'),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('boost/settings', array('settings' => $this->getSettings()));
    }
}
