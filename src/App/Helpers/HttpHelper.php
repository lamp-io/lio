<?php

namespace Console\App\Helpers;

use GuzzleHttp\ClientInterface;

class HttpHelper
{
	/**
	 * @var ClientInterface|null
	 */
	protected $client = null;

	/**
	 * @var array
	 */
	protected $headers = [];

	/**
	 * HttpHelper constructor.
	 * @param ClientInterface $client
	 */
	public function __construct(ClientInterface $client)
	{
		$this->client = $client;
		$this->headers = $this->getDefaultHeaders();
	}

	/**
	 * @param string $headerKey
	 * @param string $headerValue
	 */
	public function setHeader(string $headerKey, string $headerValue)
	{
		$this->headers[$headerKey] = $headerValue;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * @return array
	 */
	public function getDefaultHeaders(): array
	{
		return [
			'Content-type' => 'application/vnd.api+json',
			'Accept'       => 'application/vnd.api+json',
		];
	}

	/**
	 * @return ClientInterface
	 */
	public function getClient(): ClientInterface
	{
		return $this->client;
	}

}