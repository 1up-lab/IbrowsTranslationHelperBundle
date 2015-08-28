<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class TranslatorWrapper
 * @package Ibrows\TranslationHelperBundle\Translation
 */
class TranslatorWrapper implements TranslatorInterface, TranslatorBagInterface
{

    /**
     * @var TranslatorInterface|TranslatorBagInterface
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
    protected $remember = false;
    protected $rememberCache = array();
    /**
     * @var bool
     */
    protected $create = true;
    /**
     * @var bool
     */
    protected $createFallback = true;
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
     * @param CreatorInterface    $creator
     * @param TranslatorInterface $translator
     */
    public function __construct(CreatorInterface $creator, TranslatorInterface $translator)
    {
        $this->creator = $creator;
        $this->translator = $translator;
    }

    /**
     * @param string $id
     * @param array  $parameters
     * @param null   $domain
     * @param null   $locale
     * @return string
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {

        $id = (string)$id;

        if (null === $locale) {
            $locale = $this->translator->getLocale();
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (in_array($domain, $this->ignoreDomains)) {
            $result = $this->translator->trans($id, $parameters, $domain, $locale);
            return $result;
        }

        if ($this->isInCatalogue($id, $domain, $locale)) {
            $result = $this->translator->trans($id, $parameters, $domain, $locale);
            $this->addToCache($id, $parameters, $domain, $locale, $result);
            return $result;
        }

        if ($this->normalize) {
            $id = $this->normalize($id);
            if ($this->isInCatalogue($id, $domain, $locale)) {
                $result = $this->translator->trans($id, $parameters, $domain, $locale);
                $this->addToCache($id, $parameters, $domain, $locale, $result);
                return $result;
            }
        }
        $result = $this->translator->trans($id, $parameters, $domain, $locale);

        if ($this->create) {
            $this->creator->createTranslation($id, $domain, $locale, $this->getCatalogue($locale));
            if ($this->deleteCache) {
                $this->removeLocalesCacheFiles(array($locale));
            }
        }
        if ($this->createFallback) {
            $fallbackCatalogue = $this->getCatalogue($locale)->getFallbackCatalogue();
            if ($fallbackCatalogue) {
                $fallbackLocale = $fallbackCatalogue->getLocale();
                $this->creator->createTranslation($id, $domain, $fallbackLocale, $fallbackCatalogue);
                if ($this->deleteCache) {
                    $this->removeLocalesCacheFiles(array($fallbackLocale));
                }
            }
        }

        $result = sprintf($this->decorate, $result);
        $this->addToCache($id, $parameters, $domain, $locale, $result);

        return $result;
    }

    /**
     * @param string $id
     * @param int    $number
     * @param array  $parameters
     * @param null   $domain
     * @param null   $locale
     * @return string
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        $ret = $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
        if ($this->remember) {
            $this->rememberCache[$id] = array(
                'parameters' => $parameters,
                'domain'     => $domain,
                'locale'     => $locale,
                'result'     => $ret,
                'number'     => $number,
            );
        }
        return $ret;
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
    private function isInCatalogue($id, $domain, $locale)
    {
        return ($this->getCatalogue($locale)->has((string)$id, $domain));

    }

    /**
     * @param boolean $create
     */
    public function setCreate($create)
    {
        $this->create = $create;
    }

    /**
     * @param boolean $createFallback
     */
    public function setCreateFallback($createFallback)
    {
        $this->createFallback = $createFallback;
    }

    /**
     * @return boolean
     */
    public function isRemember()
    {
        return $this->remember;
    }

    /**
     * @param boolean $remember
     */
    public function setRemember($remember)
    {
        $this->remember = $remember;
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

    public function getTranslations($id = null)
    {
        if ($id !== null) {
            return $this->rememberCache[$id];
        } else {
            return $this->rememberCache;
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
        if (!$this->getCacheDir()) {
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
    protected function removeLocalesCacheFiles(array $locales)
    {
        if (!$this->getCacheDir()) {
            return;
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
    public function getCatalogue($locale = null)
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * @return string
     */
    protected function getCacheDir()
    {
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
        $string = preg_replace('/(?<=[a-z])([\p{Lu}])/u', '_$1', $string);
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/[\s_]+/u', '_', $string);
        return $string;
    }

    private function addToCache($id, $parameters, $domain, $locale, $ret)
    {
        if ($this->remember) {
            $this->rememberCache[$id] = array(
                'parameters' => $parameters,
                'domain'     => $domain,
                'locale'     => $locale,
                'result'     => $ret,
                'number'     => null,
            );
        }
    }


}

