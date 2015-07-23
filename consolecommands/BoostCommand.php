<?php
/**
 * @copyright 2015 iMarc LLC
 * @author Kevin Hamer [kh] <kevin@imarc.net>
 * @author Jeff Turcotte [jt] <jeff@imarc.net>
 * @license Apache (see LICENSE file)
 */

namespace Craft;

/**
 * DeployCommand adds a command to yiic (within Craft) that can be called from
 * the command line. For example,
 *
 *     craft/app/etc/console/yiic boost deploy --env={ENV}
 *
 * This command only does two things at the moment; displays current versions
 * and allows you to deploy to one of the three environments.
 */
class BoostCommand extends BaseCommand
{
    /**
     * Deploys the specified environment
     *
     * @param string $env The environment
     * @return void
     */
    public function actionDeploy($env)
    {
        $deployment = craft()->boost_deployment;

        $deployment->deploy($env);
    }


    /**
     * Shows the git log of what commits will be deployed for the specified environment
     *
     * @param string $env The environment
     * @return void
     */
    public function actionLog($env)
    {
        $deployment = craft()->boost_deployment;

        $deployment->showLog($env);
    }


    /**
     * Shows every environments currently deployed versions
     *
     * @param string $env The environment
     * @return void
     */
    public function actionVersions()
    {
        $deployment = craft()->boost_deployment;

        $deployment->showVersions($env);
    }
}
