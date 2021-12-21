<?php

namespace Oliverde8\PhpEtlBundle;

use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\ChainBuilderCompiler;
use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\ChainCompiler;
use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\ChainParameterCompiler;
use Oliverde8\PhpEtlBundle\DependencyInjection\Compiler\RuleEngineCompiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Oliverde8PhpEtlBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ChainBuilderCompiler());
        $container->addCompilerPass(new RuleEngineCompiler());
        $container->addCompilerPass(new ChainParameterCompiler());
        $container->addCompilerPass(new ChainCompiler());
    }

}
