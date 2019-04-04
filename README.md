# pcbis2pdf

## What
This small library serves as an example workflow for collecting information from [CSV files](https://en.wikipedia.org/wiki/Comma-separated_values), exported from [pcbis.de](https://pcbis.de), and gathering some more through wholesale book distributor [KNV](http://knv.de)'s API (with built-in [GoogleBooks API](https://developers.google.com/books) support). For the documentation on their [WSDL](https://en.wikipedia.org/wiki/Web_Services_Description_Language) interface, see [here](http://www.knv.de/fileadmin/user_upload/IT/KNV-Webservice-2.pdf).

## Why
In the future, this script should automatize the generation of our [list of recommendations](https://fundevogel.de/en/recommendations) (~ 100 books), which gets published biannually. For now, it gathers information (caching them locally), downloads book covers (from the [German National Library](https://www.dnb.de/EN/Home/home_node.html)) and exports everything back to CSV. From there, you might want to pass you results to the [DTP](https://en.wikipedia.org/wiki/Desktop_publishing) software of your choice (eg, [Scribus](https://www.scribus.net), [QuarkXPress](http://www.quark.com/Products/QuarkXPress/), etc).

## How
This is a WIP and far from finished (*Do you even OOP?*): It won't be availabe through Composer or anything.

It's more of a living boilerplate, if you will - feel free to adapt it to suit your needs. However, if you want to get started, just type in your credentials, drop some `*.csv` files in `src/csv` and there you go:

```bash
mv knv.example.json knv.login.json
```

Now, start the thing off with `composer start` - good luck!

:copyright: Fundevogel Kinder- und Jugendbuchhandlung
