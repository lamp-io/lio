<?php

namespace Lio\App\Commands\Files\SubCommands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesNewCommand extends AbstractNewCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create a file on your app')
			->setHelp('Create a file, api reference' . PHP_EOL . 'https://www.lamp.io/api#/files/filesCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('file_id', InputArgument::REQUIRED, 'File ID of a file to create')
			->addArgument('contents', InputArgument::OPTIONAL, 'File content', '')
			->addOption('apache_writable', null, InputOption::VALUE_REQUIRED, 'Allow apache to write to the file ID')
			->addOption('source', null, InputOption::VALUE_REQUIRED, 'A URL to that will be retrieved for fetch content')
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
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('app_id'),
			!empty($input->getOption('source')) ? '?command=fetch&source=' . $input->getOption('source') : ''
		));
		parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$output->writeln('<info>Success, file ' . $document->get('data.id') . ' has been created</info>');
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$body = [
			'data' => [
				'type'       => 'files',
				'id'         => $input->getArgument('file_id'),
				'attributes' => [
					'contents' => $input->getArgument('contents'),
				],
			],
		];

		if (!empty($input->getOption('apache_writable')) && $input->getOption('apache_writable') != 'false') {
			$body['data']['attributes']['apache_writable'] = true;
		}
		return json_encode($body);
	}
}