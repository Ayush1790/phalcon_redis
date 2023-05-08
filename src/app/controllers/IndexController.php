<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->cache->set("name", "ayush");
        //redirect to view
        echo "<pre>";
        print_r($this->cache->get('name'));
        die;
    }
}
