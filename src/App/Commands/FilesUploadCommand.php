<?php


namespace Console\App\Commands;


use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUploadCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files';

	protected static $defaultName = 'files:upload';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Creates new file')
			->setHelp('https://www.lamp.io/api#/files/filesCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file', InputArgument::REQUIRED, 'Path to file, that should be uploaded')
			->addArgument('remote_path', InputArgument::REQUIRED, 'Path on app, where uploaded file should be saved');
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
			die();
		}

		try {
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
								$input->getArgument('remote_path'),
								$input->getArgument('file')
							),
							'contents' => fopen($input->getArgument('file'), 'r'),
						],
					],
				]);
			$output->writeln('<info>File ' . $this->getRemoteFileName(
					$input->getArgument('remote_path'),
					$input->getArgument('file')
				) . ' successfully uploaded</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
		}
	}

	/**
	 * @param string $remoteFile
	 * @param string $localFileName
	 * @return string
	 */
	protected function getRemoteFileName(string $remoteFile, string $localFileName): string
	{
		return $this->isRemoteFileNameSpecified($remoteFile) ? $remoteFile : $remoteFile . $localFileName;
	}

	/**
	 * @param string $remoteFile
	 * @return bool
	 */
	protected function isRemoteFileNameSpecified(string $remoteFile): bool
	{
		return $remoteFile[strlen($remoteFile) - 1] != '/';
	}

}