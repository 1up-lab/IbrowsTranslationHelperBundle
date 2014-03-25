<?php

namespace Ibrows\TranslationHelperBundle\Translation;


use Symfony\Component\Translation\MessageCatalogue;

interface CreatorInterface
{
    /**
     * @param string $id
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function createTranslation($id, $domain, $locale,MessageCatalogue $catalogue);

}