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
use Art4\JsonApiClient\Serializer\ArraySerializer;
 
class AppslistCommand extends Command
{
    protected static $defaultName = 'apps:list';

    protected function configure()
    {
        $this
            ->setDescription('gets the set of apps from the org associated with your token')
            ->setHelp('try rebooting')
        ;
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $token = file_get_contents('/root/.config/lamp.io/token');

        $client = new \GuzzleHttp\Client();
        $headers = [
            'Authorization' => "Bearer $token",
            'Content-type'  => 'application/vnd.api+json',
            'Accept'        => 'application/vnd.api+json'
        ];
        $response = $client->request('GET', "https://api.lamp.io/apps", ['headers' => $headers]);

        try {
            $document = Parser::parseResponseString($response->getBody()->getContents());
        } catch (InputException $e) {
            $output->writeln($e->getMessage());
        } catch (ValidationException $e) {
            $output->writeln($e->getMessage());
        }

        $serializer = new ArraySerializer(['recursive' => true]);
        $apps = $serializer->serialize($document);
        $output->writeln("app name\tdescription"); 
        foreach($apps['data'] as $app){
            $output->writeln($app['id'] . "\t" . $app['attributes']['description']);
        }

    }
}
