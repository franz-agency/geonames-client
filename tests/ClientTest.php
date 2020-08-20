<?php

namespace GeoNames\Tests;

use PHPUnit\Framework\TestCase;
use GeoNames\Client as GeoNamesClient;

final class ClientTest extends TestCase
{
    /**
     * @var GeoNamesClient $client
     */
    protected $client;

    protected $geonameId = '294640'; // Israel
    protected $country = 'IL'; // ISO-3166
    protected $lat = 32.117425; // Israel, Tel Aviv
    protected $lng = 34.831990; // Israel, Tel Aviv

    public function setUp()
    {
        $config = json_decode(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config.json'),
            true
        );
        $this->client = new GeoNamesClient($config['username'], $config['token']);
    }

    public function testIsAValidInstanceOfClient()
    {
        $this->assertInstanceOf(GeoNamesClient::class, $this->client);
    }

    public function testUnsupportedEndpoint()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode($this->client::UNSUPPORTED_ENDPOINT);
        $this->client->spaceships();
    }

    public function testGetSupportedEndpoints()
    {
        $endpoints = $this->client->getSupportedEndpoints();
        $this->assertIsArray($endpoints);
        $this->assertContains('astergdem', $endpoints);
        $this->assertContains('wikipediaSearch', $endpoints);
    }

    public function testGetLastTotalResultsCount()
    {

        $g =& $this->client;

        // make sure we start from scratch
        $this->setUp();

        // check for current value
        $total = $g->getLastTotalResultsCount();

        // make sure it's currently null
        $this->assertNull($total);

        // search for a large result
        $arr = $g->search([
            'q'    => '東京都',
            'lang' => 'en',
        ]);

        $total = $g->getLastTotalResultsCount();

        $this->assertIsInt($total);
        $this->assertGreaterThan(100, $total);

        // search for a couple results
        $arr = $g->search([
            'name_equals'  => 'Grüningen (Stedtli)',
            'country'      => 'CH',
            'featureClass' => 'P',
        ]);

        $this->assertIsArray($arr);
        $this->assertNotEmpty($arr);

        $count = count($arr);

        $total = $g->getLastTotalResultsCount();

        $this->assertIsInt($total);
        $this->assertEquals($count, $total);

        // search for a non-existing place
        $arr = $g->search([
            'name_equals'    => 'öalkdjfpaoirhpauhrpgjanfdlijgbiopesrzgpi'
        ]);

        $this->assertIsArray($arr);
        $this->assertEmpty($arr);

        $total = $g->getLastTotalResultsCount();

        $this->assertIsInt($total);
        $this->assertEquals(0, $total);
    }

    public function testEndpointError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode($this->client::INVALID_PARAMETER);
        $this->client->astergdem([]);
    }

    public function testAstergram()
    {
        $obj = $this->client->astergdem([
            'lat' => $this->lat,
            'lng' => $this->lng,
        ]);
        $this->assertInstanceOf(\stdClass::class, $obj);
        $this->assertObjectHasAttribute('astergdem', $obj);
        $this->assertEquals('45', $obj->astergdem);
    }

    public function testCountryInfo()
    {
        $arr = $this->client->countryInfo([
            'country' => $this->country,
            'lang'    => 'ru',
        ]);
        $this->assertIsArray($arr);
        $this->assertArrayHasKey(0, $arr);
        $obj = $arr[0];
        $this->assertObjectHasAttribute('countryName', $obj);
        $this->assertEquals('Израиль', $obj->countryName);
    }

    public function testAddress()
    {
        $obj = $this->client->address([
            'lat' => 34.072713,
            'lng' => -118.402997,
        ]);
        $this->assertInstanceOf(\stdClass::class, $obj);
        $this->assertObjectHasAttribute('countryCode', $obj);
        $this->assertEquals('US', $obj->countryCode);
        $this->assertObjectHasAttribute('locality', $obj);
        $this->assertEquals('Beverly Hills', $obj->locality);
    }

    public function testGet()
    {
        $obj = $this->client->get([
            'geonameId' => $this->geonameId,
            'lang'      => 'en',
        ]);
        $this->assertInstanceOf(\stdClass::class, $obj);
        $this->assertObjectHasAttribute('toponymName', $obj);
        $this->assertEquals('State of Israel', $obj->toponymName);
    }

    public function testOcean()
    {
        $obj = $this->client->ocean([
            'lat'    => $this->lat,
            'lng'    => $this->lng,
            'radius' => 10,
        ]);
        $this->assertInstanceOf(\stdClass::class, $obj);
        $this->assertObjectHasAttribute('name', $obj);
        $this->assertEquals('Mediterranean Sea, Eastern Basin', $obj->name);
    }

    public function testSearch()
    {
        $arr = $this->client->search([
            'q'    => '東京都',
            'lang' => 'en',
        ]);
        $this->assertIsArray($arr);
        $this->assertArrayHasKey(0, $arr);
        $obj = $arr[0];
        $this->assertObjectHasAttribute('name', $obj);
        $this->assertEquals('Tokyo', $obj->name);
    }

    /*
     * GitHub Example
     */
    public function testGitHubExample()
    {
        $g = $this->client;

        // get a list of supported endpoints
        $endpoints = $g->getSupportedEndpoints();

        // get info for country
        // note that I'm using the array destructor introduced in PHP 7.1
        [$country] = $g->countryInfo([
            'country' => 'IL',
            'lang'    => 'ru', // display info in Russian
        ]);

        // country name (in Russian)
        $country_name = $country->countryName;

        // spoken languages (ISO-639-1)
        $country_languages = $country->languages;

        $this->assertCount(39, $endpoints);
        $this->assertEquals('Израиль', $country_name);
        $this->assertEquals('he,ar-IL,en-IL,', $country_languages);
    }
}
