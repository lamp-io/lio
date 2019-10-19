<?php


namespace Lio\App\Commands\Tokens;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class TokensListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens';

	/**
	 * @var string
	 */
	protected static $defaultName = 'tokens:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns all tokens for this user')
			->setHelp('Get all tokens for this user, api reference' . PHP_EOL . 'https://www.lamp.io/api#/tokens/tokensList')
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
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = CommandsHelper::sortData($serializedDocument['data'], 'updated_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Tokens',
			[
				'Id'           => 'data.%d.id',
				'Description'  => 'data.%d.attributes.description',
				'Created at'   => 'data.%d.attributes.created_at',
				'Last used at' => 'data.%d.attributes.last_used_at',
				'Enabled'      => 'data.%d.attributes.enabled',
			],
			new Table($output),
			end($sortedData) ? end($sortedData) : [],
			80
		);
		$table->render();
	}
}