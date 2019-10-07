<?php


namespace Lio\App\AbstractCommands;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDescribeCommand extends AbstractCommand
{
	/**
	 * @var array
	 */
	protected $skipAttributes = [];

	/**
	 * @param array $skipAttributes
	 */
	public function setSkipAttributes(array $skipAttributes): void
	{
		$this->skipAttributes = $skipAttributes;
	}

	/**
	 * @return array
	 */
	public function getSkipAttributes(): array
	{
		return $this->skipAttributes;
	}

	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(InputInterface $input): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			$this->getApiEndpoint(),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
	}


	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$table = $this->getTableOutput(
			$document,
			$document->get('data.id'),
			new Table($output),
			$this->getSkipAttributes()
		);
		$table->render();
	}

	/**
	 * @param Document $document
	 * @param string $title
	 * @param Table $table
	 * @param array $skipKeys
	 * @param int $wordWrap
	 * @return Table
	 */
	protected function getTableOutput(Document $document, string $title, Table $table, array $skipKeys = [], int $wordWrap = 20): Table
	{
		$table->setHeaderTitle($title);
		$table->setHeaders(['Attribute', 'Value']);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		foreach ($serializedDocument['data']['attributes'] as $key => $row) {
			if (empty($row) || in_array($key, $skipKeys)) {
				continue;
			}
			if ($row === true || $row === false) {
				$row = $row ? 'true' : 'false';
			}
			$tableRow[] = $key;
			$value = wordwrap(trim(preg_replace(
				'/\s\s+|\t/', ' ', $row
			)), $wordWrap, PHP_EOL);
			$table->addRow([$key, $value]);
		}

		return $table;
	}
}