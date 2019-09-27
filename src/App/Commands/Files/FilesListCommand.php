<?php


namespace Console\App\Commands\Files;


use Art4\JsonApiClient\Exception\ValidationException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Console\App\Commands\Command;

class FilesListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	const MAX_LIMIT = '1000';

	protected static $defaultName = 'files:list';

	/**
	 * @var int
	 */
	protected $counter = 0;

	/**
	 * @var array
	 */
	protected $dataToOutput = [];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return files from the root of an app')
			->setHelp('Get files from the root of an app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesList')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::OPTIONAL, 'The ID of the file. The ID is also the file path relative to its app root.', '/')
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, ' The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out', self::MAX_LIMIT)
			->addOption('human-readable', '', InputOption::VALUE_NONE, 'Format size values from raw bytes to human readable format')
			->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Command is performed on all files or objects under the specified path');
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
			$response = $this->sendRequest($input->getArgument('app_id'), $input->getArgument('file_id'));
			$responseBody = $response->getBody()->getContents();
			if (!empty($input->getOption('json'))) {
				$output->writeln($responseBody);
			} else {
				if (!$this->isDirectory($responseBody)) {
					$table = $this->getTableFileInfo($responseBody, new Table($output));
				} else {
					$this->prepareOutput($input, $responseBody);
					$table = $this->getTableFilesList($this->dataToOutput, new Table($output));
				}
				$table->render();
			}

		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		} catch (ValidationException $validationException) {
			$output->writeln('<error>' . $validationException->getMessage() . '</error>');
			return 1;
		}

	}

	protected function getTableFileInfo(string $responseBody, Table $table): Table
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($responseBody);
		$table->setHeaderTitle($document->get('data.id'));
		$table->setHeaders(['Metadata', 'Content']);
		$table->addRow([
			implode(PHP_EOL, [
				'file_name: ' . $document->get('data.attributes.file_name'),
				'size: ' . $this->formatBytes($document->get('data.attributes.size')),
				'mime_type: ' . $document->get('data.attributes.mime_type'),
				'modify_time: ' . $document->get('data.attributes.modify_time'),
				$document->get('data.attributes.apache_writable') ? 'apache_writable: true' : 'apache_writable: false',
				'file_mode: ' . $document->get('data.attributes.file_mode'),
				$document->get('data.attributes.is_symlink') ? 'is_symlink: true' : 'is_symlink: false',
				$document->get('data.attributes.is_symlink') ? 'target: ' . $document->get('data.attributes.target') : '',
			]),
			wordwrap(trim(preg_replace('/\t/', ' ', $document->get('data.attributes.contents'))), 80, PHP_EOL),
		]);
		return $table;
	}

	/**
	 * @param array $data
	 * @param string $fieldName
	 * @return array
	 */
	protected function sortData(array $data, string $fieldName): array
	{
		uasort($data, function ($a, $b) use ($fieldName) {
			if ($a['isDir'] && !$b['isDir']) {
				return -1;
			} elseif (!$a['isDir'] && $b['isDir']) {
				return 1;
			} else {
				return $a[$fieldName] <=> $b[$fieldName];
			}
		});

		return $data;
	}

	/**
	 * @param array $data
	 * @param Table $table
	 * @return Table
	 */
	protected function getTableFilesList(array $data, Table $table): Table
	{
		$table->setStyle('compact');
		foreach ($this->sortData($data, 'fileName') as $val) {
			$table->addRow([
				$val['timestamp'],
				$val['size'],
				$val['fileName'],
			]);
		}
		return $table;
	}

	/**
	 * @param InputInterface $input
	 * @param string $responseBody
	 * @throws GuzzleException
	 * @throws ValidationException
	 */
	protected function prepareOutput(InputInterface $input, string $responseBody)
	{
		if ($this->counter == $input->getOption('limit') || $this->counter == self::MAX_LIMIT) {
			return;
		}
		/** @var Document $document */
		$document = Parser::parseResponseString($responseBody);
		$serializer = new ArraySerializer(['recursive' => true]);
		$siblings = [];
		if ($document->has('data.relationships.siblings')) {
			$siblingsData = $document->get('data.relationships.siblings.data');
			foreach ($serializer->serialize($siblingsData) as $val) {
				$siblings[] = $val['id'];
			}
		}

		foreach ($serializer->serialize($document->get('included')) as $key => $val) {
			if (empty($val['relationships']) || in_array($val['id'], $siblings)) {
				continue;
			}
			$this->dataToOutput[] = $this->formatFileInfo(
				$val['id'],
				$val['attributes'],
				$input->getOption('human-readable')
			);

			$this->counter += 1;
			if ($val['attributes']['is_dir'] && !empty($input->getOption('recursive'))) {
				$responseBody = $this->sendRequest($input->getArgument('app_id'), $val['id'])->getBody()->getContents();
				$this->prepareOutput($input, $responseBody);
			}
		}
	}

	/**
	 * @param string $appId
	 * @param string $filePath
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(string $appId, string $filePath): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($filePath)
			),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
	}

	/**
	 * @param string $fileName
	 * @param array $fileAttributes
	 * @param bool $isHumanReadable
	 * @return array
	 */
	protected function formatFileInfo(string $fileName, array $fileAttributes, bool $isHumanReadable = true): array
	{
		$date = date('Y-m-d H:i:s', strtotime($fileAttributes['modify_time']));
		$size = $isHumanReadable ? $this->formatBytes($fileAttributes['size']) : $fileAttributes['size'];
		$fileName = $fileAttributes['is_dir'] ? $fileName . '/' : $fileName;
		return [
			'isDir'     => $fileAttributes['is_dir'],
			'timestamp' => $date,
			'size'      => $size,
			'fileName'  => $fileName,
		];
	}

	protected function isDirectory(string $responseBody): bool
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($responseBody);
		return $document->get('data.attributes.is_dir');
	}

	/**
	 * @param int $bytes
	 * @param int $precision
	 * @return string
	 */
	protected function formatBytes(int $bytes, int $precision = 2): string
	{
		$unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		$exp = floor(log($bytes, 1024)) | 0;
		if (empty($unit[$exp])) {
			$result = (string)$bytes;
		} else {
			$result = round($bytes / (pow(1024, $exp)), $precision) . $unit[$exp];
		}
		return $result;
	}
}
