<?php
namespace Boris\ImgScrape;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;
use Mockery as M;

/**
 * Boris\ImgScrape\ScraperTest
 *
 * @group BorisImgScrape
 */
class ScraperTest extends \PHPUnit_Framework_TestCase
{
    public function testIsImageSuccess()
    {
        $url = 'some url';
        $headerValue = 'some value';
        $config = [
            'acceptedTypes' => [
                $headerValue
            ]
        ];
        $loggerMock = $this->getLoggerMock();

        $responseMock = $this->getResponseMock("image/" . $headerValue);
        /** @var Client|M\MockInterface $clientMock */
        $clientMock = M::mock('\GuzzleHttp\Client');
        $clientMock->shouldReceive('head')->with($url)->andReturn($responseMock)->once();

        $scraper = new Scraper($clientMock, $loggerMock, $config);

        $this->assertTrue($scraper->isImage($url));
    }

    public function testIsImageFail()
    {
        $url = 'some url';
        $headerValue = 'some value';

        $responseMock = $this->getResponseMock($headerValue);
        $loggerMock = $this->getLoggerMock();

        /** @var Client|M\MockInterface $clientMock */
        $clientMock = M::mock('\GuzzleHttp\Client');
        $clientMock->shouldReceive('head')->with($url)->andReturn($responseMock)->once();

        $scraper = new Scraper($clientMock, $loggerMock);

        $this->assertFalse($scraper->isImage($url));
    }

    public function testIsImageNotPartOfAcceptedTypes()
    {
        $url = 'some url';
        $headerValue = 'some value';
        $responseMock = $this->getResponseMock("image/" . $headerValue);

        $loggerMock = $this->getLoggerMock();

        /** @var Client|M\MockInterface $clientMock */
        $clientMock = M::mock('\GuzzleHttp\Client');
        $clientMock->shouldReceive('head')->with($url)->andReturn($responseMock)->once();

        $scraper = new Scraper($clientMock, $loggerMock);

        $this->assertFalse($scraper->isImage($url));
    }

    public function testIsImageNoContentTypeHeader()
    {
        $url = 'some url';

        /** @var ResponseInterface|M\MockInterface $responseMock */
        $responseMock = M::mock('\GuzzleHttp\Message\ResponseInterface');

        $responseMock->shouldReceive('getHeader')->with('Content-Type')->andReturn(null)->once();

        $loggerMock = $this->getLoggerMock();

        /** @var Client|M\MockInterface $clientMock */
        $clientMock = M::mock('\GuzzleHttp\Client');
        $clientMock->shouldReceive('head')->with($url)->andReturn($responseMock)->once();

        $scraper = new Scraper($clientMock, $loggerMock);

        $this->assertFalse($scraper->isImage($url));
    }

    public function testIsImageHeadCallThrowsException()
    {
        $url = 'some url';

        $responseMock = $this->getResponseMock(null);

        /** @var ClientException|M\MockInterface $exceptionMock */
        $exceptionMock = M::mock('\GuzzleHttp\Exception\ClientException');
        $exceptionMock->shouldReceive('getResponse')->andReturn($responseMock)->once();

        $loggerMock = $this->getLoggerMock();
        $loggerMock->shouldReceive('log');

        /** @var Client|M\MockInterface $clientMock */
        $clientMock = M::mock('\GuzzleHttp\Client');
        $clientMock->shouldReceive('head')->with($url)->andThrow($exceptionMock)->once();

        $scraper = new Scraper($clientMock, $loggerMock);

        $this->assertFalse($scraper->isImage($url));
    }

    /**
     * @return Logger|M\MockInterface
     */
    private function getLoggerMock()
    {
        /** @var Logger $loggerMock */
        $loggerMock = M::mock('\Boris\ImgScrape\Logger');
        return $loggerMock;
    }

    /**
     * @param string $headerValue
     *
     * @return ResponseInterface|M\MockInterface
     */
    private function getResponseMock($headerValue)
    {
        /** @var ResponseInterface|M\MockInterface $responseMock */
        $responseMock = M::mock('\GuzzleHttp\Message\ResponseInterface');
        $responseMock->shouldReceive('getHeader')->with('Content-Type')->andReturn($headerValue)->once();
        return $responseMock;
    }
}
