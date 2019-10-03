<?php


namespace Lio\App\AbstractCommands;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Lio\App\Console\Command;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Exception\GuzzleException;

abstract class AbstractNewCommand extends Command
{
	/**
	 * @var string
	 */
	protected $apiEndpoint = '';

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				$this->getApiEndpoint(),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$this->renderOutput($response, $output);
			}
		} catch (BadResponseException $badResponseException) {
			$output->write(PHP_EOL);
			$output->writeln('<error>' . $badResponseException->getResponse()->getBody()->getContents() . '</error>');
			return 1;
		}
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
	 * @return mixed
	 */
	abstract protected function getRequestBody(InputInterface $input);

	/**
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @return null
	 */
	abstract protected function renderOutput(ResponseInterface $response, OutputInterface $output);
}