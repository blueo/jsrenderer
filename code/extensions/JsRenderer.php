<?php

class JsRenderer extends LeftAndMainExtension implements Flushable
{

    private $cache;

    private static $allowed_actions = array(
        'getUrlsToRender',
        'getUrlRenderData',
        'storeRenderedTemplate',
    );

    public function init()
    {

        Requirements::javascript(JSRENDERER_DIR . '/javascript/dist/index.js');
    }

    public function getUrlsToRender()
    {
        $urls = Config::inst()->get('JsRenderer', 'urls');


        return json_encode($urls);
    }

    public function getUrlRenderData()
    {
        $link = $this->owner->getRequest()->getVar('link');
        $obj = SiteTree::get_by_link($link);
        $apiController = new InternalAPIController();
        $json = $apiController->convertToJSON($obj);
        $this->owner->response->addHeader('Content-Type', 'application/json');
        return $json;
    }

    public function storeRenderedTemplate()
    {
        $rq = $this->owner->getRequest();
        $body = json_decode($rq->getBody());
        $cache = $this->getCache();
        $cache->save(json_decode($body->html), md5($body->key));
        $this->owner->response->addHeader('Content-Type', 'application/json');
        return json_encode('saved template');
    }

    private function getCache()
    {
        if ($this->cache) {
            return $this->cache;
        }
        $this->cache = SS_Cache::factory('JSRendererCache', 'Output', array(
            'automatic_serialization' => true,
        ));
        return $this->cache;
    }

    public static function flush()
    {
        $cache = SS_Cache::factory('JSRendererCache', 'Output', array(
            'automatic_serialization' => true,
        ));
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
}
