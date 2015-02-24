<?php

namespace Boris\ImgScrape;

/**
 * Scraper.php
 * Author: Boris Pavlov <borispavlov0 at gmail.com>
 * Date: 6-Dec-2014
 */
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Url;

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
     * @var Client
     */
    private $client;

    /**
     * Default constructor. Please refer to the configuration reference for the format of $config
     *
     * @param Client $client
     * @param Logger $logger
     * @param array  $config
     */
    public function __construct(Client $client, Logger $logger, array $config = null)
    {
        $this->config = require __DIR__ . '/config/config.php';
        if (is_array($config)) {
            $this->config = array_merge_recursive($this->config, $config);
        }
//        $this->logger = new Logger($this->config['logger']);
        $this->client = $client;
        $this->logger = $logger;
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
        $response = $this->headCall($url);

        if (in_array($response->getHeader('Content-Type'), $this->getAcceptedTypes())) {
            return true;
        }

        return false;
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
        $this->logger->log(Logger::DEBUG, "Get headers: " . $url . "\n");
        $response = $this->headCall($url);

        $this->logger->log(Logger::DEBUG, "Received headers: " . $url . "\n", json_encode($response->getHeaders()));

        $this->count++;

        if ($response->getHeader('Content-Length')) {
            return $response->getHeader('Content-Length');
        }

        $data = $this->getSizeFromUrl($url);

        return sizeof($data);
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
        if ($this->isBlacklisted($url)) {
            return null;
        }
        if ($this->isImage($url)) {
            $this->logger->log(Logger::INFO, "Picture URL is image (" . $url . "). \n");
            $this->count++;

            return $url;
        }
        if ($this->config['imageLinksOnly']) {
            $this->logger->log(Logger::NOTICE, "Only image links allowed, skipping url " . $url);

            return null;
        }

        return $this->processImageArray($this->getImageSources($url));
    }

    /**
     * Returns an array of all image sources ("img" tags) defined in remote URL
     *
     * @param string $url
     *
     * @return array
     */
    private function getImageSources($url)
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
     * Get the source of a remote URL
     *
     * @param string $url
     *
     * @return string
     */
    private function getHtml($url)
    {
        $response = $this->client->get($url, [
            'headers' => [
                'User-Agent' => $this->config['user-agent']
            ]
        ]);

        return (string) $response->getBody();
    }

    /**
     * Returns an array of defined accepted image types, each in the form image/*
     *
     * @return array
     */
    private function getAcceptedTypes()
    {
        return array_map(
            function ($value) {
                return "image/" . $value;
            },
            $this->config['acceptedTypes']
        );
    }

    /**
     * Downloads an element and returns its size
     *
     * @param string $url
     *
     * @return int
     */
    private function getSizeFromUrl($url)
    {
        $response = $this->client->get($url);

        return $response->getBody()->getSize();
    }

    /**
     * @param string $url
     *
     * @return Response
     */
    private function headCall($url)
    {
        try {
            return $this->client->head($url);
        } catch (ClientException $e) {
            $this->logger->log(Logger::ERROR, $e->getMessage());

            return $e->getResponse();
        }
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function isBlacklisted($url)
    {
        $url = Url::fromString($url);
        if (in_array($url->getHost(), $this->config['blacklist'])) {
            $this->logger->log(Logger::NOTICE, "Url is blacklisted: " . $url);

            return true;
        }

        return false;
    }

    /**
     * @param array $images
     *
     * @return null
     */
    private function processImageArray($images = array())
    {
        $size = 0;

        $pictureUrl = null;

        foreach ($images as $i) {

            $imgSize = $this->getSize($i);
            $this->count++;

            if ($imgSize > $size) {
                $size = $imgSize;
                $pictureUrl = $i;
            }
        }
        $this->logger->log(Logger::INFO, "Returning picture url as '" . ($pictureUrl
                ? $pictureUrl
                : "NULL") . "'");

        return $pictureUrl;
    }
}
