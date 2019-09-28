<?php

namespace Console\App\Commands\Files;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s%s';

	const BOOL_OPTIONS = [
		'apache_writable',
	];

	protected static $defaultName = 'files:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update file at file_id(file path including file name, relative to app root)')
			->setHelp('Update files, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to update')
			->addArgument('file', InputArgument::OPTIONAL, 'Path to a local file; this is uploaded to remote_path', '')
			->addOption('apache_writable', null, InputOption::VALUE_REQUIRED, 'Allow apache to write to the file ID')
			->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Recur into directories')
			->setBoolOptions(['apache_writable']);
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
			if (!empty($input->getArgument('file')) && !file_exists($input->getArgument('file'))) {
				throw new InvalidArgumentException('File ' . $input->getArgument('file') . ' not exists');
			}
			if (!empty($input->getOption('recursive')) && empty($input->getOption('apache_writable'))) {
				throw new InvalidArgumentException('[--recursive][-r] can be used only in pair with [--apache_writable]');
			}
			$progressBar = self::getProgressBar('Updating ' . $input->getArgument('file_id'), $output);
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					$input->getArgument('file_id'),
					!empty($input->getOption('recursive')) ? '?recur=true' : ''
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						$input->getArgument('file'),
						$input->getArgument('file_id'),
						$input->getOption('apache_writable')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			$output->write(PHP_EOL);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln('<info>Success, file ' . $input->getArgument('file_id') . ' has been updated</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $localFile
	 * @param string $remoteFile
	 * @param bool $isApacheWritable
	 * @return string
	 */
	protected function getRequestBody(string $localFile, string $remoteFile, bool $isApacheWritable): string
	{
		$body = [
			'data' => [
				'id'         => $remoteFile,
				'type'       => 'files',
				'attributes' => [],
			],
		];
		if (!empty($localFile)) {
			$body['data']['attributes']['contents'] = file_get_contents($localFile);
		}
		if (!empty($isApacheWritable)) {
			$body['data']['attributes']['apache_writable'] = true;
		}
		return json_encode($body);
	}

}
