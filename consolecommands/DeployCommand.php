<?php
namespace Craft;

/**
 * DeployCommand
 *
 * @copyright 2014 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
 */
class DeployCommand extends BaseCommand
{
	public function actionIndex($env=null)
	{
		$deployment = craft()->boost_deployment;

		if ($env === null) {
			$deployment->showVersions();
		} else {
			$deployment->deploy($env);
		}
	}
}
