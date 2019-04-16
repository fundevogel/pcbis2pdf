# pcbis2pdf

## What
This small library powers [our example workflow](https://github.com/Fundevogel/book-recommendations) for collecting information from [CSV files](https://en.wikipedia.org/wiki/Comma-separated_values), exported from [pcbis.de](https://pcbis.de), and gathering some more through wholesale book distributor [KNV](http://knv.de)'s API (with built-in [Google Books API](https://developers.google.com/books) & [OpenLibrary Books API](https://openlibrary.org/dev/docs/api/books) support). For the documentation on their [WSDL](https://en.wikipedia.org/wiki/Web_Services_Description_Language) interface, see [here](http://www.knv.de/fileadmin/user_upload/IT/KNV_Webservice_2018.pdf).

Despite its name, `pcbis2pdf` probably won't ever make the step from collected data to print-ready PDF at one fell swoop (don't be fooled), but rather aid as much as possible in the process (speaking asymptotically, if you will) - it's more of a `pcbis2dtp` right now, really.


## Why
In the future, this script should power the automatized generation of our [list of recommendations](https://fundevogel.de/en/recommendations) (~ 300 books), which gets published biannually. For now, it gathers information (caching them locally), downloads book covers (from the [German National Library](https://www.dnb.de/EN/Home/home_node.html)) and exports everything back to CSV. From there, you might want to pass you results to the [DTP](https://en.wikipedia.org/wiki/Desktop_publishing) software of your choice (eg [Scribus](https://www.scribus.net), [InDesign](https://www.adobe.com/products/indesign.html), [QuarkXPress](http://www.quark.com/Products/QuarkXPress) and others).


## How
This is a "living", constantly changing boilerplate - feel free to adapt it to suit your needs. It's [available for Composer](https://packagist.org/packages/fundevogel/pcbis2pdf). Without passing any options, `pcbis2pdf` assumes the following project structure:

```text
├── ..
├── index.php
├── composer.json
├── knv.login.json
├── src
│   ├── dataList.sla
│   ├── Titelexport.csv
│   └── csv
│       ├── raw_one.csv
│       ├── raw_two.csv
│       └── ..
├── dist
│   ├── data.csv
│   ├── result.sla
│   └── images
│       ├── cover_one.jpg
│       ├── cover_two.jpg
│       └── ..
└── vendor
    ├── ..
    └── ..
```

*Note: The `dist` directory gets filled up after running `index.php`, but for presentiveness, results are included above as well.*

Make sure to provide your API credentials (see example `*.login.json` files inside the `example` directory).
Given this structure, you may automagically download book covers to `dist/images` and generate `dist/data.csv` with the following simple script:

```php
<?php

// index.php

require_once('vendor/autoload.php');

$object = new PCBIS2PDF\PCBIS2PDF;

// Combines all CSV files from `src/csv/*.csv` to `src/Titelexport.csv`
// $object->mergeCSV();

// Do it like this ..
$fromCSV = $object->CSV2PHP();
$array = $object->process($fromCSV);
$object->PHP2CSV($array);

// .. or go crazy like this:
// $object->PHP2CSV($object->process($object->CSV2PHP()));
```

Taking things one step further, inject the generated `dist/data.csv` and all downloaded images into a DTP template. In the following example (found inside the `example` directory) is using [Scribus](https://www.scribus.net), an open source desktop publishing software compatible with Windows, macOS & GNU/Linux.

Working with this over some time, you may want to add the following commands to your `composer.json` to automatize things even further. However, you might as well execute them directly:

```json
"scripts": {
    "start": "php index.php",
    "print": "./vendor/berteh/scribusgenerator/ScribusGeneratorCLI.py --single -c ./dist/data.csv -o dist -n result src/dataList.sla",
}
```

Now, start the thing off with `composer start` - good luck!

For the Scribus example workflow mentioned above, hit `composer print` and head over to `dist/result.sla`.


:copyright: Fundevogel Kinder- und Jugendbuchhandlung
