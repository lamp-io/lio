<?php

namespace Lio\App\Commands\Apps\SubCommands;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppUpdatesStatusCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	protected static $defaultName = 'apps:update:status';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Enable/disable app')
			->setHelp('Enable/disable app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsUpdate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addOption('enable', null, InputOption::VALUE_REQUIRED, 'Enable/disable your stopped app')
			->setBoolOptions(['enable']);
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
			$input->getArgument('app_id')
		));
		if (empty($input->getOption('enable'))) {
			$output->writeln('<error>You need to specify --enable true/false to call this command</error>');
			return 1;
		}
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
		$action = $input->getOption('enable') === 'false' ? 'disabled' : 'enabled';
		$output->writeln('<info>App ' . $input->getArgument('app_id') . ', has been ' . $action . '</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'replicas' => $input->getOption('enable') === 'false' ? 0 : 1,
				],
				'id'         => $input->getArgument('app_id'),
				'type'       => 'apps',
			],
		]);
	}

}