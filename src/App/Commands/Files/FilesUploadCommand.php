<?php


namespace Console\App\Commands\Files;


use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;

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
			->setHelp('Allow you to create new file, api reference https://www.lamp.io/api#/files/filesCreate')
			->addArgument('file', InputArgument::REQUIRED, 'Local path of file to upload')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to save');
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
		if (!file_exists($input->getArgument('file'))) {
			$output->writeln('<error>File not exists</error>');
			return 1;
		}

		try {
			$progressBar = self::getProgressBar('Uploading ' . $input->getArgument('file'), $output);
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
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
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
