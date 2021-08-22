<?php
/**
 * Use a custom vendor Dir for compiling our phar
 * Why? because we don't need to bundle all of
 * the tools used for compilation of our app
 */
require_once __DIR__ . '/vendor/autoload.php';
use Symfony\Component\Finder\Finder;

class Compiler
{

    public function __construct($pharFile = 'erlister.phar')
    {
        $this->compile($pharFile);
    }

    public function compile($pharFile = 'erlister')
    {
        $applicationPath = __DIR__. '/..';
        $pharFilename = __DIR__. '/../dist/' . $pharFile;

        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFilename, 0, $pharFile);
        // SIGN our PHAR to prevent content tampering
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        $phar->startBuffering();

        $finder = new Finder();

        // We only want to bundle the root /vendor, /src and /bin directories  nothing else
        $finder->notPath('build')
            ->notPath('dist')
            ->notName('composer.*')
            ->in($applicationPath);

        $itemsFound = $finder->count();

        foreach ($finder as $item) {
            $iteration = isset($iteration) ? $iteration + 1 : 1;
            $percentageDone = sprintf('%.2f', (100 / $itemsFound) * $iteration);
            if (is_dir($item)) {
                $phar->addEmptyDir($item->getRelativePathname());
                echo '[' . $percentageDone . '% done] Added directory ' . $item->getRelativePathname() . "\n";
            } else {
                $phar->addFile($item->getRealPath(), $item->getRelativePathname());
                echo '[' . $percentageDone . '% done] Added file ' . $item->getRelativePathname() . "\n";
            }
        }

        // Register our console command
        $content = file_get_contents($applicationPath . '/bin/console');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/console scrape', $content);

        $stub = trim("
#!/usr/bin/env php
<?php
Phar::mapPhar('" . $pharFile . "');
require 'phar://" . $pharFile . "/bin/console';
__HALT_COMPILER();
");

        $phar->setStub($stub);
        $phar->stopBuffering();

        unset($phar);
    }

}

$compiler = new Compiler('erlister.phar');