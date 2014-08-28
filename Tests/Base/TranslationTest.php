<?php

namespace Ibrows\TranslationHelperBundle\Tests;

use Ibrows\TranslationHelperBundle\DependencyInjection\IbrowsTranslationHelperExtension;
use Ibrows\TranslationHelperBundle\Translation\DefaultCreator;
use Ibrows\TranslationHelperBundle\Translation\TranslatorWrapper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Tests\Dumper\YamlDumperTest;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Yaml\Yaml;

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

    public function testCreator(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator(false);

        $defaultCreator->setDecorate("!!!%s");
        $defaultCreator->createTranslation('test','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('test',$data);
        $this->assertEquals('!!!test',$data['test']);
    }

    public function testSimpleDefaultFallback(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->createTranslation('simple','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('simple',$data);
        $this->assertEquals('simple',$data['simple']);
    }

    public function testSimpleLocaleDefaultFallback(){
        $messageCatalogue = new MessageCatalogue('de_CH');
        $messageCatalogue->addFallbackCatalogue(new MessageCatalogue('de'));
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->createTranslation('simple','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('simple',$data);
        $this->assertEquals('simple',$data['simple']);
    }

    public function testDotDefaultFallback(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->createTranslation('hello.simple','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('hello.simple',$data);
        $this->assertEquals('simple',$data['hello.simple']);
    }

    public function testDotTestDefaultFallback(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->createTranslation('dottest.test','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('dottest.test',$data);
        $this->assertEquals('hello',$data['dottest.test']);
    }

    public function testDotNotDefaultFallback(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->createTranslation('abc.first','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('abc.first',$data);
        $this->assertEquals('first',$data['abc.first']);
    }

    public function testDefaultYmlDirs(){
        $messageCatalogue = new MessageCatalogue('de');
        $defaultCreator = $this->setUpCreator();

        $defaultCreator->setDefaultYmlDirs(array(__DIR__ . DIRECTORY_SEPARATOR . 'default.de.yml',__DIR__ . DIRECTORY_SEPARATOR . 'default'));
        $defaultCreator->createTranslation('abc.first','messages','de',$messageCatalogue);

        $this->assertFileExists($this->getMessagesFilePath());
        $data = Yaml::parse($this->getMessagesFilePath());
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('abc.first',$data);
        $this->assertEquals('firstdefault',$data['abc.first']);
    }


    protected function setUpCreator($setDefaultYmlDirs=true){
        $translationWriter = new TranslationWriter();
        $dumper = new YamlFileDumper();
        $translationWriter->addDumper('yml',$dumper);
        $defaultCreator = new DefaultCreator($translationWriter,'yml',$this->getTranslationFolder());
        if($setDefaultYmlDirs){
            $defaultCreator->setDefaultYmlDirs(array(__DIR__ . DIRECTORY_SEPARATOR . 'default.de.yml'));
        }
        return $defaultCreator;
    }

    protected function getTranslationFolder(){
        return __DIR__ . DIRECTORY_SEPARATOR . 'translations';
    }

    protected function getMessagesFilePath(){
        return $this->getTranslationFolder(). DIRECTORY_SEPARATOR . 'messages.de.yml';
    }

    protected function resetTranslationFiles(){
        $dir =$this->getTranslationFolder();
        if(!file_exists($dir)){
            mkdir($dir);
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
             unlink("$dir/$file");
        }
    }

    protected function setUp()
    {
        parent::setUp();
        $this->resetTranslationFiles();
    }

    protected function tearDown()
    {
        $this->resetTranslationFiles();
    }

    protected function mockWriter()
    {
        return $this->getMock('Symfony\Component\Translation\Writer\TranslationWriter', null, array(), '', false);
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
