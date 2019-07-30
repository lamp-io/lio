<?php

namespace Console\App\Helpers;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PasswordHelper
{
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
			$number = preg_match('/[0-9]|[\W]+/', $value);
			$length = strlen($value) >= 8;
			if (is_null($value)) {
				$output->writeln('<error>Error: Refusing to set empty password</error>');
				exit(1);
			}
			if ((!$length || !$number)) {
				$exceptionMessage = [
					'* Must be a minimum of 8 characters',
					'* Must contain at least 1 number or 1 symbol',
				];
				throw new Exception(PHP_EOL . implode(PHP_EOL, $exceptionMessage));
			}

			return $value;
		});
		$question->setMaxAttempts(10);

		return $question;
	}
}