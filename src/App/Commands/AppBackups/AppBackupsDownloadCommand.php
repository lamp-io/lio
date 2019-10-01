<?php

namespace Lio\App\Commands\AppBackups;

use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsDownloadCommand extends Command
{
	protected static $defaultName = 'app_backups:download';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Download an app backup')
			->setHelp('Download an app backup, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_backups/appBackupsShow')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup')
			->addArgument('dir', InputArgument::OPTIONAL, 'Local path for downloaded file', getcwd());
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		$progressBar = self::getProgressBar(
			'Downloading app backup ' . $input->getArgument('app_backup_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$output->writeln('<info>Downloading started</info>');
			$this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_backup_id')
				),
				[
					'headers' => array_merge(
						$this->httpHelper->getHeaders(),
						['Accept' => 'application/x-gzip']
					),
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
					'sink'    => fopen($input->getArgument('dir') . DIRECTORY_SEPARATOR . $input->getArgument('app_backup_id') . '.tar.gz', 'w+'),
				]

			);
			$output->write(PHP_EOL);
			$output->writeln(
				'<info>File received, ' . $input->getArgument('dir') . DIRECTORY_SEPARATOR . $input->getArgument('app_backup_id') . '.tar.gz' . '</info>'
			);
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

}
