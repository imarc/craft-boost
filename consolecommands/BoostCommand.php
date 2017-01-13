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
     * By default, show the help.
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->actionHelp();
    }

    /**
     * Prints some help text.
     *
     * @return void
     */
    public function actionHelp()
    {
        $b = "\033[1m";
        $d = "\033[0m";
        $version = craft()->plugins->getPlugin('boost')->getVersion();
        echo "Boost - Deployment Management - $version\n\n";
        echo "Usage: ${b}yiic boost${d} [command] [arguments]\n\n";

        echo "Deploying:\n";
        echo "    ${b}yiic boost deploy${d} --env=dev\n";
        echo "        Deploys ${b}master${d} to the DEV environment.\n\n";
        echo "    ${b}yiic boost deploy${d} --env=stage\n";
        echo "        Deploys ${b}master${d} to the STAGE environment.\n\n";
        echo "    ${b}yiic boost deploy${d} --env=prod\n";
        echo "        Deploys the version running on the STAGE environment to the PROD environment.\n\n";
        echo "    ${b}yiic boost deploy${d} --env=stage --copyDatabase=0\n";
        echo "        Deploys, but disables copying the db from the canonical environment.\n\n";
        echo "    ${b}yiic boost deploy${d} --env=dev --branch=bug-fix\n";
        echo "        Deploys the bug-fix branch to the DEV environment.\n\n";

        echo "Log:\n";
        echo "    ${b}yiic boost log${d} --env=[ENVIRONMENT]\n";
        echo "        Shows the git log of what commits will be deployed to the environment.\n\n";

        echo "Versions:\n";
        echo "    ${b}yiic boost versions${d}\n";
        echo "        Shows the current versions of each of the environments.\n\n";

        echo "Version:\n";
        echo "    ${b}yiic boost version${d}\n";
        echo "        Shows the current version of Boost.\n\n";
    }

    /**
     * Prints the boost version
     *
     * @return void
     */
    public function actionVersion()
    {
        $version = craft()->plugins->getPlugin('boost')->getVersion();

        echo "Boost Version: $version\n";
    }


    /**
     * Deploys the specified environment
     *
     * @param string $env The environment
     * @return void
     */
    public function actionDeploy($env, $copyDatabase = 1, $branch = 'master')
    {
        $deployment = craft()->boost_deployment;

        $copyDatabase = (bool) $copyDatabase;

        $deployment->deploy($env, $copyDatabase, $branch);
    }


    /**
     * Shows the git log of what commits will be deployed for the specified environment
     *
     * @param string $env The environment
     * @return void
     */
    public function actionLog($env, $branch = 'master')
    {
        $deployment = craft()->boost_deployment;

        $deployment->showLog($env, $branch);
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

        $deployment->showVersions();
    }
}
