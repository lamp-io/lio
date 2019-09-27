<?php


namespace Console\App\Commands\AppRestores;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppRestoresNewCommand extends Command
{
	/**
	 * @var string
	 */
	protected static $defaultName = 'app_restores:new';

	/**
	 *
	 */
	const API_ENDPOINT = 'https://api.lamp.io/app_restores';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Restore files to an app')
			->setHelp('Restores files in app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_restores/appRestoresCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup');
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
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input->getArgument('app_id'), $input->getArgument('app_backup_id')),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$appRestoreId = $document->get('data.id');
				$progressBar = Command::getProgressBar('Restoring app ' . $input->getArgument('app_id'), $output);
				$progressBar->start();
				while (!AppRestoresDescribeCommand::isAppRestoreCompleted($appRestoreId, $this->getApplication())) {
					$progressBar->advance();
				}
				$progressBar->finish();
				$output->write(PHP_EOL);
				$output->writeln('<info>App restore finished</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;

		}
	}

	/**
	 * @param string $appId
	 * @param string $backupId
	 * @return string
	 */
	protected function getRequestBody(string $appId, string $backupId): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'target_app_id' => $appId,
					'app_backup_id' => $backupId,
				],
				'type'       => 'app_restores',
			],
		]);
	}
}