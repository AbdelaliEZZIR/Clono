<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\site;


class serviceController extends Controller
{
    public function index()
    {
        //return view('index');
        $root = "https://flashymind.com";
        $site = new Site($root);
        $site->getContent();
    }
    public function i()
    {
        $root = "http://flashymind.com";
        $site = new Site($root);
        $site->getContent();
    }
}