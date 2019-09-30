<?php


namespace Lio\App\Commands\AppRestores;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AppRestoresDescribeCommand extends Command
{
	protected static $defaultName = 'app_restores:describe';

	const API_ENDPOINT = 'https://api.lamp.io/app_restores/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return an app restore')
			->setHelp('Get an app restore, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_restores/appRestoresShow')
			->addArgument('app_restore_id', InputArgument::REQUIRED, 'The ID of the app restore');
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
			'Getting app restore ' . $input->getArgument('app_restore_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_restore_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->write(PHP_EOL);
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;

		}
	}

	/**
	 * @param string $appRestoreId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public static function isAppRestoreCompleted(string $appRestoreId, Application $application): bool
	{
		$appRunsDescribeCommand = $application->find(self::getDefaultName());
		$args = [
			'command'        => self::getDefaultName(),
			'app_restore_id' => $appRestoreId,
			'--json'         => true,
		];
		$bufferOutput = new BufferedOutput();
		$appRunsDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		$commandResponse = $bufferOutput->fetch();
		/** @var Document $document */
		$document = Parser::parseResponseString($commandResponse);
		if (!$document->has('data.attributes.status') || $document->get('data.attributes.status') === 'failed') {
			throw new Exception('App restore job failed ' . PHP_EOL . $commandResponse);
		}
		return $document->get('data.attributes.complete');
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('App Restore ' . $document->get('data.id'));
		$table->setHeaders(['App backup Id', 'Complete', 'Created at', 'Organization id', 'Status']);
		$table->addRow([
			$document->get('data.attributes.app_backup_id'),
			$document->get('data.attributes.complete'),
			$document->get('data.attributes.created_at'),
			$document->get('data.attributes.organization_id'),
			$document->get('data.attributes.status'),
		]);
		return $table;
	}
}