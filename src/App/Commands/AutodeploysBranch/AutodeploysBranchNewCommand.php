<?php

namespace Lio\App\Commands\AutodeploysBranch;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractNewCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysBranchNewCommand extends AbstractNewCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys_branch';

	protected static $defaultName = 'autodeploysBranch:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create an autodeployBranch for an organization')
			->setHelp('Create an autodeployBranch, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app this branch deploys to')
			->addArgument('autodeploy_id', InputArgument::REQUIRED, 'The ID of an autodeploy')
			->addArgument('branch', InputArgument::REQUIRED, 'Branch name')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'One organization_id. If omitted defaults to user\'s default organization')
			->setApiEndpoint(self::API_ENDPOINT);
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [
			'app_id'          => $input->getArgument('app_id'),
			'autodeploy_id'   => $input->getArgument('autodeploy_id'),
			'branch'          => $input->getArgument('branch'),
			'organization_id' => $input->getOption('organization_id'),
		];

		return json_encode([
			'data' => [
				'attributes' => array_filter($attributes, function ($attribute) {
					return !empty($attribute);
				}),
				'type'       => 'autodeploys_branch',
			],
		], JSON_FORCE_OBJECT);
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
		$output->writeln('<info>Your new autodeployBranch successfully created, id: ' . $document->get('data.id') . '</info>');
	}
}
