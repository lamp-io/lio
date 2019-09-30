<?php


namespace Console\App\Commands\Organizations;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationsUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/organizations/%s';

	const OPTIONS_TO_PARAM = [
		'payment' => 'stripe_source_id',
	];

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
			->addOption('payment', 'p', InputOption::VALUE_REQUIRED, 'Stripe source id');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		$progressBar = self::getProgressBar(
			'Updating an organization ' . $input->getArgument('organization_id'),
			(empty($input->getOption('json'))) ? $output : new NullOutput()
		);
		try {
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('organization_id')
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody($input),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->write(PHP_EOL);
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $optionValue) {
			if (!in_array($optionKey, self::DEFAULT_CLI_OPTIONS) && !empty($input->getOption($optionKey))) {
				$key = isset(self::OPTIONS_TO_PARAM[$optionKey]) ? self::OPTIONS_TO_PARAM[$optionKey] : $optionKey;
				$attributes[$key] = $optionValue;
			}

		}
		if (empty($attributes)) {
			throw new InvalidArgumentException('Command requires at least one option to be executed');
		}
		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('organization_id'),
				'type'       => 'organizations',
			],
		]);
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Organization');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'Attributes',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$attributes = [];
		foreach ($serializedDocument['data']['attributes'] as $attributeKey => $attribute) {
			array_push($attributes, $attributeKey . ' : ' . $attribute);
		}
		$table->addRow([
			$serializedDocument['data']['id'],
			implode(PHP_EOL, $attributes),
		]);

		return $table;
	}
}