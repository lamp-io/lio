<?php


namespace Lio\App\Commands\AppRestores;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Lio\App\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppRestoresNewCommand extends AbstractNewCommand
{
	/**
	 *
	 */
	const API_ENDPOINT = 'https://api.lamp.io/app_restores';

	/**
	 * @var string
	 */
	protected static $defaultName = 'app_restores:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Restore files to an app')
			->setHelp('Restores files in app, api reference' . PHP_EOL . 'https://www.lamp.io/api#/app_restores/appRestoresCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface|null $output
	 * @param InputInterface $input
	 * @throws GuzzleException
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$appRestoreId = $document->get('data.id');
		$progressBar = Command::getProgressBar('Restoring app ' . $document->get('data.attributes.target_app_id'), $output);
		$progressBar->start();
		while (!AppRestoresDescribeCommand::isAppRestoreCompleted($appRestoreId, $this->getApplication())) {
			$progressBar->advance();
		}
		$progressBar->finish();
		$output->write(PHP_EOL);
		$output->writeln('<info>Restore finished for app, ' . $document->get('data.attributes.target_app_id') . '</info>');

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
					'target_app_id' => $input->getArgument('app_id'),
					'app_backup_id' => $input->getArgument('app_backup_id'),
				],
				'type'       => 'app_restores',
			],
		]);
	}
}