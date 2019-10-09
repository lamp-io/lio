<?php


namespace Lio\App\Commands\Organizations;

use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Exception;
use Lio\App\Helpers\CommandsHelper;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationsUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/organizations/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an organization')
			->setHelp('Update an organization, api reference' . PHP_EOL . 'https://www.lamp.io/api#/organizations/organizationsUpdate')
			->addArgument('organization_id', InputArgument::REQUIRED, 'The ID of the organization')
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'New organization name')
			->addOption('promo_code', null, InputOption::VALUE_REQUIRED, 'Apply promo code')
			->addOption('stripe_source_id', null, InputOption::VALUE_REQUIRED, 'Stripe source id');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('organization_id')
		));
		parent::execute($input, $output);
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $optionValue) {
			if (!in_array($optionKey, CommandsHelper::DEFAULT_CLI_OPTIONS) && !empty($input->getOption($optionKey))) {
				$attributes[$optionKey] = $optionValue;
			}

		}
		if (empty($attributes)) {
			throw new InvalidArgumentException('CommandWrapper requires at least one option to be executed');
		}
		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('organization_id'),
				'type'       => 'organizations',
			],
		]);
	}
}