<?php
namespace ReportingBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddProvidersChainPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $chain = $container->getDefinition('reporting.provider.chain');
        $providers = $container->findTaggedServiceIds('reporting.provider');

        foreach ($providers as $id => $tag) {
            $chain->addMethodCall('add', array($id, new Reference($id)));
        }
    }
}