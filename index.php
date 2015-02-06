<?php

require 'vendor/autoload.php';
$redditUrl = 'http://www.reddit.com';

use Boris\ImgScrape\Scraper;
use GuzzleHttp\Client;

$client = new Client(['base_url' => $redditUrl]);

$response = $client->get('/api/me.json')->json();

if (empty($response)) {
    $response = $client->post('/api/login',
        [
            'body' => [
                'api_type' => 'json',
                'user'     => 'some user',
                'passwd'   => 'some pass',
                'rem'      => true
            ]
        ]
    )->json();
}

$hotPosts = $client
    ->get('/r/comicbookart/hot.json',
        [
            'headers' => [
                'X-Modhash' => $response['json']['data']['modhash']
            ],
            'query'   => [
                'limit' => 5
            ]
        ])
    ->json();

$scraper = new Scraper($client);
foreach ($hotPosts['data']['children'] as $post) {
    $url = $post['data']['url'];
    $pictureUrl = $scraper->getLargestImageUrl($url);

    if ($pictureUrl) {
        exec(
            'wget -P ~/Projects/ImgScrape/downloads ' . $pictureUrl . ' 2>&1', $output, $returnVar
        );
    }
}
