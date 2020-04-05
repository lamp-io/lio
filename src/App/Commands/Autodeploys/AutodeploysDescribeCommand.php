<?php

namespace Lio\App\Commands\Autodeploys;

use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class AutodeploysDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys/%s';

	protected static $defaultName = 'autodeploys:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns an autodeploy')
			->setHelp('Get selected autodeploy, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys/autoDeploysShow')
			->addArgument('autodeploy_id', InputArgument::REQUIRED, 'The id of the autodeploy');
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
			$input->getArgument('autodeploy_id')
		));
		$this->skipAttributes = ['updated_at'];
		return parent::execute($input, $output);
	}
}
