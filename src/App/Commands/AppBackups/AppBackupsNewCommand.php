<?php


namespace Lio\App\Commands\AppBackups;

use Art4\JsonApiClient\Document;
use Art4\JsonApiClient\Helper\Parser;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'app_backups:new';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Back up files in app')
			->setHelp('Backup files in app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_backups/appBackupsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$output->writeln(
			'<info>Backuping app, with id ' . $document->get('data.attributes.app_id') . ', started' . PHP_EOL . 'Backup id: ' . $document->get('data.id') . '</info>'
		);
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
					'app_id' => $input->getArgument('app_id'),
				],
				'type'       => 'app_backups',
			],
		]);
	}
}