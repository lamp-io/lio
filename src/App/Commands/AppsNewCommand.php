<?php


namespace Console\App\Commands;


use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppsNewCommand extends Command
{
    const API_ENDPOINT = 'https://api.lamp.io/apps';

    protected static $defaultName = 'apps:new';

    protected function configure()
    {
        $this->setDescription('Creates a new app')
            ->setHelp('Allow you to create app, api reference https://www.lamp.io/api#/apps/appsCreate')
            ->addArgument('organization_id', InputArgument::REQUIRED, 'The ID(uuid) of the organization this app belongs to.')
            ->addArgument('description', InputArgument::OPTIONAL, 'A description', 'Default description')
            ->addArgument('httpd_conf', InputArgument::OPTIONAL, 'The apache httpd.conf, located at /etc/apache2/sites-enabled/httpd.conf', '# httpd.conf default')
            ->addArgument('max_replicas', InputArgument::OPTIONAL, 'The maximum number of auto-scaled replicas', '1')
            ->addArgument('memory', InputArgument::OPTIONAL, 'The amount of memory available (example: 1Gi)', '128Mi')
            ->addArgument('min_replicas', InputArgument::OPTIONAL, 'The minimum number of auto-scaled replicas', '1')
            ->addArgument('php_ini', InputArgument::OPTIONAL, 'The php.ini, located at /usr/local/etc/php/conf.d/php.ini', '; php.ini default')
            ->addArgument('replicas', InputArgument::OPTIONAL, 'The number current number replicas available. 0 stops app.', '1')
            ->addArgument('vcpu', InputArgument::OPTIONAL, 'The number of virtual cpu cores available (maximum: 4, minimum: 0.25)', '0.25');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try {
            $response = $this->httpHelper->getClient()->request(
                'POST',
                self::API_ENDPOINT,
                [
                    'headers' => $this->httpHelper->getHeaders(),
                    'body'    => $this->getRequestBody($input),
                ]
            );
        } catch (GuzzleException $guzzleException) {
            $output->writeln($guzzleException->getMessage());
            die();
        }

        try {
            /** @var Document $document */
            $document = Parser::parseResponseString($response->getBody()->getContents());
            $output->writeln('Your new app successfully created, id: ' . $document->get('data.id'));
        } catch (ValidationException $e) {
            $output->writeln($e->getMessage());
        }
    }

    protected function getRequestBody(InputInterface $input): string
    {
        return json_encode([
            'data' => [
                'attributes' =>
                    [
                        'description'     => $input->getArgument('description'),
                        'httpd_conf'      => $input->getArgument('httpd_conf'),
                        'max_replicas'    => $input->getArgument('max_replicas'),
                        'memory'          => $input->getArgument('memory'),
                        'min_replicas'    => $input->getArgument('min_replicas'),
                        'organization_id' => $input->getArgument('organization_id'),
                        'php_ini'         => $input->getArgument('php_ini'),
                        'replicas'        => $input->getArgument('replicas'),
                        'vcpu'            => $input->getArgument('vcpu'),
                    ],
                'type'       => 'apps',
            ],
        ]);
    }


}