<?php

namespace Lio\App\Commands\Autodeploys;

use Exception;
use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException as CliInvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutodeploysUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/autodeploys/%s';

	protected static $defaultName = 'autodeploys:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an autodeploy')
			->setHelp('Update a key, api reference' . PHP_EOL . 'https://www.lamp.io/api#/autodeploys/autoDeploysUpdate')
			->addArgument('autodeploy_id', InputArgument::REQUIRED, 'The ID of the autodeploy')
			->addOption('github_repository', 'g', InputOption::VALUE_REQUIRED, 'The repository this autodeploy uses')
			->addOption('create_app_on_pr', 'c', InputOption::VALUE_REQUIRED, 'Create lamp.io app when a PR is created')
			->addOption('delete_app_on_branch_delete', 'd', InputOption::VALUE_REQUIRED, 'Delete lamp.io app when branch is deleted')
			->setBoolOptions(['create_app_on_pr', 'delete_app_on_branch_delete']);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('autodeploy_id')
		));
		$this->setSkipAttributes(['updated_at']);
		return parent::execute($input, $output);
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $key => $val) {
			if (!empty($val)) {
				if ($key == 'delete_app_on_branch_delete' || $key == 'create_app_on_pr') {
					$attributes[$key] = $val == 'true';
				} else {
					$attributes[$key] = $val;
				}
			}
		}
		if (empty($attributes)) {
			throw new CliInvalidArgumentException('Command requires at least one option to be executed. List of allowed options');
		}
		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('autodeploy_id'),
				'type'       => 'autodeploys',
			],
		]);
	}

}
