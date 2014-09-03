<?php

namespace Ibrows\TranslationHelperBundle\Twig;

use Ibrows\TranslationHelperBundle\Translation\TranslatorWrapper;


class TransExtension extends \Twig_Extension
{
    /**
     * @var TranslatorWrapper
     */
    protected $translator;

    /**
     * @param TranslatorWrapper $translator
     */
    public function __construct(TranslatorWrapper $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'getTranslations' => new \Twig_Function_Method($this, 'getTranslations'),
        );
    }

    public function getTranslations($id = null){
        return $this->translator->getTranslations($id);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ibrows_translation_tool';
    }
}
