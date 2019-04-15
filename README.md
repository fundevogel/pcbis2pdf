# pcbis2pdf

## What
This small library serves [our example workflow](https://github.com/Fundevogel/book-recommendations) for collecting information from [CSV files](https://en.wikipedia.org/wiki/Comma-separated_values), exported from [pcbis.de](https://pcbis.de), and gathering some more through wholesale book distributor [KNV](http://knv.de)'s API (with built-in [GoogleBooks API](https://developers.google.com/books) support). For the documentation on their [WSDL](https://en.wikipedia.org/wiki/Web_Services_Description_Language) interface, see [here](http://www.knv.de/fileadmin/user_upload/IT/KNV_Webservice_2018.pdf).


## Why
In the future, this script should automatize the generation of our [list of recommendations](https://fundevogel.de/en/recommendations) (~ 100 books), which gets published biannually. For now, it gathers information (caching them locally), downloads book covers (from the [German National Library](https://www.dnb.de/EN/Home/home_node.html)) and exports everything back to CSV. From there, you might want to pass you results to the [DTP](https://en.wikipedia.org/wiki/Desktop_publishing) software of your choice (eg, [Scribus](https://www.scribus.net), [QuarkXPress](http://www.quark.com/Products/QuarkXPress/), etc).


## How
This is a WIP, by definition constantly changing: It's more of a living boilerplate, if you will - feel free to adapt it to suit your needs (available for Composer, see [here](https://packagist.org/packages/fundevogel/pcbis2pdf)):

```php
<?php

require_once('vendor/autoload.php');

$object = new PCBIS2PDF\PCBIS2PDF;

$fromCSV = $object->CSV2PHP();
$array = $object->process($fromCSV);
$toCSV = $object->PHP2CSV($array);

// or go crazy like this:
$object->PHP2CSV($object->process($object->CSV2PHP()));
```

However, if you want to get started, just provide your credentials (see example `.json` files inside the `example` directory), copy over the example files, drop some `*.csv` files in `src/csv` and add the following commands to your `composer.json` (or execute them directly):

```json
"scripts": {
    "start": "php index.php",
    "print": "./vendor/berteh/scribusgenerator/ScribusGeneratorCLI.py --single -c ./dist/data.csv -o dist -n result src/example.sla",
}
```

Now, start the thing off with `composer start` - good luck!

For the included Scribus example workflow, hit `composer print` and head over to `dist/result.sla`.


:copyright: Fundevogel Kinder- und Jugendbuchhandlung
