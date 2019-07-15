<?php


namespace Console\App\Commands\AppBackups;


use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_backups';

	/**
	 * @var string
	 */
	protected static $defaultName = 'app:backup:list';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Return app backups')
			->setHelp('https://www.lamp.io/api#/app_backups/appBackupsList')
			->addOption('json', 'j', InputOption::VALUE_NONE, 'Output as raw json');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'proxy'   => 'localhost:3127',
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			exit(1);
		}
	}

	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Backups');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'App Id', 'Complete', 'Created at', 'Organization Id', 'Status', 'Updated at',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		foreach ($serializedDocument['data'] as $key => $value) {
			$table->addRow([
				$value['id'],
				$document->get('data.' . $key . '.attributes.app_id'),
				$document->get('data.' . $key . '.attributes.complete'),
				$document->get('data.' . $key . '.attributes.created_at'),
				$document->get('data.' . $key . '.attributes.organization_id'),
				$document->get('data.' . $key . '.attributes.status'),
				$document->get('data.' . $key . '.attributes.updated_at'),
			]);
			if ($key != count($serializedDocument['data']) - 1) {
				$table->addRow(new TableSeparator());
			}

		}
		return $table;
	}
}