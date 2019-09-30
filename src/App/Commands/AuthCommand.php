<?php

namespace Lio\App\Commands;

use Lio\App\Helpers\AuthHelper;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AuthCommand extends Command
{
	const TOKEN_LENGTH = '52';

	const TOKEN_ATTEMPTS = '3';

	protected static $defaultName = 'auth';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Set auth token')
			->setHelp('Get your token at https://www.lamp.io/ on settings page')
			->addOption('update_token', 'u', InputOption::VALUE_NONE, 'Update existing token')
			->addOption('token', 't', InputOption::VALUE_REQUIRED, 'Set/Update auth token, in noninteractive mode');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!empty($input->getOption('token'))) {
			$token = $input->getOption('token');
		} else {
			/** @var QuestionHelper $questionHelper */
			$questionHelper = $this->getHelper('question');
			if (AuthHelper::isTokenExist() && empty($input->getOption('update_token'))) {
				$output->writeln(' <info>Token already exist, if you want to update it, please add [-u][--update_token] option </info>');
				return 1;
			} else {
				$question = new Question('Tokens can be generated at https://www.lamp.io/tokens' . PHP_EOL . PHP_EOL . 'Enter token:' . PHP_EOL, '');
				$question->setValidator(function ($answer) use ($input) {
					if (!empty($input->getOption('no-interaction'))) {
						throw new RuntimeException(
							'[--update_token][-u] Works only on interaction mode, you can set token directly using [--token][-t]'
						);
					}
					if (empty($answer) || strlen($answer) < self::TOKEN_LENGTH) {
						throw new RuntimeException(
							'Invalid Token. Get your token at https://www.lamp.io/ on settings page'
						);
					}
					return $answer;
				});
				$question->setMaxAttempts(self::TOKEN_ATTEMPTS);
				$token = $questionHelper->ask($input, $output, $question);
			}
		}
		AuthHelper::saveToken($token);
		$output->writeln('<info>Token saved</info>');
	}
}
