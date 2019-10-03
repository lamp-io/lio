<?php


namespace Lio\App\Commands\Organizations;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractListCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationsListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/organizations';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns this user\'s organizations')
			->setHelp('Get an this user\'s organizations, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organizations/organizationsList')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @return void|null
	 */
	protected function renderOutput(ResponseInterface $response, OutputInterface $output)
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$table = $this->getTableOutput(
			$sortedData,
			$document,
			'Organizations',
			[
				'Id'         => 'data.%d.id',
				'Name'       => 'data.%d.attributes.name',
				'Created at' => 'data.%d.attributes.created_at',
				'Updated at' => 'data.%d.attributes.updated_at',
			],
			new Table($output),
			end($sortedData)
		);
		$table->render();
	}
}