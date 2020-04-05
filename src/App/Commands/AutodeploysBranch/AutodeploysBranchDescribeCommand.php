<?php

namespace Lio\App\Commands\AutodeploysBranch;

use Exception;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysBranchDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys_branch/%s';

	protected static $defaultName = 'autodeploysBranch:describe';

	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return an autodeploy_branch.')
			->setHelp('Get selected autodeployBranch, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchShow')
			->addArgument('autodeploy_branch_id', InputArgument::REQUIRED, 'The id of the autoDeploysBranch');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('autodeploy_branch_id')
		));
		$this->skipAttributes = ['updated_at'];
		return parent::execute($input, $output);
	}
}
