<?php

namespace Lio\App\Commands\Files;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s%s';

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
			->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Recur into directories (works only with --apache_writable)')
			->setBoolOptions(['apache_writable']);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_id'),
			$input->getArgument('file_id'),
			!empty($input->getOption('recursive')) ? '?recur=true' : ''
		));
		if (!empty($input->getArgument('file')) && !file_exists($input->getArgument('file'))) {
			throw new InvalidArgumentException('File ' . $input->getArgument('file') . ' not exists');
		}
		if (!empty($input->getOption('recursive')) && empty($input->getOption('apache_writable'))) {
			throw new InvalidArgumentException('[--recursive][-r] can be used only in pair with [--apache_writable]');
		}
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		$output->writeln('<info>Success, file ' . $input->getArgument('file_id') . ' has been updated</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$body = [
			'data' => [
				'id'         => $input->getArgument('file_id'),
				'type'       => 'files',
				'attributes' => [],
			],
		];
		if (!empty($input->getArgument('file'))) {
			$body['data']['attributes']['contents'] = file_get_contents($input->getArgument('file'));
		}
		if (!empty($input->getOption('apache_writable'))) {
			$body['data']['attributes']['apache_writable'] = $input->getOption('apache_writable') != 'false';
		}
		return json_encode($body);
	}

}
