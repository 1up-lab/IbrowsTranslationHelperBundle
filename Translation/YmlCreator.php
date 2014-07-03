<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;


/**
 * Class DefaultCreator
 * @package Ibrows\TranslationHelperBundle\Translation
 */
class YmlCreator extends DefaultCreator
{
    protected function supportFormat($format)
    {
        return ($format == 'yml');
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

        $this->setNewId($id, $domain, $catalogue);
        $file = $domain . '.' . $catalogue->getLocale() . '.' . $this->format;
        $fullpath = $this->path . '/' . $file;
        $allMessages = $catalogue->all($domain);

        $this->flatten($allMessages);
        //first sort to make sure all-level sort
        uksort($allMessages, 'strnatcasecmp');
        $allMessages = $this->expand($allMessages);
        //second sort to make sure first-level sort
        uksort($allMessages, 'strnatcasecmp');
        $yaml = Yaml::dump($allMessages, 9);
        if ($this->backup && file_exists($fullpath)) {
            $backupfullpath = $this->path . '/' . $domain . '.' . $locale . '.' . $this->format . '~';
            if (!file_exists($backupfullpath)) {
                copy($fullpath, $backupfullpath);
            }
        }
        file_put_contents($fullpath, $yaml);


    }

    private function expand(array $messages, array $current = null)
    {

        foreach ($messages as $key => $value) {
            $keys = explode('.', $key);
            if (sizeof($keys) > 1) {
                krsort($keys);
                $current = array();
                foreach ($keys as $i => $subkey) {
                    $current = array();
                    $current[$subkey] = $value;
                    $value = $current;
                }
                $messages = array_replace_recursive($messages, $value);
                unset($messages[$key]);
            }
        }

        return $messages;
    }

    private function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = & $messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path . '.' . $key : $key;
                $this->flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path . '.' . $key] = $value;
            }
        }
    }


}
