<?php


namespace Lio\App\Commands\Files;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;

class FilesDownloadCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:download';

	const RESPONSE_FORMAT_TYPES = [
		'gzip'   => [
			'AcceptHeader' => 'Accept: application/x-gzip',
		],
		'zip'    => [
			'AcceptHeader' => 'Accept: application/zip',
		],
		'stream' => [
			'AcceptHeader' => 'Accept: application/octet-stream',
		],
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Download files')
			->setHelp('Download files, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesShow')
			->addArgument('app_id', InputArgument::REQUIRED, 'App ID')
			->addArgument('file_id', InputArgument::REQUIRED, 'The ID of the file. The ID is also the file path relative to its app root.')
			->addArgument('dir', InputArgument::OPTIONAL, 'Local path for downloaded file', getcwd());
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
			'Downloading',
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$appId = $input->getArgument('app_id');
			$fileId = $input->getArgument('file_id');
			$filePathAsArray = explode('/', rtrim($fileId, '/'));
			$isDirectory = $this->isDirectory($appId, $fileId);
			$fileName = $isDirectory ? end($filePathAsArray) . '.zip' : '';
			$this->download(
				$appId,
				$fileId,
				$fileName,
				rtrim($input->getArgument('dir') . DIRECTORY_SEPARATOR),
				$isDirectory,
				$progressBar
			);
			$output->write(PHP_EOL);
			$output->writeln(
				'<info>File received, ' . rtrim($input->getArgument('dir')) . DIRECTORY_SEPARATOR . $fileName . '</info>'
			);
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $appId
	 * @param string $fileId
	 * @param string $fileName
	 * @param string $downloadPath
	 * @param bool $isDirectory
	 * @param ProgressBar $progressBar
	 * @throws GuzzleException
	 * @return ResponseInterface
	 */
	protected function download(string $appId, string $fileId, string $fileName, string $downloadPath, bool $isDirectory, ProgressBar $progressBar): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($fileId)
			),
			[
				'headers'  => [
					'Authorization' => $this->httpHelper->getHeader('Authorization'),
					$this->httpHelper->getHeader('Content-type'),
					'Accept'        => $isDirectory ? self::RESPONSE_FORMAT_TYPES['zip']['AcceptHeader'] : self::RESPONSE_FORMAT_TYPES['stream']['AcceptHeader'],
				],
				'progress' => function () use ($progressBar) {
					$progressBar->advance();
				},
				'sink'     => fopen($downloadPath . DIRECTORY_SEPARATOR . $fileName, 'w+'),
			]
		);
	}

	/**
	 * @param string $appId
	 * @param string $fileId
	 * @return bool
	 * @throws GuzzleException
	 */
	protected function isDirectory(string $appId, string $fileId): bool
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($this->getFileInfo($appId, $fileId)->getBody()->getContents());
		return $document->get('data.attributes.is_dir');
	}


	/**
	 * @param string $appId
	 * @param string $fileId
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function getFileInfo(string $appId, string $fileId): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($fileId)
			),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
	}
}
