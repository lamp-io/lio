<?php

namespace Lio\App\Commands\Keys;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeysNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'keys:new';

	const API_ENDPOINT = 'https://api.lamp.io/keys';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new key')
			->setHelp('Create key, api reference' . PHP_EOL . 'https://www.lamp.io/api#/keys/keysCreate')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'The organization this key belongs to')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'An immutable description for this key')
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
		$output->writeln('<info>Your new key successfully created, key id: ' . $document->get('data.id') . '</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [
			'description'     => $input->getOption('description') ?? '',
			'organization_id' => $input->getOption('organization_id') ?? '',
		];

		return json_encode([
			'data' => [
				'attributes' => array_filter($attributes, function ($attribute) {
					return !empty($attribute);
				}),
				'type'       => 'keys',
			],
		], JSON_FORCE_OBJECT);
	}

}
