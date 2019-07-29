<?php

namespace Console\App\Helpers;

use Exception;
use Symfony\Component\Console\Question\Question;

class PasswordHelper
{
	/**
	 * @return Question
	 */
	public static function getPasswordQuestion(): Question
	{
		$question = new Question('<info>Please provide a password for the MySQL root user (leave blank for a randomly generated one)</info>', ' ');
		$question->setHiddenFallback(false);
		$question->setHidden(true);
		$question->setValidator(function ($value) {
			$number = preg_match('/[0-9]|[\W]+/', $value);
			$length = strlen($value) >= 8;
			if ((!$length || !$number) && $value !== ' ') {
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

	public static function generateRandomPassword(int $size): string
	{
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$%^&*(){}";
		$pass = [];
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < $size; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);
	}

}