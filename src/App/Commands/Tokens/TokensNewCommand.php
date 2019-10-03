<?php

namespace Lio\App\Commands\Tokens;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TokensNewCommand extends AbstractNewCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens';

	protected static $defaultName = 'tokens:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new token')
			->setHelp('Creates a new token, api reference' . PHP_EOL . 'https://www.lamp.io/api#/tokens/tokensCreate')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Token description', '')
			->addOption('enable', null, InputOption::VALUE_NONE, 'Enable token')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$table = $this->getTableOutput(
			$document,
			'Token',
			[
				'Id' => 'data.id',
				'Token' => 'data.attributes.token',
				'Enabled' => 'data.attributes.enabled',
				'Created at' => 'data.attributes.created_at',
			],
			new Table($output)
		);
		$table->render();
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
					'description' => $input->getOption('description'),
					'enabled'     => $input->getOption('enable'),
				],
				'type'       => 'tokens',
			],
		]);
	}
}