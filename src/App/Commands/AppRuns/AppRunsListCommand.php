<?php


namespace Lio\App\Commands\AppRuns;

use Exception;
use Lio\App\AbstractCommands\AbstractListCommand;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Symfony\Component\Console\Input\InputInterface;

class AppRunsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'page_number'     => 'page[number]',
		'page_size'       => 'page[size]',
		'organization_id' => 'filter[organization_id]',
		'output_lines'    => 'output[max_lines]',
	];

	protected static $defaultName = 'app_runs:list';

	/**
	 * @var int
	 */
	protected $outputMaxLines;

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return all app runs for all user\'s organizations')
			->setHelp('Get all app runs for all user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_runs/appRunsList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Filter output by organization id value')
			->addOption('page_number', null, InputOption::VALUE_REQUIRED, 'Pagination page', '1')
			->addOption('page_size', null, InputOption::VALUE_REQUIRED, 'Count per paginated page', '100')
			->addOption('output_lines', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of lines returned. 1 is Unlimited', '5');
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
			$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
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
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'created_at');
		$this->outputMaxLines = $input->getOption('output_lines');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'App runs',
			[
				'Id'         => 'data.%d.id',
				'App ID'     => 'data.%d.attributes.app_id',
				'Created at' => 'data.%d.attributes.created_at',
				'Complete'   => 'data.%d.attributes.complete',
				'Command'    => 'data.%d.attributes.command',
				'Status'     => 'data.%d.attributes.status',
			],
			new Table($output),
			end($sortedData) ? end($sortedData) : []
		);
		$table->render();
	}

	/**
	 * @param array $data
	 * @param Document $document
	 * @param string $title
	 * @param array $rows
	 * @param Table $table
	 * @param array $lastElement
	 * @param int $wordWrap
	 * @param bool $cutWord
	 * @return Table
	 */
	protected function getTableOutput(array $data, Document $document, string $title, array $rows, Table $table, array $lastElement = [], int $wordWrap = 20, bool $cutWord = false): Table
	{
		$table->setHeaderTitle($title);
		$table->setHeaders(array_keys($rows));
		foreach ($data as $key => $val) {
			if (empty($val)) {
				break;
			}
			$tableRow = [];
			foreach ($rows as $lineHeader => $row) {
				$tableValue = $document->get(sprintf($row, $key));
				if ($tableValue === true || $tableValue === false) {
					$tableValue = $tableValue ? 'true' : 'false';
				}
				if ($lineHeader === 'Command') {
					$tableValue = $this->truncateCommandString($tableValue, $this->outputMaxLines);
				}
				$tableRow[] = wordwrap(trim(preg_replace(
					'/\s\s+|\t/', ' ', $tableValue
				)), $wordWrap, PHP_EOL);
			}
			$table->addRow($tableRow);
			if (!empty($lastElement) && $val != $lastElement) {
				$table->addRow(new TableSeparator());
			} elseif ($key != count($data) - 1 && empty($lastElement)) {
				$table->addRow(new TableSeparator());
			}
		}
		return $table;
	}

	private function truncateCommandString(string $command, int $lineLimit): string
	{
		$lines = explode(PHP_EOL, $command);
		return implode(PHP_EOL, array_slice($lines, 0, $lineLimit === 1 ? count($lines) : $lineLimit));
	}
}
