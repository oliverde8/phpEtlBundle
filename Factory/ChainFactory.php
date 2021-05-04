<?php


namespace Oliverde8\PhpEtlBundle\Factory;


use Oliverde8\Component\PhpEtl\ChainBuilder;

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

    public function __invoke($config)
    {
        return $this->chainBuilder->buildChainProcessor($config);
    }
}
