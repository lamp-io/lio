<?php


namespace Lio\App\Commands\Apps;

use Exception;
use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppsDeleteCommand extends AbstractDeleteCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s';

	protected static $defaultName = 'apps:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an app')
			->setHelp('Delete app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/apps/appsDestroy')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
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
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>App ' . $input->getArgument('app_id') . ' has been deleted</info>');
	}


}
