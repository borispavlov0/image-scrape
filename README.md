#Installation

Install via composer:  
  
    require: {
        "boris/imgscrape": "0.*"
    }
  
#Usage

Please check the index.php file supplied for a working example.

In essence you initialize the scraper by doing:

    $scraper = new Boris\ImgScraper\Scraper($client, $logger, $configArray);
    
Here the $client object is a Guzzle client instance and the $logger is located in the same namespace as the scraper.

To get the source of the largest image on any URL:

    $scraper->getLargestImageUrl($url);
    
The script issues a head request first. If the 'imageLinksOnly' parameter is set to true, if the response does not contain a 'Content-Type' header or if that header is not of an image type, it returns null. Otherwise, it just returns the same URL (this functionality is useful if you have a huge array of URLs and you want to get only the direct image URLs).


###Symfony

To use this component in Symfony, please register it as a service:  
  
  
    parameters:  
      boris.scraper: ~
      boris.logger: ~
      guzzle.params:
        base_url: http://www.reddit.com
  
  
    services:
        boris.logger:
          class: Boris\ImgScrape\Logger
          arguments: [%boris.logger%]
        
        boris.imgscrape:
          class: Boris\ImgScrape\Scraper
          arguments: [@guzzle.client, %boris.scraper%]
        
        guzzle.client:
          class: GuzzleHttp\Client
          arguments: [%guzzle.params%]
          
          
You can then call this from the container:
    
    $this->container->get('boris.imgscrape');
   
#Parameter Reference

There is a default set of parameters that can be overridden when initializing the scraper and logger combo:  
  
    $config = [
        'imageLinksOnly' => false,
        'acceptedTypes' => [
            'jpeg',
            'jpg',
            'gif',
            'png',
        ],
        'blacklist' => [
            'www.reddit.com'
        ],
        'user-agent' => 'Boris-ImgScrape/0.2 (amateur script, contact: my at email dot com)'
    ];
    
    $configLogger = [
        'enabled' => true,
        'handlers' => [
            [
                'dir' => __DIR__ . '/../../../../log/debug.log',
                'level' => 'debug'
            ],
            [
                'dir' => __DIR__ . '/../../../../log/main.log',
                'level' => 'info'
            ],
        ]
    ];
    
These can be used as your %scraper% parameters value and you only need to override what you need. Here is a reference on what each parameter means:

    scraper:
        imageLinksOnly: only returns the URL if the supplied URL is for and image
        acceptedTypes: accepted image mime types
        blacklist: which hostnames to ignore
        user-agent: your useragent string
    
    logger:
        enabled: whether or not to enable the logger
        handlers: an array for each logger handler. Supply the dir and the level of the logger (this component uses Monolog, so you can check the default documentation for levels)
        
#Tests

In order for tests to run, you need to include the following dependencies in your project for composer to install:

    require-dev: {
        "mockery/mockery": "0.9.*@dev",
        "phpunit/phpunit": "4.7.*@dev"
    }

To run tests, navigate to the root directory of the project and run:
  
    phpunit --group=BorisImgScrape
    
#Logs

By default, Monolog creates a log file with the level specified in the 'handlers' parameter of the logger config. You can use DEBUG, but keep in mind the logs get quite big.