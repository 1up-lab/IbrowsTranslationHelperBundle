<?php

namespace Ibrows\TranslationHelperBundle\Tests;

use Ibrows\TranslationHelperBundle\DependencyInjection\IbrowsTranslationHelperExtension;
use Ibrows\TranslationHelperBundle\Translation\TranslatorWrapper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\Translator;

class TranslationTest extends \PHPUnit_Framework_TestCase
{


    public function testTranslatorExtension()
    {

        $loader = new IbrowsTranslationHelperExtension();
        $container = new ContainerBuilder();
        $loader->load(array('ibrows_translation_helper' => array('translator' => array('normalize' => true))), $container);

        $loadedconfig = $container->getParameter('ibrows_translation_helper.translator');
        $this->assertArrayHasKey('normalize', $loadedconfig);
        $this->assertEquals($loadedconfig['normalize'], true);

    }

    public function testTranslator()
    {
        $translator = $this->getTranslationWrapper();
        /* @var $translator TranslatorWrapper */
        $translator->setCreate(false);
        $translator->setNormalize(false);
        $translator->setDecorate('%s');
        $trans = $translator->trans('testtrans', array(), 'Test', 'fr');
        $this->assertEquals("testtrans", $trans);
    }

    public function testTranslatorDecorate()
    {
        $translator = $this->getTranslationWrapper();
        /* @var $translator TranslatorWrapper */
        $translator->setCreate(false);
        $translator->setNormalize(false);
        $translator->setDecorate('!!!%s???');
        $trans = $translator->trans('testtrans', array(), 'Test', 'fr');
        $this->assertEquals("!!!testtrans???", $trans);
    }

    protected function mockDefaultCreator()
    {
        return $this->getMock('Ibrows\TranslationHelperBundle\Translation\DefaultCreator', null, array(), '', false);
    }

    protected function getTranslationWrapper()
    {
        $defaulttranslator = new Translator('de');

        return $translator = new TranslatorWrapper($this->mockDefaultCreator(), $defaulttranslator);
    }

}
