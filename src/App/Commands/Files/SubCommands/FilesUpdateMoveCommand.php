<?php

namespace Lio\App\Commands\Files\SubCommands;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateMoveCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s/?%s';

	protected static $defaultName = 'files:update:move';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Move file to another directory')
			->setHelp('Move file to another directory, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of file to move')
			->addArgument('move_path', InputArgument::REQUIRED, 'The target File ID to move to. NOTE: The target directory must exist');
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
				ltrim($input->getArgument('file_id'), '/'),
				'command=move')
		);
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
		$output->writeln('<info>Success, file ' . $input->getArgument('file_id') . ' has been moved</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'target' => $input->getArgument('move_path'),
				],
				'id'         => ltrim($input->getArgument('file_id'), '/'),
				'type'       => 'files',
			],
		], JSON_UNESCAPED_SLASHES);

	}
}
