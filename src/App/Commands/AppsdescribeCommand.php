<?php
namespace Console\App\Commands;
 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Art4\JsonApiClient\Exception\InputException;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
 
class AppsdescribeCommand extends Command
{
    protected static $defaultName = 'apps:describe';

    protected function configure()
    {
        $this
            ->setDescription('gets the apps you specify')
            ->setHelp('try rebooting')
            ->addArgument('app_name', InputArgument::REQUIRED, 'which app would you like to describe?')
        ;
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $token = file_get_contents(getenv('HOME') . '/.config/lamp.io/token');

        $client = new \GuzzleHttp\Client();
        $headers = [
            'Authorization' => "Bearer $token",
            'Content-type'  => 'application/vnd.api+json',
            'Accept'        => 'application/vnd.api+json'
        ];
        $app_name = $input->getArgument('app_name');
        $response = $client->request('GET', "https://api.lamp.io/apps/$app_name", ['headers' => $headers]);

        try {
            $document = Parser::parseResponseString($response->getBody()->getContents());
        } catch (InputException $e) {
            $output->writeln($e->getMessage());
        } catch (ValidationException $e) {
            $output->writeln($e->getMessage());
        }

        $output->writeln('name:        ' . $document->get('data.id'));
        $output->writeln('description: ' . $document->get('data.attributes.description'));
        $output->writeln('status:      ' . $document->get('data.attributes.status'));
        $output->writeln('vcpu:        ' . $document->get('data.attributes.vcpu'));
        $output->writeln('memory:      ' . $document->get('data.attributes.memory'));
        $output->writeln('replicas:    ' . $document->get('data.attributes.replicas'));
    }
}

