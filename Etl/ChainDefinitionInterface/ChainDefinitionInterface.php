<?php

namespace Oliverde8\PhpEtlBundle\Etl\ChainDefinitionInterface;



use Oliverde8\Component\PhpEtl\ChainConfig;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('etl.chain_definition')]
interface ChainDefinitionInterface
{
    public function getKey(): string;
    public function build(): ChainConfig;
}
