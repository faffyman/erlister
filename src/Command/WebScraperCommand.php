<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class WebScraperCommand extends Command
{
    /** @var Client  */
    private $client;

    /** @var SymfonyStyle */
    private $io;

    private $domainsOnly = false;

    protected static $defaultName = 'scrape';


    public function __construct(string $name=null)
    {
        $this->client = new Client();
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Scrapes a web page and *Lists* each *E*xternal *R*esource used by that web page')
            ->setHelp('Use this tool to scan a web page to find out all the external resources used by that page. 
useful for discovering domains that you may want to dns-prefetch or for approving CSP rules
results are output to screen grouped according to resource type.

Example:
 `erlist https://www.wikipedia.com --domains-only`

')
            ->addArgument('url', InputArgument::REQUIRED,'*FULL* URL you want to scan - you MUST include the protocol - i.e. http:// or https://')
            ->addOption('domains-only', 'd', InputOption::VALUE_NONE, 'Lists only the external domains according to usage type' )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = $io = new SymfonyStyle($input, $output);

        // get options
        if ($input->hasOption('domains-only') && $input->getOption('domains-only') ) {
            $this->domainsOnly = $domainsOnly = true;
        }
        // What URL is being scanned ?
        try {
            $this->url = $url = $input->getArgument('url');
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        // execute scan
        $starttime= microtime(true);
        $io->info( sprintf('START SCANNING %s', $url));
        $results = $this->scanUrl($this->url,$this->domainsOnly , $io);
        $endtime = microtime(true) - $starttime;
        $io->info( sprintf('SCAN DURATION %d seconds', $endtime));

        $this->outputResults($results, $io);

        return Command::SUCCESS;
    }


    /**
     * Find and print all
     * - <a href> links
     * - <link href>
     * - <img src>
     * - <script src>
     * - <iframe src>
     * - <object src>
     * - <video src>
     * - <video><source src>
     * - <audio src>
     * - <audio ><source src>
     *
     * @param $url
     * @param false $domainsOnly
     * @param SymfonyStyle $io
     */
    public function scanUrl($url, $domainsOnly = false,SymfonyStyle $io)
    {
        $crawler = $this->client->request('GET', $url);
        $domain = parse_url($url, PHP_URL_HOST);
        $io->warning('Exclude '. $domain);
        $results = [];

        // link hrefs - Favicons, stylesheets, License, external preconnects, etc
        $this->filterFor('Stylesheet Links','link', 'href', $crawler, $domain, $results);
        // a hrefs
        $this->filterFor('Clickable Links','a', 'href', $crawler, $domain, $results);
        // Images
        $this->filterFor('Images','img', 'src', $crawler, $domain, $results);
        $this->filterFor('Images - Video Posters','video', 'poster', $crawler, $domain, $results);
        // Scripts
        $this->filterFor('Scripts','script', 'src', $crawler, $domain, $results);
        // Scripts
        $this->filterFor('IFrames','iframe', 'src', $crawler, $domain, $results);
        // Object
        $this->filterFor('Object','object', 'src', $crawler, $domain, $results);
        // Videos
        $this->filterFor('Video','video', 'src', $crawler, $domain, $results);
        $this->filterFor('Video Alternate Sources','video source', 'src', $crawler, $domain, $results);
        // Audio
        $this->filterFor('Audio','audio', 'src', $crawler, $domain, $results);
        $this->filterFor('Audio Alternate Sources','audio > source', 'src', $crawler, $domain, $results);
        // ToDo
        // Examine CSS Files for any loaded media
        // Examine any JS injected media
        return $results;
    }


    public function filterFor(string $group, string $selector, string $attr = null, Crawler $crawler, string $domain = null, array &$results = [])
    {

        if(!array_key_exists($group, $results)) {
            $results[$group] = [];
        }

        $crawler->filter($selector)->each(function (Crawler $node) use ($group, $attr, $domain, &$results) {

            $value = $node->attr($attr);
            if(!stripos($value, $domain)) {
                if($this->domainsOnly) {
                    $value = parse_url($value, PHP_URL_HOST);
                }
                if(!in_array($value,$results[$group] )) {
                    array_push($results[$group], $value);
                }
            }
            array_filter($results[$group]);
            asort($results[$group]);

            return $results;
        });

        return $results;
    }

    public function outputResults($results, SymfonyStyle $io) {

        //ToDO - use IO->table instead?
        $table = new Table($io);
        $table->setHeaders(['TYPE', 'DOMAIN']);

        foreach ($results as $type => $list) {
            //$io->title($type);
            $items = 0;
            if(!empty($list)) {
                foreach ($list as $cntr => $item) {
                    if (null !== $item) {
                        $items++;
                        $table->addRow([$type, $item]);
                        //$io->writeln($item);
                    }
                    if( count($list) === ($cntr+1) &&  $items >=1) {
                        $table->addRow(new TableSeparator());
                    }
                }

            }
        }
        $table->setStyle('box');
        $table->render();





    }


}