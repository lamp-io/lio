<?php

namespace Lio\App\AbstractCommands;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Lio\App\Console\Command;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractDeleteCommand extends Command
{
	/**
	 * @var string
	 */
	protected $apiEndpoint = '';

	protected function configure()
	{
		parent::configure();
		$this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

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
		if (!$this->askConfirm('<info>Are you sure you want to delete ? (y/N)</info>', $output, $input)) {
			return 0;
		}
		try {
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				$this->getApiEndpoint(),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
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
	 * @param string $questionText
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return bool
	 */
	protected function askConfirm(string $questionText, OutputInterface $output, InputInterface $input): bool
	{
		if (!empty($input->getOption('yes'))) {
			return true;
		}
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion($questionText, false);
		return $helper->ask($input, $output, $question);
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
	 * @param ResponseInterface $response
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return null
	 */
	abstract protected function renderOutput(ResponseInterface $response, OutputInterface $output, InputInterface $input);
}