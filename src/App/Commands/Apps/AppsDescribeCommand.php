<?php

namespace Lio\App\Commands\Apps;

use Exception;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Exception\GuzzleException;

class AppsDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'apps:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return your app')
			->setHelp('Get selected app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsShow')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_id')
		));
		$this->setSkipAttributes([
			'github_webhook_secret',
			'httpd_conf',
			'php_ini',
			'ssh_pub_key',
			'webhook_run_command',
		]);
		parent::execute($input, $output);
	}
}
