<?php

namespace Lio\App\AbstractCommands;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Lio\App\Console\CommandWrapper;
use Lio\App\Helpers\CommandsHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends CommandWrapper
{
	/**
	 * @var string
	 */
	protected $apiEndpoint = '';

	/**
	 * @var array
	 */
	protected $boolOptions = [];

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		CommandsHelper::validateBoolOptions($this->getBoolOptions(), $input->getOptions());
		try {
			$response = $this->sendRequest($input);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$this->renderOutput($response, $output, $input);
			}
		} catch (BadResponseException $badResponseException) {
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
	}

	/**
	 * @return array
	 */
	public function getBoolOptions(): array
	{
		return $this->boolOptions;
	}

	/**
	 * @param array $boolOptions
	 * @return $this
	 */
	protected function setBoolOptions(array $boolOptions)
	{
		$this->boolOptions = array_merge($this->boolOptions, $boolOptions);
		return $this;
	}

	/**
	 * @param string $apiEndpoint
	 */
	public function setApiEndpoint(string $apiEndpoint): void
	{
		$this->apiEndpoint = $apiEndpoint;
	}

	/**
	 * @return string
	 */
	public function getApiEndpoint(): string
	{
		return $this->apiEndpoint;
	}

	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 */
	abstract protected function sendRequest(InputInterface $input): ResponseInterface;

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return null
	 */
	abstract protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input);
}