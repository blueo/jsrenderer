<?php

class JsRenderer extends LeftAndMainExtension implements Flushable
{

    private $cache;

    private static $allowed_actions = array(
        'getJsRenderJob',
        'getUrlRenderData',
        'storeRenderedTemplate',
    );

    public function init()
    {

        Requirements::javascript(JSRENDERER_DIR . '/javascript/dist/index.js');
    }

    public function getJsRenderJob()
    {
        $url = StaticPagesQueue::get_next_url(); // TODO use static publisher URLS
        $page = SiteTree::get_by_link($url);
        $worker = singleton($page->ClassName)->stat('jsrenderer_worker');
        // get worker path from config
        $workers = Config::inst()->get(static::class, 'workers');

        $formatter = new JSONDataFormatter();
        $job = array(
            'Url' => $url,
            'Worker' => $workers[$worker],
        );
        $this->owner->extend('updateJsRenderJob', $job);
        $this->owner->response->addHeader('Content-Type', 'application/json');
        return json_encode($job);
    }

    public function storeRenderedTemplate()
    {
        $rq = $this->owner->getRequest();
        $body = json_decode($rq->getBody());
        $cache = $this->getCache();
        $cache->save(json_decode($body->html), md5($body->key));
        StaticPagesQueue::delete_by_link($body->key);
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
