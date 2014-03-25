<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;


/**
 * Class DefaultCreator
 * @package Ibrows\TranslationHelperBundle\Translation
 */
class DefaultCreator implements CreatorInterface
{


    /**
     * @var \Symfony\Component\Translation\Writer\TranslationWriter
     */
    protected $writer;

    /**
     * @var string
     */
    protected $format;
    /**
     * @var boolean
     */
    protected $backup;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $decorate = "__%s";

    /**
     * @param \Symfony\Component\Translation\Writer\TranslationWriter $writer
     * @param string $format
     * @param string $path
     * @internal param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(TranslationWriter $writer, $format, $path)
    {
        $this->writer = $writer;
        $this->format = $format;
        $this->path = $path;
        $supportedFormats = $writer->getFormats();
        if (!in_array($format, $supportedFormats)) {
            throw new \Exception('Wrong format' . $format . '. Supported formats are ' . implode(', ', $supportedFormats));
        }
    }




    /**
     * @param string $id
     * @param string $domain
     * @param string $locale
     * @param MessageCatalogue $catalogue
     * @return string|void
     */
    public function createTranslation($id, $domain, $locale, MessageCatalogue $catalogue)
    {
        $value = sprintf($this->decorate, $id);
        $catalogue->set($id, $value, $domain);
        $messages = ($catalogue->all($domain));
        $cataloguetemp = new MessageCatalogue($locale, array($domain => $messages));
        $this->writer->writeTranslations($cataloguetemp, $this->format, array('path' => $this->path));
        if (!$this->backup) {
            $backupfullpath = $this->path . '/' . $domain . '.' . $locale . '.' . $this->format . '~';
            if (file_exists($backupfullpath)) {
                unlink($backupfullpath);
            }
        }
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $decorate
     */
    public function setDecorate($decorate)
    {
        $this->decorate = $decorate;
    }

    /**
     * @param boolean $backup
     */
    public function setBackup($backup)
    {
        $this->backup = $backup;
    }


}
