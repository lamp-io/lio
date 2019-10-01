<?php


namespace Lio\App\Commands\Files;


use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lio\App\Commands\Command;

class FilesUploadCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files';

	protected static $defaultName = 'files:upload';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates new file')
			->setHelp('Create new file, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesCreate')
			->addArgument('file', InputArgument::REQUIRED, 'Local path of file to upload')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to save');
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
		if (!file_exists($input->getArgument('file'))) {
			$output->writeln('<error>File not exists</error>');
			return 1;
		}

		try {
			$progressBar = self::getProgressBar(
				'Uploading ' . $input->getArgument('file'),
				$output
			);
			$this->httpHelper->getClient()->request(
				'POST',
				sprintf(self::API_ENDPOINT, $input->getArgument('app_id')),
				[
					'headers'   => [
						'Accept'        => 'application/json',
						'Authorization' => $this->httpHelper->getHeader('Authorization'),
					],
					'multipart' => [
						[
							'name'     => $this->getRemoteFileName(
								$input->getArgument('file_id'),
								$input->getArgument('file')
							),
							'contents' => fopen($input->getArgument('file'), 'r'),
						],
					],
					'progress'  => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (empty($input->getOption('json'))) {
				$output->writeln(PHP_EOL . '<info>File ' . $this->getRemoteFileName(
						$input->getArgument('file_id'),
						$input->getArgument('file')
					) . ' successfully uploaded</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $remoteFile
	 * @param string $localFilePath
	 * @return string
	 */
	protected function getRemoteFileName(string $remoteFile, string $localFilePath): string
	{
		$localFilePathAsArray = explode(DIRECTORY_SEPARATOR, $localFilePath);
		$localFileName = $localFilePathAsArray[count($localFilePathAsArray) - 1];
		return $this->isRemoteFileNameSpecified($remoteFile) ? $remoteFile : $remoteFile . $localFileName;
	}

	/**
	 * @param string $remoteFile
	 * @return bool
	 */
	protected function isRemoteFileNameSpecified(string $remoteFile): bool
	{
		return $remoteFile[strlen($remoteFile) - 1] != DIRECTORY_SEPARATOR;
	}

}
