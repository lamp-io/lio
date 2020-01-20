<?php


namespace Lio\App\Commands\Keys;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Exception;
use Lio\App\AbstractCommands\AbstractDescribeCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeysDescribeCommand extends AbstractDescribeCommand
{
	const API_ENDPOINT = 'https://api.lamp.io/keys/%s';

	protected static $defaultName = 'keys:describe';

	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns a key')
			->setHelp('Get selected key, api reference' . PHP_EOL . 'https://www.lamp.io/api#/keys/keysShow')
			->addArgument('key_id', InputArgument::REQUIRED, 'The id of the key');
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
			$input->getArgument('key_id')
		));
		$this->skipAttributes = ['updated_at'];
		return parent::execute($input, $output);
	}

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
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
}
