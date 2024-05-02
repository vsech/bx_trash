<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Iblock;

class SitemapGenerator
{
    private $documentRoot;

    public function __construct()
    {
        $this->documentRoot = $_SERVER["DOCUMENT_ROOT"];
    }

    /**
     * Generate sitemap
     *
     * @param int $iBlockID ID of the IBLOCK
     * @param string $withSect Include sections in the sitemap or not
     *
     * @return string XML sitemap as string
     */
    public function generateSitemap($iBlockID, $withSect = 'y')
    {
        Loader::includeModule("iblock");
        $arSitemap = [];

        if ($withSect == 'y') {
            $arSitemap = $this->generateSitemapForSections($iBlockID);
        }

        $arSitemap = array_merge($arSitemap, $this->generateSitemapForElements($iBlockID));

        return $this->structSitemap($arSitemap);
    }


    /**
     * Generate sitemap from sections of the IBLOCK
     *
     * @param int $iBlockID ID of the IBLOCK
     *
     * @return array Array with two keys: LINK and DATE
     *               LINK - full URL to the page
     *               DATE - last modification date in ISO 8601 format
     */
    private function generateSitemapForSections($iBlockID)
    {
        $arSitemap = []; // Array with sitemap data
        $arFilter = array('IBLOCK_ID' => IntVal($iBlockID), 'GLOBAL_ACTIVE' => 'Y'); // Filter for the section
        $dbList = \CIBlockSection::GetList(array('SORT' => 'ASC'), $arFilter, true); // Get sections

        while ($arResult = $dbList->GetNext()) {
            $date = new DateTime($arResult['TIMESTAMP_X']); // Get last modification date
            $arSitemap[] = array( // Add data to the sitemap array
                'LINK' => $arResult['SECTION_PAGE_URL'],
                'DATE' => $date->format('c')
            );
        }

        return $arSitemap;
    }

    /**
     * Generate sitemap from elements of the IBLOCK
     *
     * @param int $iBlockID ID of the IBLOCK
     *
     * @return array Array with two keys: LINK and DATE
     *               LINK - full URL to the page
     *               DATE - last modification date in ISO 8601 format
     */
    private function generateSitemapForElements($iBlockID)
    {
        $arSitemap = []; // Array with sitemap data
        $arSelect = array("ID", 'DETAIL_PAGE_URL', 'ACTIVE', 'TIMESTAMP_X'); // Fields to get from the element
        $arFilter = array('IBLOCK_ID' => IntVal($iBlockID), 'ACTIVE' => 'Y'); // Filter for the element
        $res = \CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect); // Get elements

        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields(); // Get fields of the element
            $date = new DateTime($arFields['TIMESTAMP_X']); // Get last modification date
            $arSitemap[] = array( // Add data to the sitemap array
                'LINK' => $arFields['DETAIL_PAGE_URL'],
                'DATE' => $date->format('c')
            );
        }

        return $arSitemap;
    }

    /**
     * Generate sitemap from PHP files in the document root
     *
     * @return string XML sitemap as string
     */
    public function generateSitemapFromFiles()
    {
        // Get information about files in the document root
        $result = $this->fsDirectoryInfo($this->documentRoot, true, false, false, array('php'), false);

        // Prepare sitemap array
        $arSitemap = [];

        // Loop through file names
        foreach ($result['NAMES'] as $path) {

            // Check if the file path meets some conditions
            if (
                stristr($path, 'bitrix/') === FALSE and
                stristr($path, 'local/') === FALSE and
                stristr($path, 'upload/') === FALSE and
                stristr($path, 'include/') === FALSE and
                stristr($path, 'ajax/') === FALSE and
                stristr($path, 'personal/') === FALSE and
                stristr($path, 'test/') === FALSE and
                stristr($path, 'sitemaps/') === FALSE and
                stristr($path, 'callback/') === FALSE and
                stristr($path, 'index.php') !== FALSE
            ) {

                // Get file modification date
                $dateFile = date("c", filemtime($this->documentRoot . '/' . $path));

                // Add data to the sitemap array
                $arSitemap[] = array(
                    'LINK' => '/' . str_replace("index.php", "", $path),
                    'DATE' => $dateFile
                );
            }
        }

        // Build XML sitemap from the array
        return $this->structSitemap($arSitemap);
    }


    /**
     * Generate sitemap from HighloadBlock data
     *
     * @return string XML sitemap as string
     */
    public function generateSitemapFromHlSmartFilter()
    {
        // HighloadBlock data
        Loader::includeModule("highloadblock");
        $arSitemap = [];

        // Get HighloadBlock info
        $hlblock = HL\HighloadBlockTable::getById(_)->fetch();
        // Create entity
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        // Get entity data class name
        $entityDataClass = $entity->getDataClass();

        // Get data from the entity
        $rsData = $entityDataClass::getList(
            array(
                // Select all fields
                "select" => array("*"),
                // Order by ID asc
                "order" => array("ID" => "ASC"),
            )
        );

        // Fill sitemap array
        while ($arData = $rsData->Fetch()) {
            $date = new DateTime();
            $arSitemap[] = array(
                'LINK' => $arData['UF_HREF'],
                'DATE' => $date->format('c')
            );
        }

        // Build XML sitemap from array
        return $this->structSitemap($arSitemap);
    }

    /**
     * Recursively get information about files and directories
     *
     * @param string $path       Absolute path to directory
     * @param bool   $searchFile Search for files or not
     * @param bool   $searchDir  Search for directories or not
     * @param array  $prefix     Prefix for file or directory names
     * @param array  $fileFormats File formats to search for
     * @param bool   $topDirectory Save top directory or not
     *
     * @return array Array with two keys: NAMES and DIRS
     *               NAMES - list of filenames
     *               DIRS - list of directory paths
     */
    private function fsDirectoryInfo($path, $searchFile = true, $searchDir = true, $prefix = array(), $fileFormats = array(), $topDirectory = true)
    {
        $result = array(
            'NAMES' => array(),
            'DIRS' => array(),
        );
        $path = realpath($path);
        if ($searchDir && $topDirectory) {
            $result['DIRS'][] = $path;
        }
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $fullPath = $path . '/' . $entry;
                    if (is_dir($fullPath)) {
                        if ($searchDir) {
                            $result['DIRS'][] = $fullPath;
                        }
                    } elseif ($searchFile) {
                        if (!count($fileFormats) || in_array(pathinfo($fullPath, PATHINFO_EXTENSION), $fileFormats)) {
                            $result['NAMES'][] = implode('/', array_merge($prefix, array($entry)));
                        }
                    }
                }
            }
            closedir($handle);
        }

        return $result;
    }


    /**
     * Build XML sitemap from array
     *
     * @param array $arr Array with two keys: LINK and DATE
     *                  LINK - full URL to the page
     *                  DATE - last modification date in ISO 8601 format
     *
     * @return string XML sitemap as string
     */
    private function structSitemap(array $arr)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        /** @var array $item Single item from array */
        foreach ($arr as $item) {
            $xml->startElement('url');
            $xml->writeElement('loc', $item['LINK']);
            $xml->writeElement('lastmod', $item['DATE']);
            $xml->endElement(); // url
        }

        $xml->endElement(); // urlset

        return $xml->flush();
    }


}

// Usage
$sitemapGenerator = new SitemapGenerator();
$sitemap = $sitemapGenerator->generateSitemap($iBlockID);
