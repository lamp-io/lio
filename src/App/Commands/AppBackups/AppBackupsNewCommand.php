<?php


namespace Lio\App\Commands\AppBackups;

use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsNewCommand extends Command
{
	protected static $defaultName = 'app_backups:new';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Back up files in app')
			->setHelp('Backup files in app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_backups/appBackupsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
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

		try {
			$this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input->getArgument('app_id')),
				]
			);
			$output->writeln('<info>Backuping app with id ' . $input->getArgument('app_id') . ', started</info>');
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $appId
	 * @return string
	 */
	protected function getRequestBody(string $appId): string
	{
		return json_encode([
			'data' =>
				[
					'attributes' =>
						[
							'app_id' => $appId,
						],
					'type'       => 'app_backups',
				],
		]);
	}
}