<?php

namespace Ibrows\TranslationHelperBundle\Translation;


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
     * @var array
     */
    protected $defaultYmlDirs = null;


    /**
     * @var bool
     */
    protected $ucFirst = false;

    /**
     * @var array
     */
    protected $fileDefaultValueData = array();

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
        if (!$this->supportFormat($format)) {
            throw new \Exception('Wrong format' . $format . '. Supported formats are ' . implode(', ', $this->writer->getFormats()));
        }
    }

    /**
     * @param boolean $ucFirst
     */
    public function setUcFirst($ucFirst)
    {
        $this->ucFirst = $ucFirst;
    }

    /**
     * @param array $defaultYmlDirs
     */
    public function setDefaultYmlDirs(array $defaultYmlDirs)
    {
        $this->defaultYmlDirs = $defaultYmlDirs;
    }



    /**
     * @param string $id
     * @param string $domain
     * @param string $locale
     * @param MessageCatalogue $catalogue
     * @return void
     */
    public function createTranslation($id, $domain, $locale, MessageCatalogue $catalogue)
    {
        $this->setNewId($id, $domain, $catalogue);
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

    /**
     * @param $format
     * @return bool
     */
    protected function supportFormat($format)
    {
        $supportedFormats = $this->writer->getFormats();

        return in_array($format, $supportedFormats);
    }

    /**
     * Set the new id into the MessageCatalogue
     * @param $id
     * @param $domain
     * @param MessageCatalogue $catalogue
     */
    protected function setNewId($id, $domain, MessageCatalogue $catalogue)
    {
        $value = $this->checkForDefaultValue($id, $catalogue->getLocale());
        if (!$value && $catalogue->getFallbackCatalogue()) {
            $value = $this->checkForDefaultValue($id, $catalogue->getFallbackCatalogue()->getLocale());
        }
        if (!$value) {
            $value = $this->decorate($id);
        }
        $catalogue->set($id, $value, $domain);
    }

    /**
     * get normalized array of a translation yml file
     * @param $filename
     * @param bool $force
     * @return array|null
     */
    protected function getFileDefaultValueData($filename, $force = false)
    {
        if (array_key_exists($filename, $this->fileDefaultValueData) && !$force) {
            return $this->fileDefaultValueData[$filename];
        }
        $this->fileDefaultValueData[$filename] = null;
        $value = Yaml::parse($filename);
        if (!is_array($value)) {
            return null;
        }
        $normalized = $this->normalizeData($value);
        $this->fileDefaultValueData[$filename] = $normalized;

        return $normalized;
    }

    /**
     * get a default value or null if no one found
     * @param $key
     * @param $locale
     * @return null|string
     */
    protected function checkForDefaultValue($key, $locale)
    {
        $files = $this->createFilenames($locale);
        if (!$files) {
            return null;
        }
        foreach($files as $file){
            $normalized = $this->getFileDefaultValueData($file);
            if (isset($normalized[$key])) {
                return $normalized[$key];
            }
        }
        foreach($files as $file){
            $normalized = $this->getFileDefaultValueData($file);
            $key = $this->seperateKeyFromPath($key);
            if (isset($normalized[$key])) {
                return $normalized[$key];
            }
        }


        return null;
    }

    /**
     * creates a array prekey.key => value
     * @param array $data
     * @param array $result
     * @param string $path
     * @return array
     */
    protected function normalizeData(array $data, &$result = array(), $path = "")
    {
        foreach ($data as $key => $value) {
            $_path = $path;
            if ($_path != "") {
                $_path .= ".";
            }
            $_path .= "$key";

            if (is_array($value)) {

                $this->normalizeData($value, $result, $_path);
            } else {
                $result[$_path] = $value;
            }
        }

        return $result;
    }

    protected static function getFiles($dir) {
        $files = array();
        $internalFiles = array_diff(scandir($dir), array('.','..'));
        foreach ($internalFiles as $file) {
            $filePath = $dir.DIRECTORY_SEPARATOR. $file;
            if(is_dir($filePath)){
                $files += static::getFiles($filePath);
            }else{
                $files[] = $filePath;
            }
        }
        return $files;
    }

    /**
     * @param $locale
     * @return null|array
     */
    protected function createFilenames($locale)
    {
        if (!$this->defaultYmlDirs) {
            return null;
        }
        $files = array();
        foreach($this->defaultYmlDirs as $dir){
            if(is_dir($dir)){
                $files = array_merge($files,static::getFiles($dir));
            }else{
                $files[] = $dir;
            }
        }
        foreach($files as $key => $file){
            if(strpos($file,"$locale.yml") !== strlen($file)-strlen("$locale.yml")){

                unset($files[$key]);
            }
        }
        return $files;
    }

    protected function checkLocale($file){

    }
    /**
     * @param $id string with dots
     * @return string
     */
    protected function seperateKeyFromPath($id)
    {
        $parts = explode(".", $id);

        return $parts[count($parts) - 1];
    }

    /**
     * @param $id
     * @return string
     */
    protected function decorate($id)
    {
        if($this->ucFirst){
            $id = ucfirst($id);
        }

        return sprintf($this->decorate, $id);
    }


}
