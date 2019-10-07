<?php

namespace Lio\App\AbstractCommands;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractDeleteCommand extends AbstractCommand
{

	protected function configure()
	{
		parent::configure();
		$this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

	/**
	 * @param InputInterface $input
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(InputInterface $input): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'DELETE',
			$this->getApiEndpoint(),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
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
}