IbrowsTranslationHelperBundle
=============================

[![Build Status](https://travis-ci.org/ibrows/IbrowsTranslationHelperBundle.svg?branch=master)](https://travis-ci.org/ibrows/IbrowsTranslationHelperBundle)

Provide a TranslationWrapper which can create missing translations for you

Enable/disbale creation over config.

Normalize translationkeys

Decorate Missing Keys

Use your own customized Creator

Currently only YML Translation-Creator shipped in the Bundle


Install & setup the bundle
--------------------------

1. Add IbrowsTranslationHelperBundle in your composer.json:

	```js
	{
	    "require": {
	        "ibrows/translation-helper-bundle": "~1.0",
	    }
	}
	```

2. Now tell composer to download the bundle by running the command:

    ``` bash
    $ php composer.phar update ibrows/translation-helper-bundle
    ```

    Composer will install the bundle to your project's `ibrows/translation-helper-bundle` directory. ( PSR-4 )

3. Add the bundle to your `AppKernel` class

    ``` php
    // app/AppKernerl.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Ibrows\TranslationHelperBundle\IbrowsTranslationHelperBundle(),
            // ...
        );
        // ...
    }
    ```
    
4. Sample Configuration

    ```yaml
    ibrows_translation_helper:
        translator:
            normalize:            true
            create:               true
            creator:              ibrows_translation_helper.defaultcreator
            decorate:             !!!%s
            ignoreDomains:        []
            deleteCache:          false
        defaultCreator:
            format:               yml
            path:                 %kernel.root_dir%/../src/Ibrows/SampleBundle/Resources/translations
            decorate:             ___%s
            backup:               false
    ```

Avoid unnecessary translations in choices
-----------------------------------------

Use this in your form_div_default_layout.html.twig

```
{% extends 'form_div_layout.html.twig' %}
{% use '@IbrowsTranslationHelper/form_div_layout_trans_fix.html.twig' %}
```


And use this for SonataAdmin

``` yml
    sonata_doctrine_orm_admin:
        entity_manager: ~
        templates:
            form:
                - 'IbrowsTranslationHelperBundle::form_admin_fields.html.twig'
```

