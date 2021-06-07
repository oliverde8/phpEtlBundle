<?php


namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ChainParameterCompiler implements CompilerPassInterface
{
    /** @TODO make this a parameter. */
    protected $paths = ["../config/etl/", "config/etl/", __DIR__ . "/../../Resources/config/etl"];

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $definitionsArray = [];
        $definitionsString = [];
        $etlFiles = [];

        $finder = new Finder();

        foreach ($this->paths as $path) {
            if (file_exists($path)) {
                $etlFiles = array_merge($etlFiles, iterator_to_array($finder->in([$path])->name("*.yml")));
            }
        }

        foreach ($etlFiles as $filename => $etlFile) {
            // Register file so that when it's modified in dev mode symfony empty caches automatially.
            $container->fileExists($etlFile);

            $etlName = str_replace(".yml", "", $etlFile->getBasename());
            $ymlContent = file_get_contents($etlFile);

            $definitionsArray[$etlName] = Yaml::parse($ymlContent)['chain'];
            $definitionsString[$etlName] = $ymlContent;
        }

        $container->setParameter("oliverde8-php-etl_chain", $definitionsArray);
        $container->setParameter("oliverde8-php-etl_chain__string", $definitionsString);
    }
}