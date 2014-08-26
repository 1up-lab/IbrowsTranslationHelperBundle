<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\CssSelector\Parser\Parser;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Yaml\Yaml;


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
     * @var string
     */
    protected $defaultYML;

    protected $defaultYMLFilename = "default";


    /**
     * @param \Symfony\Component\Translation\Writer\TranslationWriter $writer
     * @param string $format
     * @param string $path
     * @internal param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(TranslationWriter $writer, $format, $path, $defaultYML)
    {
        $this->writer = $writer;
        $this->format = $format;
        $this->path = $path;
        $this->defaultYML = $defaultYML;
        if (!$this->supportFormat($format)) {
            throw new \Exception('Wrong format' . $format . '. Supported formats are ' . implode(', ', $supportedFormats));
        }
    }


    /**
     * @param $format
     * @return bool
     */
    protected  function supportFormat($format){
        $supportedFormats = $this->writer->getFormats();
        return in_array($format, $supportedFormats);
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
        $this->setNewId($id,$domain,$catalogue);
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
     * Set the new id into the MessageCatalogue
     * @param $id
     * @param $domain
     * @param MessageCatalogue $catalogue
     */
    protected function setNewId($id, $domain, MessageCatalogue $catalogue) {

        $value = $this->checkForDefaultValue($id, $catalogue->getLocale() );
        $id = str_replace(" ", "_", $id);
        if(!$value){
            $value = $this->decorate($id);
        }

        //$t = $catalogue->get($id);
        $catalogue->set($id, $value, $domain);
    }

    protected function checkForDefaultValue($key ,$locale){
        $file = $this->createFilename($locale);
        $value = Yaml::parse($file);
        if(!is_array($value)){
            return null;
        }
        $normalized = $this->normalizeData($value);

        if(isset($normalized[$key])){
            return $normalized[$key];
        }

        $key = $this->seperateKeyFromPath($key);
        $normalized = $this->normalizeDataWithKey($value);
        if(isset($normalized[$key])){
            return $normalized[$key];
        }

        return null;
    }

    protected function normalizeDataWithKey(array $data, &$result=array()){
        foreach($data as $key => $value){
            if(is_array($value)){
                $this->normalizeDataWithKey($value, $result);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function normalizeData(array $data, &$result=array(), $path=""){
        foreach($data as $key => $value){
            $_path = $path;
            if($_path != ""){
                $_path.=".";
            }
            $_path.="$key";

            if(is_array($value)){

                $this->normalizeData($value, $result, $_path);
            } else {
                $result[$_path] = $value;
            }
        }
        return $result;
    }

    protected function createFilename($locale){
        return $this->defaultYML."/".$this->defaultYMLFilename.".".$locale.".yml";
    }

    protected function seperateKeyFromPath($id){
        $parts = explode(".", $id);
        return $parts[count($parts)-1];
    }

    /**
     * @param $id
     * @return string
     */
    protected function decorate($id){
        $id = ucfirst($id);
        return sprintf($this->decorate, $id);
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
