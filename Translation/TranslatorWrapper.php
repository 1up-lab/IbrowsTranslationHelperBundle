<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class TranslatorWrapper
 * @package Ibrows\TranslationHelperBundle\Translation
 */
class TranslatorWrapper implements TranslatorInterface
{

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var CreatorInterface
     */
    protected $creator;

    /**
     * @var bool
     */
    protected $normalize = true;
    /**
     * @var bool
     */
    protected $create = true;
    /**
     * @var bool
     */
    protected $deleteCache = true;
    /**
     * @var string
     */
    protected $decorate = "!%s!";
    /**
     * @var array
     */
    protected $ignoreDomains = array();

    /**
     * @param CreatorInterface $creator
     * @param TranslatorInterface $translator
     */
    public function __construct(CreatorInterface $creator, TranslatorInterface $translator)
    {
        $this->creator = $creator;
        $this->translator = $translator;
    }

    /**
     * @param string $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {

        $id = (string)$id;
        $result = $this->translator->trans($id, $parameters, $domain, $locale);

        if (null === $locale) {
            $locale = $this->translator->getLocale();
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (in_array($domain, $this->ignoreDomains)) {
            return $result;
        }

        $catalogue = $this->getCatalogue($locale);
        if ($catalogue->has($id, $domain)) {
            return $result;
        }

        if ($this->normalize) {
            $id = $this->normalize($id);
            if ($catalogue->has($id, $domain)) {
                return $this->translator->trans($id, $parameters, $domain, $locale);
            }
        }

        if ($this->create) {
            $this->creator->createTranslation($id, $domain, $locale, $catalogue);
            if ($this->deleteCache) {
                $this->removeLocalesCacheFiles(array($locale));
            }
        }


        return sprintf($this->decorate, $result);

    }

    /**
     * @param string $id
     * @param int $number
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * @param string $locale
     * @return mixed
     */
    public function setLocale($locale)
    {
        return $this->translator->setLocale($locale);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * @param $id
     * @param $domain
     * @param $locale
     * @return mixed
     */
    public function isInCatalogue($id, $domain, $locale)
    {

        $catalogue = $this->getCatalogues($locale);

        return ($catalogue->has((string)$id, $domain));

    }

    /**
     * @param boolean $create
     */
    public function setCreate($create)
    {
        $this->create = $create;
    }

    /**
     * @return \Ibrows\TranslationHelperBundle\Translation\CreatorInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param \Ibrows\TranslationHelperBundle\Translation\CreatorInterface $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @param string $decorate
     */
    public function setDecorate($decorate)
    {
        $this->decorate = $decorate;
    }

    /**
     * @return boolean
     */
    public function getDeleteCache()
    {
        return $this->deleteCache;
    }

    /**
     * @param boolean $deleteCache
     */
    public function setDeleteCache($deleteCache)
    {
        $this->deleteCache = $deleteCache;
    }

    /**
     * @param array $ignoreDomains
     */
    public function setIgnoreDomains(array $ignoreDomains)
    {
        $this->ignoreDomains = $ignoreDomains;
    }

    /**
     * @param boolean $normalize
     */
    public function setNormalize($normalize)
    {
        $this->normalize = $normalize;
    }

    /**
     * @return \Symfony\Component\Translation\TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    public function __call($method, $args)
    {
        if (is_callable(array($this->translator, $method))) {
            return call_user_func_array(array($this->translator, $method), $args);
        } else {
            trigger_error("Call to undefined method '{$method}'");
        }
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    protected function removeCacheFile($locale)
    {
        if(!$this->getCacheDir()){
            return false;
        }
        $localeExploded = explode('_', $locale);
        $localePattern = sprintf('%s/catalogue.*%s*.php', $this->getCacheDir(), $localeExploded[0]);
        $files = glob($localePattern);

        $deleted = true;
        foreach ($files as $file) {
            if (!unlink($file)) {
                $deleted = false;
            }
            $metadata = $file . '.meta';
            if (file_exists($metadata)) {
                unlink($metadata);
            }
        }

        return $deleted;
    }

    /**
     * Remove the cache file corresponding to each given locale.
     *
     * @param array $locales
     */
    protected  function removeLocalesCacheFiles(array $locales)
    {
        if(!$this->getCacheDir()){
            return false;
        }
        foreach ($locales as $locale) {
            $this->removeCacheFile($locale);
        }

        // also remove database.resources.php cache file
        $file = sprintf('%s/database.resources.php', $this->getCacheDir());
        if (file_exists($file)) {
            unlink($file);
        }

        $metadata = $file . '.meta';
        if (file_exists($metadata)) {
            unlink($metadata);
        }
    }

    /**
     * @param $locale
     * @return MessageCatalogue
     */
    protected function getCatalogue($locale)
    {
        $rf = new \ReflectionObject($this->translator);
        $rfp = $rf->getProperty('catalogues');
        $rfp->setAccessible(true);
        $catalogues = $rfp->getValue($this->translator);
        $rfp->setAccessible(false);

        return $catalogues[$locale];
    }

    /**
     * @return string
     */
    protected function getCacheDir(){
        $rf = new \ReflectionObject($this->translator);
        $rfp = $rf->getProperty('options');
        $rfp->setAccessible(true);
        $options = $rfp->getValue($this->translator);
        $rfp->setAccessible(false);
        return $options['cache_dir'];
    }

    /**
     * @param string $string
     * @return string
     */
    protected function normalize($string)
    {
        return mb_strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $string), 'UTF-8');
    }

}
