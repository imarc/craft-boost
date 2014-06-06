<?php
namespace Craft;

class BoostPlugin extends BasePlugin
{
	function getName()
	{
		return 'Boost';
	}

	function getVersion()
	{
		return '0.1';
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
