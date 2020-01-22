<?php


namespace Lio\App\Commands\Keys;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Lio\App\AbstractCommands\AbstractUpdateCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeysUpdateCommand extends AbstractUpdateCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/keys/%s';

	protected static $defaultName = 'keys:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update a key')
			->setHelp('Update a key, api reference' . PHP_EOL . 'https://www.lamp.io/api#/keys/keysUpdate')
			->addArgument('key_id', InputArgument::REQUIRED, 'The ID of the key')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'An immutable description for this key');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->setApiEndpoint(sprintf(
			self::API_ENDPOINT,
			$input->getArgument('key_id')
		));
		$this->setSkipAttributes(['updated_at']);
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
		$table = $this->getTableOutput(
			$document,
			$document->get('data.id'),
			new Table($output),
			$this->getSkipAttributes(),
			50,
			true
		);
		$table->render();
	}

	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (empty($input->getOption('description'))) {
			throw new InvalidArgumentException('Command requires at least one option to be executed.');
		}
		return json_encode([
			'data' => [
				'attributes' => [
					'description' => $input->getOption('description'),
				],
				'id'         => $input->getArgument('key_id'),
				'type'       => 'keys',
			],
		]);
	}

}
