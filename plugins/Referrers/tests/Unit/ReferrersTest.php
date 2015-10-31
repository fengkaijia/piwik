<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Referrers\tests;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Period;
use Piwik\Plugins\Referrers\SearchEngine;

require_once PIWIK_INCLUDE_PATH . '/plugins/Referrers/Referrers.php';

/**
 * @group Referererer
 */
class ReferrersTest extends \PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        // inject definitions to avoid database usage
        $yml = file_get_contents(PIWIK_INCLUDE_PATH . SearchEngine::DEFINITION_FILE);
        SearchEngine::getInstance()->loadYmlData($yml);

        parent::setUpBeforeClass();
    }

    /**
     * Dataprovider serving all search engine data
     */
    public function getSearchEngines()
    {
        $searchEngines = array();
        foreach (SearchEngine::getInstance()->getSearchEngineDefinitions() as $url => $searchEngine) {
            $searchEngines[] = array($url, $searchEngine);
        }
        return $searchEngines;
    }

    /**
     * search engine has at least one keyword
     *
     * @group Plugins
     *
     * @dataProvider getSearchEngines
     */
    public function testMissingSearchEngineKeyword($url, $searchEngine)
    {
        // Get list of search engines and first appearing URL
        static $searchEngines = array();

        $name = parse_url('http://' . $url);
        if (!array_key_exists($searchEngine['name'], $searchEngines)) {
            $searchEngines[$searchEngine['name']] = $url;

            $this->assertTrue(!empty($searchEngine['params']), $name['host']);
        }
    }

    /**
     * search engine is defined in DataFiles/SearchEngines.php but there's no favicon
     *
     * @group Plugins
     *
     * @dataProvider getSearchEngines
     */
    public function testMissingSearchEngineIcons($url, $searchEngine)
    {
        // Get list of existing favicons
        $favicons = scandir(PIWIK_PATH_TEST_TO_ROOT . '/plugins/Referrers/images/searchEngines/');

        // Get list of search engines and first appearing URL
        static $searchEngines = array();

        $name = parse_url('http://' . $url);
        if (!array_key_exists($searchEngine['name'], $searchEngines)) {
            $searchEngines[$searchEngine['name']] = $url;

            $this->assertTrue(in_array($name['host'] . '.png', $favicons), $name['host']);
        }
    }

    /**
     * favicon exists but there's no corresponding search engine defined in DataFiles/SearchEngines.php
     *
     * @group Plugins
     */
    public function testObsoleteSearchEngineIcons()
    {
        // Get list of search engines and first appearing URL
        $searchEngines = array();
        foreach (SearchEngine::getInstance()->getSearchEngineDefinitions() as $url => $searchEngine) {
            $name = parse_url('http://' . $url);
            if (!array_key_exists($name['host'], $searchEngines)) {
                $searchEngines[$name['host']] = true;
            }
        }

        // Get list of existing favicons
        $favicons = scandir(PIWIK_PATH_TEST_TO_ROOT . '/plugins/Referrers/images/searchEngines/');
        foreach ($favicons as $name) {
            if ($name[0] == '.' || strpos($name, 'xx.') === 0) {
                continue;
            }

            $host = substr($name, 0, -4);
            $this->assertTrue(array_key_exists($host, $searchEngines), $host);
        }
    }

    public function removeUrlProtocolTestData()
    {
        return array(
            array('http://www.facebook.com', 'www.facebook.com'),
            array('https://bla.fr', 'bla.fr'),
            array('ftp://bla.fr', 'bla.fr'),
            array('udp://bla.fr', 'bla.fr'),
            array('bla.fr', 'bla.fr'),
            array('ASDasdASDDasd', 'ASDasdASDDasd'),
        );
    }

    /**
     * @group Plugins
     *
     * @dataProvider removeUrlProtocolTestData
     */
    public function testRemoveUrlProtocol($url, $expected)
    {
        $this->assertEquals($expected, \Piwik\Plugins\Referrers\removeUrlProtocol($url));
    }
}
