<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Goutte\Client;

class Page extends Model
{
    public $url;
    public $parsedUrl;
    public $path = 'index';
    public $localPath;
    public $content;
    public $href;
    public $site;
    public $rootHost;
    public $nameFolder;


    function __construct($site, $url, $nameFolder)
    {
        $this->nameFolder = $nameFolder;
        $this->site = $site;
        $this->url = $url;
        $this->parsedUrl = parse_url($url);
        $this->rootHost = $this->getFormattedHost($this->parsedRoot);
        if (isset($this->parsedUrl['path'])) {
            $path = $this->parsedUrl['path'];
            if (array_key_exists('query', $this->parsedUrl)) {
                $path .=  "?" . $this->parsedUrl['query'];
            }
            if ($path == '' || $path == '/') {
            } else {
                $this->path = $path;
            }
            //var_dump("path  : " . $this->path . "</br>");
        }
    }

    function createHtml($fileName, $fileContent)
    {
        return $this->createFile($this->sitePath . $fileName, $fileContent);
    }

    function getUrls($content)
    {
        $content->filter('a')->each(function ($element, $i) {
            $url = $element->attr("href");
            if ($url == $this->url) {
            } else {
                if ($this->isUrl($url) == 0) {
                    if (str_starts_with($url, "#" || $url == "#" || $url == "")) {
                    } else {
                        $page = new Page($this->site, $url, $this->nameFolder);
                        $parsedHref = parse_url($url);
                        $formattedHrefHost = $page->getFormattedHost();
                        if ($formattedHrefHost == $this->rootHost ||  $formattedHrefHost == '') {
                            if (array_key_exists('path', $parsedHref) || array_key_exists('query', $parsedHref)) {
                                $path = $parsedHref['path'];
                                if (array_key_exists('query', $parsedHref)) {
                                    $path .=  "?" . $parsedHref['query'];
                                }
                                if ($path == '' || $path == '/') {
                                } else {
                                    $this->getPage($path);
                                }
                            }
                            if (str_starts_with($url, "https://")) {
                            }
                        }
                    }
                }
            }
        });
    }
    function isUrl($url)
    {
        foreach ($this->site->pages as $page) {
            if ($page->url == $url) {
                return 1;
            }
        }
        return 0;
    }

    function getPage($path)
    {
        $this->path = $path;
        $client = new Client();
        $this->content = $client->request('GET', $this->url);
        $html =  $this->content->html();
        $this->localPath = $this->createHtml($this->path, (string)$html);
        array_push($this->site->pages, $this);
        $this->getUrls($this->content);
    }

    function getFormattedHost()
    {
        if (!isset($this->parsedUrl["host"])) {
            return $this->site->root;
        }

        if ($this->parsedUrl != null && array_key_exists("host", $this->parsedUrl)) {
            $host = $this->parsedUrl["host"];
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
            array('<', '>', ':', '"', '..', '/', '\\', '|', '?', '*')
        ), '-', $name);
        // maximise filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $name = mb_strcut(pathinfo($name, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($name)) . ($ext ? '.' . $ext : '');
        return $name;
    }

    function createFile($fileName, $fileContent)
    {
        $sanitizedName = $this->sanitizeFileName($fileName);
        $path = "$this->nameFolder/$sanitizedName.html";

        if (!file_exists($path)) {
            file_put_contents($path, $fileContent);
        } else {
            $i = 1;

            do {
                $sanitizedName = substr($sanitizedName, 0, -5);
                $sanitizedName .= $i . ".html";
                $path = "$this->nameFolder/$sanitizedName";
            } while (file_exists($path));
            file_put_contents($path, $fileContent);
        }

        return $sanitizedName;
    }

    function changePath($fileName, $fileContent)
    {
        $sanitizedName = $this->sanitizeFileName($fileName);
        $path = "$this->nameFolder/$sanitizedName";
        file_put_contents($path, $fileContent);
    }
}