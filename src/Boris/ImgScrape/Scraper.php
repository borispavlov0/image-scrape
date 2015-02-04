<?php

namespace Boris\ImgScrape;

    /**
     * Scraper.php
     * Author: Boris Pavlov <borispavlov0 at gmail.com>
     * Date: 6-Dec-2014
     */

/**
 * Class used to analyze remote images and URLs
 */
class Scraper
{
    const IMAGE_SOURCE_REGEX = '/(?=img).+\ssrc=\"([^\s\"]+)/i';
    public $count = 0;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var array
     */
    private $config;

    /**
     * Default constructor. Please refer to the configuration reference for the format of $config
     *
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        $this->config = require __DIR__ . '/config/config.php';
        if (is_array($config)) {
            $this->config = array_replace_recursive($this->config, $config);
        }
        $this->logger = new Logger($this->config['logger']);
    }

    /**
     * This function issues a HEAD call to get the Content-Type and checks if the URL supplied is an image
     *
     * @param string $url
     *
     * @return boolean
     */
    public function isImage($url)
    {
        $headers = get_headers($url);

        foreach ($headers as $h) {
            $headerArray = explode(":", $h);

            if ($headerArray[0] == 'Content-Type' && in_array(trim($headerArray[1]), $this->getAcceptedTypes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of defined accepted image types, each in the form image/*
     *
     * @return array
     */
    public function getAcceptedTypes()
    {
        return array_map(function ($value) {
            return "image/" . $value;
        }, $this->config['acceptedTypes']
        );
    }

    /**
     * Issues a HEAD call to URL which must be pointing to an image resource. If the response does not contain
     * a Content-Length header, it proceeds to download the binary of the image and computes its size that way
     *
     * @param string $url
     *
     * @return int
     */
    public function getSize($url)
    {
        $this->logger->log('debug', "Get headers: " . $url . "\n");
        $headers = get_headers($url);

        $this->logger->log('debug', "Received headers: " . $url . "\n", $headers);

        $this->count++;

        foreach ($headers as $h) {
            $headerArray = explode(":", $h);

            if ($headerArray[0] == 'Content-Length') {
                return $headerArray[1];
            }
        }
        $data = $this->getDataAsString($url);

        return sizeof($data);
    }

    /**
     * Returns an array of all image sources ("img" tags) defined in remote URL
     *
     * @param string $url
     *
     * @return array
     */
    public function getImageSources($url)
    {
        $html = $this->getHtml($url);

        preg_match_all(self::IMAGE_SOURCE_REGEX, $html, $matches);
        array_walk($matches[1],
            function (&$value) use ($url) {
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $urlInfo = parse_url($url);
                    if (substr($value, 0, 2) == '//') {
                        $this->logger->log(
                            Logger::DEBUG, "Returning Image Source (Fixed): " . $urlInfo['scheme'] . ":" . $value . "\n"
                        );

                        $value = $urlInfo['scheme'] . ":" . $value;

                        return;
                    } elseif (substr($value, 0, 1) == "/") {
                        $this->logger->log(
                            Logger::DEBUG,
                            "Returning Image Source (Fixed): " .
                            $urlInfo['scheme'] . "://" . $urlInfo['host'] . $value . "\n"
                        );
                        $value = $urlInfo['scheme'] . "://" . $urlInfo['host'] . $value;

                        return;
                    }
                    $this->logger->log(Logger::DEBUG, "Unsetting: " . $value . "\n");
                    $value = false;

                    return;
                }
                $this->logger->log(Logger::DEBUG, "Returning Image Source (valid URL): " . $value . "\n");
            });

        return array_filter($matches[1]);
    }

    /**
     * Returns the URL of the largest (in size) image on remote URL
     *
     * @param string $url
     *
     * @return null|string
     */
    public function getLargestImageUrl($url)
    {
        if ($this->isImage($url)) {
            $this->logger->log(Logger::INFO, "Picture URL is image (" . $url . "). \n");
            $this->count++;

            return $url;
        }
        if ($this->config['imageLinksOnly']) {
            $this->logger->log(Logger::NOTICE, "Only image links allowed, skipping url " . $url);

            return null;
        }
        $images = $this->getImageSources($url);
        $size = 0;

        foreach ($images as $i) {

            $imgSize = $this->getSize($i);
            $this->count++;

            if ($imgSize > $size) {
                $size = $imgSize;
                $pictureUrl = $i;
            }
        }

        return isset($pictureUrl)
            ? $pictureUrl
            : null;
    }

    /**
     * Get the source of a remote URL
     *
     * @param string $url
     *
     * @return string
     */
    private function getHtml($url)
    {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20141220 Firefox/2.0.0.13');

        // $output contains the output string
        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    /**
     * Downloads an element and returns its size
     *
     * @param string $url
     *
     * @return int
     */
    private function getDataAsString($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        $data = curl_exec($curl);
        curl_close($curl);

        return sizeof($data);
    }
}
