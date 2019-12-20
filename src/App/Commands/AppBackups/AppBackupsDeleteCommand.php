<?php

namespace Lio\App\Commands\AppBackups;

use Lio\App\AbstractCommands\AbstractDeleteCommand;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsDeleteCommand extends AbstractDeleteCommand
{
	protected static $defaultName = 'app_backups:delete';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete an app backup')
			->setHelp('Delete an app backup, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_backups/appBackupsShow')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup');
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
			$input->getArgument('app_backup_id')
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
		$output->writeln('<info>Backup ' . $input->getArgument('app_backup_id') . ' has been deleted</info>');
	}

}