<?php

namespace Console\App\Commands\Files;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:update';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('This will update the file at specified file ID (file path including file name, relative to app root)')
			->setHelp('https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'File path on app, that should be updated')
			->addArgument('file', InputArgument::OPTIONAL, 'Path to a local file, which content will sent to remote. If not specified, will make your <remote_path> appache writable', '');
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
		try {
			if (!empty($input->getArgument('file'))) {
				$this->validateArguments($input->getArgument('file'));
			}
			$this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					$input->getArgument('remote_path')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody(
						$input->getArgument('file'),
						$input->getArgument('remote_path')
					),
				]);
			$output->writeln('<info>Success, file ' . $input->getArgument('remote_path') . ' has been updated</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}

	/**
	 * @param $file
	 */
	protected function validateArguments($file)
	{
		if (!file_exists($file)) {
			throw new \InvalidArgumentException('File not exists');
		}
	}

	/**
	 * @param string $localFile
	 * @param string $remoteFile
	 * @return string
	 */
	protected function getRequestBody(string $localFile, string $remoteFile): string
	{
		return json_encode([
			'data' => [
				'attributes' =>
					array_merge([
						'apache_writable' => true,
						'target'           => '',
					], !empty($localFile) ? [
						'contents' => file_get_contents($localFile),
					] : []),
				'id'         => $remoteFile,
				'type'       => 'files',
			],
		]);

	}

}