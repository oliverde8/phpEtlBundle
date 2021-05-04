<?php


namespace Oliverde8\PhpEtlBundle\DependencyInjection\Compiler;


use Oliverde8\Component\RuleEngine\RuleApplier;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RuleEngineCompiler implements CompilerPassInterface
{
    public const TAG = "etl.rule";


    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $ruleApplierDefinition = $container->getDefinition(RuleApplier::class);

        $rules = $container->findTaggedServiceIds(self::TAG);
        $rulesReferences = [];

        foreach ($rules as $id => $parameters) {
            $rulesReferences[] = new Reference($id);
        }

        $ruleApplierDefinition->setArgument('$rules', $rulesReferences);
    }

}