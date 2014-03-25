<?php

namespace Ibrows\TranslationHelperBundle\Tests;

use Ibrows\TranslationHelperBundle\Translation\DefaultCreator;
use Ibrows\TranslationHelperBundle\Translation\TranslatorWrapper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TranslationTest extends WebTestCase
{

    protected $container = null;

    public function testTranslatorWrapped()
    {
        $translator = $this->container->get('translator');

        $this->assertInstanceOf('Ibrows\TranslationHelperBundle\Translation\TranslatorWrapper', $translator);

    }

    public function testTranslatorNormal()
    {
        $translator = $this->container->get('translator');
        /* @var $translator TranslatorWrapper */
        $translator->setCreate(false);
        $translator->setNormalize(false);
        $translator->setDecorate('%s');
        $trans = $translator->trans('testtrans', array(), 'Test', 'fr');
        $this->assertEquals("testtrans", $trans);
    }

    public function testTranslatorDecorate()
    {
        $translator = $this->container->get('translator');
        /* @var $translator TranslatorWrapper */
        $translator->setCreate(false);
        $translator->setNormalize(false);
        $translator->setDecorate('!!!%s???');
        $trans = $translator->trans('testtrans', array(), 'Test', 'fr');
        $this->assertEquals("!!!testtrans???", $trans);
    }

    public function testTranslatorCreate()
    {
        $translator = $this->container->get('translator');
        /* @var $translator TranslatorWrapper */
        $translator->setCreate(true);
        $translator->setNormalize(true);

        $creator = $translator->getCreator();
        /* @var $creator DefaultCreator */
        $creator->setPath(__DIR__ . '/../Resources/translations');

        $translator->setDecorate('!!!%s');

        $trans = $translator->trans('TestTrans!', array(), 'Test', 'fr');
        $this->assertEquals("!!!TestTrans!", $trans);

        $trans = $translator->trans('TestTrans!', array(), 'Test', 'fr');
        $this->assertEquals("___test_trans!", $trans);

        $trans = $translator->trans('test_trans!', array(), 'Test', 'fr');
        $this->assertEquals("___test_trans!", $trans);

        $trans = $translator->trans('test_trans!', array(), 'Test', 'fr_CH');
        $this->assertEquals("___test_trans!", $trans);

        $trans = $translator->trans('TestTrans!', array(), 'Test', 'en_US');
        $this->assertEquals("!!!TestTrans!", $trans);

        $translator->setNormalize(false);

        $trans = $translator->trans('TestTrans!', array(), 'Test', 'fr');
        $this->assertEquals("!!!TestTrans!", $trans);

        $trans = $translator->trans('test_trans!', array(), 'Test', 'fr');
        $this->assertEquals("___test_trans!", $trans);

        $trans = $translator->trans('TestTrans2!', array(), 'Test', 'fr');
        $this->assertEquals("!!!TestTrans2!", $trans);

        $trans = $translator->trans('TestTrans2!', array(), 'Test', 'fr');
        $this->assertEquals("___TestTrans2!", $trans);

        $trans = $translator->trans('TestTrans2!', array(), 'Test', 'en_US');
        $this->assertEquals("!!!TestTrans2!", $trans);

        $creator->setDecorate("|||%s???");


        $trans = $translator->trans('test_trans!', array(), 'Test', 'fr');
        $this->assertEquals("___test_trans!", $trans);

        $trans = $translator->trans('TestTrans3!', array(), 'Test', 'fr');
        $this->assertEquals("!!!TestTrans3!", $trans);

        $trans = $translator->trans('TestTrans3!', array(), 'Test', 'fr');
        $this->assertEquals("|||TestTrans3!???", $trans);
    }

    protected function setUp()
    {
        parent::setUp();
        $client = static::createClient();
        $this->container = $client->getContainer();
        $this->resetFiles();

    }

    protected function tearDown()
    {
        $this->resetFiles();
    }

    protected function resetFiles()
    {
        $transpath = __DIR__ . DIRECTORY_SEPARATOR. '..'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'translations';
        @mkdir($transpath);
        $handle = opendir($transpath);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                unlink($transpath.DIRECTORY_SEPARATOR.$entry);
            }
        }
        closedir($handle);


    }

}
