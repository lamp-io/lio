<?php

namespace Lio\App\Helpers;

use Symfony\Component\Console\Exception\RuntimeException;

class AuthHelper
{
	const TOKEN_FILE_NAME = 'token';

	const TOKEN_ENV_VAR = 'LAMP_IO_TOKEN';

	/**
	 * @param string $token
	 * @return bool|int
	 * @throws RuntimeException
	 */
	public static function saveToken(string $token)
	{
		if (!file_exists(self::getPathToTokenFolder())) {
			mkdir(
				self::getPathToTokenFolder(),
				0744,
				true
			);
		}
		return file_put_contents(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME, $token);


	}

	/**
	 * @return string
	 */
	public static function getPathToTokenFolder(): string
	{
		return getenv('HOME') . getenv("HOMEDRIVE") . getenv("HOMEPATH") . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'lamp.io' . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	protected static function getTokenFromFile(): string
	{
		return file_exists(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME) ? file_get_contents(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME) : '';
	}

	/**
	 * @return string
	 */
	protected static function getTokenFromEnv(): string
	{
		return !empty(getenv(self::TOKEN_ENV_VAR)) ? getenv(self::TOKEN_ENV_VAR) : '';
	}

	/**
	 * @return string
	 */
	public static function getToken(): string
	{
		return !empty(self::getTokenFromEnv()) ? self::getTokenFromEnv() : self::getTokenFromFile();
	}
}