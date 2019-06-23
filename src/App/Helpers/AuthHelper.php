<?php

namespace Console\App\Helpers;

class AuthHelper
{
	const TOKEN_FILE_NAME = 'token';

	/**
	 * @param string $token
	 * @throws \RuntimeException
	 * @return bool|int
	 */
	public static function saveToken(string $token)
	{
		if (!file_exists(self::getPathToTokenFolder())) {
			mkdir(
				self::getPathToTokenFolder(),
				0744
			);
		}
		return file_put_contents(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME, $token);


	}

	/**
	 * @return string
	 */
	public static function getPathToTokenFolder(): string
	{
		return getenv('HOME') . getenv("HOMEDRIVE") . getenv("HOMEPATH") . '/.config/.lamp.io/';
	}

	/**
	 * @return bool
	 */
	public static function isTokenExist(): bool
	{
		return file_exists(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME) &&
			!empty(file_get_contents(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME));

	}

	/**
	 * @return string
	 */
	public static function getToken(): string
	{
		return (self::isTokenExist()) ? file_get_contents(self::getPathToTokenFolder() . self::TOKEN_FILE_NAME) : '';
	}
}