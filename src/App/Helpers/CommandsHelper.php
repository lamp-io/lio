<?php

namespace Lio\App\Helpers;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CommandsHelper
{
	const DEFAULT_CLI_OPTIONS = [
		'help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'json',
	];

	/**
	 * @param array $boolOptions
	 * @param array $option
	 */
	public static function validateBoolOptions(array $boolOptions, array $option)
	{
		foreach ($boolOptions as $boolOption) {
			if (!empty($option[$boolOption]) && !in_array($option[$boolOption], ['true', 'false'])) {
				throw new InvalidArgumentException(
					'Value for options: ' . PHP_EOL . implode(', ', $boolOptions) . PHP_EOL . 'Must be true or false');
			}
		}
	}

	/**
	 * @param string $message
	 * @param OutputInterface $output
	 * @return ProgressBar
	 */
	public static function getProgressBar(string $message, OutputInterface $output): ProgressBar
	{
		ProgressBar::setFormatDefinition('custom', $message . '%bar%');
		$progressBar = new ProgressBar($output);
		$progressBar->setFormat('custom');
		$progressBar->setProgressCharacter('.');
		$progressBar->setEmptyBarCharacter(' ');
		$progressBar->setBarCharacter('.');
		$progressBar->setBarWidth(30);

		return $progressBar;
	}

	/**
	 * @param string $questionText
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @param QuestionHelper $questionHelper
	 * @return bool
	 */
	public static function askConfirm(string $questionText, OutputInterface $output, InputInterface $input, QuestionHelper $questionHelper): bool
	{
		if (!empty($input->getOption('yes'))) {
			return true;
		}
		$question = new ConfirmationQuestion($questionText, false);
		return $questionHelper->ask($input, $output, $question);
	}

	/**
	 * @param string $questionOutput
	 * @param mixed $default
	 * @param OutputInterface $output
	 * @return Question
	 */
	public static function getPasswordQuestion(string $questionOutput, $default, OutputInterface $output): Question
	{
		$question = new Question($questionOutput, $default);
		$question->setHiddenFallback(false);
		$question->setHidden(true);
		$question->setValidator(function ($value) use($output) {
			if (is_null($value)) {
				$output->writeln('<error>Error: Refusing to set empty password</error>');
				exit(1);
			}
			return $value;
		});
		$question->setMaxAttempts(10);

		return $question;
	}

	/**
	 * @param array $data
	 * @param string $fieldName
	 * @return array
	 */
	public static function sortData(array $data, string $fieldName): array
	{
		uasort($data, function ($a, $b) use ($fieldName) {
			if (!isset($a['attributes'][$fieldName]) || !isset($b['attributes'][$fieldName])) {
				return $a;
			} else {
				return $a['attributes'][$fieldName] <=> $b['attributes'][$fieldName];
			}
		});

		return $data;
	}
}