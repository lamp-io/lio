<?php

namespace Lio\App\Commands\Files\SubCommands;

use Lio\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateUnarchiveCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s/?%s';

	protected static $defaultName = 'files:update:unarchive';

	protected $subCommand = ['command' => 'unarchive'];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Extract archive file')
			->setHelp('Extract archive file, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to unarchive');
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
			$progressBar = self::getProgressBar(
				'Extracting ' . $input->getArgument('file_id'),
				(empty($input->getOption('json'))) ? $output : new NullOutput()
			);
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					ltrim($input->getArgument('file_id'), '/'),
					http_build_query($this->subCommand)
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						$input->getArgument('file_id')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				$output->writeln('<info>Success, file ' . $input->getArgument('file_id') . ' has been updated</info>');
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $remoteFile
	 * @return string
	 */
	protected function getRequestBody(string $remoteFile): string
	{
		return json_encode([
			'data' => [
				'id'   => ltrim($remoteFile, '/'),
				'type' => 'files',
			],
		], JSON_UNESCAPED_SLASHES);

	}

}
