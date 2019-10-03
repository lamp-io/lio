<?php


namespace Lio\App\AbstractCommands;

use Art4\JsonApiClient\V1\Document;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Lio\App\Console\Command;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractListCommand extends Command
{
	/**
	 * @var string
	 */
	protected $apiEndpoint = '';

	/**
	 * @param string $apiEndpoint
	 */
	public function setApiEndpoint(string $apiEndpoint): void
	{
		$this->apiEndpoint = $apiEndpoint;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				$this->apiEndpoint,
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$this->renderOutput($response, $output);
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @return null
	 */
	abstract protected function renderOutput(ResponseInterface $response, OutputInterface $output);

	/**
	 * @param array $data
	 * @param Document $document
	 * @param string $title
	 * @param array $rows
	 * @param Table $table
	 * @param array $lastElement
	 * @param int $wordWrap
	 * @return Table
	 */
	protected function getTableOutput(array $data, Document $document, string $title, array $rows, Table $table, array $lastElement = [], int $wordWrap = 20): Table
	{
		$table->setHeaderTitle($title);
		$table->setHeaders(array_keys($rows));
		foreach ($data as $key => $val) {
			if (empty($val)) {
				break;
			}
			$tableRow = [];
			foreach ($rows as $row) {
				$tableValue = $document->get(sprintf($row, $key));
				if ($tableValue === true || $tableValue === false) {
					$tableValue = $tableValue ? 'true' : 'false';
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
}