<?php
/**
 * Created by iBROWS AG.
 * User: marcsteiner
 * Date: 27.02.14
 * Time: 17:12
 */

namespace Ibrows\TranslationHelperBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder)
    {
        $id = ($containerBuilder->getAlias('translator'));

        $wrapper = $containerBuilder->getDefinition('ibrows_translation_helper.wrapper');
        $wrapper->addArgument(new Reference($id));
        $configs = $containerBuilder->getParameter('ibrows_translation_helper.translator');
        $creatorKey = null;
        foreach ($configs as $key => $value) {
            if ($key == 'creator') {
                $creatorKey = $value;
                $value = new Reference($value);
            }
            $wrapper->addMethodCall('set' . $key, array($value));
        }


        $containerBuilder->setAlias('translator', 'ibrows_translation_helper.wrapper');


        $creator = $containerBuilder->getDefinition($creatorKey);
        $configs = $containerBuilder->getParameter('ibrows_translation_helper.defaultCreator');
        $rfClass = new \ReflectionClass($creator->getClass());
        foreach ($configs as $key => $value) {
            if ($rfClass->hasMethod('set' . $key)) {
                $creator->addMethodCall('set' . $key, array($value));
            }

        }

    }
}
