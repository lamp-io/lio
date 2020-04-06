<?php

namespace Lio\App\Commands\Autodeploys;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Exception;
use Lio\App\AbstractCommands\AbstractListCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysListCommand extends AbstractListCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];

	protected static $defaultName = 'autodeploys:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return autodeploys')
			->setHelp('Get list all allowed autodeploys, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys/autoDeploysList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization');

	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(
			sprintf(
				self::API_ENDPOINT,
				$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
			)
		);
		return parent::execute($input, $output);
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
		$table = $this->getTableOutput(
			$serializedDocument['data'],
			$document,
			'Autodeploys',
			[
				'Id'                            => 'data.%d.id',
				'GithubRepo'                    => 'data.%d.attributes.github_repository',
				'Organization id'               => 'data.%d.attributes.organization_id',
				'Create app on a PR'            => 'data.%d.attributes.create_app_on_pr',
				'Delete app on a branch delete' => 'data.%d.attributes.delete_app_on_branch_delete',
			],
			new Table($output),
			[],
			50,
			true
		);
		$table->render();
	}

}
