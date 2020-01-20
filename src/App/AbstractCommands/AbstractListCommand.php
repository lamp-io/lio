<?php


namespace Lio\App\AbstractCommands;

use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractListCommand extends AbstractCommand
{
	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(InputInterface $input): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			$this->apiEndpoint,
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
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
			foreach ($rows as $row) {
				$tableValue = $document->get(sprintf($row, $key));
				if ($tableValue === true || $tableValue === false) {
					$tableValue = $tableValue ? 'true' : 'false';
				}
				$tableRow[] = wordwrap(trim(preg_replace(
					'/\s\s+|\t/', ' ', $tableValue
				)), $wordWrap, PHP_EOL, $cutWord);
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
