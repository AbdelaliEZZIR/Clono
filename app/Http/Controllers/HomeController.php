<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use Sunra\PhpSimple\HtmlDomParser;
use File;
use Goutte\Client;


class HomeController extends Controller
{



    function createHtml($fileName, $fileContent)
    {
        global $sitePath;
        var_dump("site " . $sitePath);
        var_dump("file " . $fileName);
        die;
        return $this->createFile($sitePath . $fileName, $fileContent);
    }


    public function index()
    {
        $root = "http://localhostddddddd";

        $parsedRoot = parse_url($root);

        //var_dump($parsedRoot);die;

        $rootHost = $this->getFormattedHost($parsedRoot);
        $sitePath = $this->createFolder($rootHost);
        $savedFiles = array();
        $this->getPage($root, 'index.html', true);
    }


    function createJs($fileName, $fileContent)
    {
        global $sitePath;
        return $this->createFile($sitePath . "/js/" . $fileName, $fileContent);
    }

    function createCss($fileName, $fileContent)
    {
        global $sitePath;
        return $this->createFile($sitePath . "/css/" . $fileName, $fileContent);
    }

    function createImages($fileName, $fileContent)
    {
        global $sitePath;
        return $this->createFile($sitePath . "/images/" . $fileName, $fileContent);
    }


    function createFile($fileName, $fileContent)
    {
        $sanitizedName = $fileName;

        if (!file_exists($sanitizedName)) {
            echo "<br>$sanitizedName<br>";
            file_put_contents($sanitizedName, $fileContent);
        } else {
            $i = 1;
            var_dump("eeeeeeeee");
            do {
                $sanitizedName .= $i;
            } while (file_exists($sanitizedName));
            file_put_contents($sanitizedName, $fileContent);
            var_dump($sanitizedName);
            var_dump($fileContent);
            die;
        }

        return $sanitizedName;
    }

    function createFolder($host)
    {
        global $sitePath;
        $path = "sites/$host";
        if (!file_exists($path)) {
            //var_dump($path);
            // die;
            //mkdir($path , 0777, true);
            //File::makeDirectory($path);
        } else {
            // $i=1;
            //do{
            //   $path .=$i;
            // }while (file_exists($sitePath));
            //mkdir($path , 0777, true);
        }

        return $path;
    }


    function getFormattedHost($parsedUrl)
    {
        if ($parsedUrl != null && array_key_exists("host", $parsedUrl)) {
            $host = $parsedUrl["host"];
            if (str_starts_with($host, "www.")) {
                return substr($host, 4);
            } else {
                return $host;
            }
        }
    }

    function sanitizeFileName($name)
    {
        // remove illegal file system characters https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        $name = str_replace(array_merge(
            array_map('chr', range(0, 31)),
            array('<', '>', ':', '"', '/', '\\', '|', '?', '*')
        ), '', $name);
        // maximise filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $name = mb_strcut(pathinfo($name, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($name)) . ($ext ? '.' . $ext : '');
        return $name;
    }

    function getPage($url, $path, $loadChildren = false)
    {
        
        global $savedFiles, $sitePath;
        // $data = file_get_contents($url);
        $client = new Client();
        $data = $client->request('GET', $url);
        //$data= Http::get($url);
        //var_dump($data);
        //var_dump("ffffffffffffffffff");

        //var_dump($a->count());die;

        $html = $data->html();
        //   HtmlDomParser::file_get_html($data);

        $localPath = $this->createHtml($path, (string)$html);

        $savedFiles[$path] = $localPath;

        if ($loadChildren) {
            $this->getUrls($html);
        }
        //     echo "<br>localPathhhhhhhhhhhhh  $localPath ".(strlen($sitePath)+1)."<br>";
        //     $localPath = substr($localPath, strlen($sitePath)+1);
        //     echo "<br>localPathhhhhhhhhhhhh  afteeereeer $localPath<br>";
        file_put_contents($localPath, (string)$html);
    }


    function getUrls($html)
    {
        global $rootHost, $savedFiles;
        foreach ($html->find('a') as $element) {
            $href = $element->href;

            echo "<br>hreeeeeeeeeeeeeef1 $href<br>";

            $parsedHref = parse_url($href);


            $formattedHrefHost = $this->getFormattedHost($parsedHref);
            if ($formattedHrefHost == $rootHost) {
                echo "<br>hreeeeeeeeeeeeeef2 $href<br>";
                $path = '/index.html';
                if (array_key_exists('path', $parsedHref) && $parsedHref['path'] != '' && $parsedHref['path'] != '/') {
                    $path = $parsedHref['path'];
                }

                echo "<br>paaaaaaaaaaaaaaaath $path<br>";
                var_dump($savedFiles);
                if (array_key_exists($path, $savedFiles)) {
                    echo "<br>hreeeeeeeeeeeeeef4 $href<br>";
                    $element->href = $savedFiles[$path];
                    echo "<br>$element->href<br>";
                    var_dump((string)$element);
                } else {
                    echo "<br>hreeeeeeeeeeeeeef5 $href - $path<br>";
                    $this->getPage($href, $path);
                    break;
                }
            }
        }
    }
}