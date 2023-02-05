<?php


namespace Oliverde8\PhpEtlBundle\Factory;


use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainProcessor;

class ChainFactory
{
    /** @var ChainBuilder */
    protected $chainBuilder;

    /**
     * ChainFactory constructor.
     * @param ChainBuilder $chainBuilder
     */
    public function __construct(ChainBuilder $chainBuilder)
    {
        $this->chainBuilder = $chainBuilder;
    }

    public function create($config, array $inputOptions, int $maxAsynchronousItems): ChainProcessor
    {
        return $this->chainBuilder->buildChainProcessor($config, $inputOptions, $maxAsynchronousItems);
    }
}
