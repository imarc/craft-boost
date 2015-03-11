<?php
/**
 * @copyright 2015 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
 * @license Apache (see LICENSE file)
 */

namespace Craft;

/**
 * DeployCommand adds a command to yiic (within Craft) that can be called from
 * the command line. For example,
 *
 *     craft/app/etc/console/yiic deploy
 *
 * This command only does two things at the moment; displays current versions
 * and allows you to deploy to one of the three environments.
 */
class DeployCommand extends BaseCommand
{

    /**
    * actionIndex is the default method called by Craft for a BaseCommand.
    *
    * @param string $env  If provided, the environment to deploy to.
    * @return void
    */
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
