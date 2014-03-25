IbrowsTranslationHelperBundle
=============================

[![Build Status](https://travis-ci.org/ibrows/IbrowsTranslationHelperBundle.svg?branch=master)](https://travis-ci.org/ibrows/IbrowsTranslationHelperBundle)

Provide a TranslationWrapper which can create missing translations for you

Enable/disbale creation over config.

Normalize translationkeys

Decorate Missing Keys


Currently only YML Translation-Creator in the Bundle.

But use your own customized Creator

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

    Composer will install the bundle to your project's `ibrows/simplecms-bundle` directory. ( PSR-4 )

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

