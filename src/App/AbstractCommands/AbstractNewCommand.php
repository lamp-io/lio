<?php


namespace Lio\App\AbstractCommands;

use Art4\JsonApiClient\V1\Document;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use GuzzleHttp\Exception\GuzzleException;

abstract class AbstractNewCommand extends AbstractCommand
{
	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(InputInterface $input): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'POST',
			$this->getApiEndpoint(),
			[
				'headers' => $this->httpHelper->getHeaders(),
				'body'    => $this->getRequestBody($input),
			]
		);
	}


	/**
	 * @param string $apiEndpoint
	 */
	public function setApiEndpoint(string $apiEndpoint): void
	{
		$this->apiEndpoint = $apiEndpoint;
	}

	/**
	 * @return string
	 */
	public function getApiEndpoint(): string
	{
		return $this->apiEndpoint;
	}


	/**
	 * @param InputInterface $input
	 * @return mixed
	 */
	abstract protected function getRequestBody(InputInterface $input);

	/**
	 * @param Document $document
	 * @param string $title
	 * @param array $rows
	 * @param Table $table
	 * @return Table
	 */
	protected function getTableOutput(Document $document, string $title, array $rows, Table $table): Table
	{
		$table->setHeaderTitle($title);
		$table->setHeaders(array_keys($rows));
		$tableRow = [];
		foreach ($rows as $row) {
			$tableValue = $document->get($row);
			if ($tableValue === true || $tableValue === false) {
				$tableValue = $tableValue ? 'true' : 'false';
			}
			$tableRow[] = wordwrap($tableValue, '30', PHP_EOL);
		}
		$table->addRow($tableRow);

		return $table;
	}
}