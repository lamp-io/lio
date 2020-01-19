<?php

namespace Lio\App\Commands\Keys;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeysNewCommand extends AbstractNewCommand
{
	protected static $defaultName = 'keys:new';

	const API_ENDPOINT = 'https://api.lamp.io/keys';

	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new key')
			->setHelp('Create key, api reference' . PHP_EOL . 'https://www.lamp.io/api#/keys/keysCreate')
			->addArgument('organization_id', InputArgument::REQUIRED, '')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'An immutable description for this key')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$table = $this->getTableOutput(
			$document,
			'Key',
			[
				'Id'              => 'document.id',
				'Description'     => 'document.attributes.description',
				'Organization id' => 'document.attributes.organization_id',
				'Ssh public key'  => 'document.attributes.ssh_pub_key',
				'Created at'      => 'document.attributes.created_at',
			],
			new Table($output)
		);
		$table->render();
	}

	protected function getRequestBody(InputInterface $input): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'description'     => $input->getOption('description') ?? '',
					'organization_id' => $input->getArgument('organization_id'),
				],
				'type'       => 'keys',
			],
		]);
	}

}
