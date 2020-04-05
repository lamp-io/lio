<?php

namespace Lio\App\Commands\Autodeploys;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysNewCommand extends AbstractNewCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys';

	protected static $defaultName = 'autodeploys:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create an autodeploy for an organization.')
			->addArgument('github_repository', InputArgument::REQUIRED, 'The repository this autodeploy uses')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'One organization_id. If omitted defaults to user\'s default organization')
			->addOption('create_app_on_pr', 'c', InputOption::VALUE_REQUIRED, 'Create lamp.io app when a PR is created', 'false')
			->addOption('delete_app_on_branch_delete', 'd', InputOption::VALUE_REQUIRED, 'Delete lamp.io app when branch is deleted', 'false')
			->setBoolOptions(['create_app_on_pr, delete_app_on_branch_delete'])
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
		$output->writeln('<info>Your new autodeploy successfully created, key id: ' . $document->get('data.id') . '</info>');
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$gitHubRepository = str_replace('https://github.com/', '', $input->getArgument('github_repository'));
		$attributes = [
			'organization_id'             => $input->getOption('organization_id') ?? '',
			'create_app_on_pr'            => $input->getOption('create_app_on_pr') === 'true' ? true : false,
			'delete_app_on_branch_delete' => $input->getOption('delete_app_on_branch_delete') === 'true' ? true : false,
			'github_repository'           => $gitHubRepository,
		];

		return json_encode([
			'data' => [
				'attributes' => array_filter($attributes, function ($attribute) {
					return !empty($attribute);
				}),
				'type'       => 'autodeploys',
			],
		], JSON_FORCE_OBJECT);
	}
}
