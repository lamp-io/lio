<?php

namespace Lio\App\Commands\AutodeploysBranch;

use Exception;
use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysBranchDeleteCommand extends AbstractDeleteCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys_branch/%s';

	protected static $defaultName = 'autodeploysBranch:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an autodeploys_branch.')
			->setHelp('Delete autodeploy, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchDelete')
			->addArgument('autodeploy_branch_id', InputArgument::REQUIRED, 'The ID of the autoDeploysBranch');
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
		return parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Autodeploy Branch ' . $input->getArgument('autodeploy_branch_id') . ' has been deleted</info>');
	}

}
