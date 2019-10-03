<?php


namespace Lio\App\Commands\AppBackups;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_backups%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	/**
	 * @var string
	 */
	protected static $defaultName = 'app_backups:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return app backups')
			->setHelp('Get all app backups, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_backups/appBackupsList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
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
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'created_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'App backups',
			[
				'Id'              => 'data.%d.id',
				'App Id'          => 'data.%d.attributes.app_id',
				'Created at'      => 'data.%d.attributes.created_at',
				'Organization Id' => 'data.%d.attributes.organization_id',
				'Status'          => 'data.%d.attributes.status',
			],
			new Table($output),
			end($sortedData)
		);
		$table->render();
	}
}