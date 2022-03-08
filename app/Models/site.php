<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Page;
use File;
use Goutte\Client;
use DOMWrap\Document;
use ZipArchive;

class site extends Model
{
    public $url;
    public $page;
    public $root;
    public $rootUrl;
    public $formattedRoot;
    public $parsedUrl;
    public $path;
    public $pages = [];
    public $nameFolder;


    function __construct($url)
    {

        $this->url = $url;

        $this->parsedUrl = parse_url($url);
        if (isset($this->parsedUrl)) {
            if (isset($this->parsedUrl['host'])) {
                $this->root = $this->parsedUrl['host'];
                if (str_starts_with($this->root, "www.")) {
                    $this->formattedRoot = substr($this->root, 4);
                } else {
                    $this->formattedRoot = $this->root;
                }
            }
        }
        $this->rootUrl = $this->getSiteUrl();
        $this->nameFolder = $this->createFolder();
    }

    function getRoot($url)
    {
        $this->parsedUrl = parse_url($url);
        if (isset($this->parsedUrl) && isset($this->parsedUrl['host'])) {
        }
    }

    function getFormattedHost()
    {
        if ($this->parsedUrl != null && array_key_exists("host", $this->parsedUrl)) {
            $host = $this->parsedUrl["host"];
            if (str_starts_with($host, "www.")) {
                return substr($host, 4);
            } else {
                return $host;
            }
        }
    }
    function getSiteUrl()
    {
        if (!isset($this->parsedUrl)) {
            return '';
        }
        return (isset($this->parsedUrl['scheme']) ? "{$this->parsedUrl['scheme']}:" : '') .
            ((isset($this->parsedUrl['user']) || isset($this->parsedUrl['host'])) ? '//' : '') .
            (isset($this->parsedUrl['host']) ? "{$this->parsedUrl['host']}" : '') .
            (isset($this->parsedUrl['port']) ? ":{$this->parsedUrl['port']}" : '');
    }

    function getContent()
    {
        $page = new Page($this, $this->rootUrl, $this->nameFolder);
        $page->getPage($page->path);
        array_push($this->pages, $page);
        $this->changePath();
        //$this->zipFolder();
        var_dump(" ------------ Finish ---------  "  . "</br>");
    }

    function findPageByPath($path)
    {
        foreach ($this->pages as $page) {
            if ($page->path == $path) {
                return $page->localPath;
            }
        }
    }
    function changePath()
    {
        foreach ($this->pages as $page) {
            $client = new Client();
            $content = $client->request('GET', $page->url);
            $this->path = $page->path;

            $doc = new Document();
            $doc->html($content->html());
            $nodes = $doc->find('a');
            $nodes->each(function ($element) {
                $url = $element->attr("href");
                if($url== "http://localhost"){
                    $localPath = $this->findPageByPath("index.html");
                    $element->attr('href', $localPath);
                }else{
                    $parsedHref = parse_url($url);
                    $path = $parsedHref['path'];
                    $localPath = $this->findPageByPath($path);
                    $element->attr('href', $localPath);    
                }
            });
            //file_put_contents($this->path, $doc->html());
            $page->changePath($this->path, $doc->html());
        }
    }

    function zipFolder()
    {
        $zip = new ZipArchive;
        if ($zip->open($this->nameFolder . ".zip", ZipArchive::CREATE) === TRUE) {
            $dir = opendir($this->nameFolder);
            while ($file = readdir($dir)) {
                if (is_file($this->nameFolder->$file)) {
                    $zip->addFile($this->nameFolder->$file, $file);
                }
            }
            $zip->close();
        }
    }


    function createFolder()
    {
        $path = "sites/$this->formattedRoot";
        if (!file_exists($path)) {
            File::makeDirectory($path);
        } else {
            $i = 1;
            do {
                $path .= $i;
            } while (file_exists($path));
            File::makeDirectory($path);
        }
        return $path;
    }
}