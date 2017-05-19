<?php

class JsRendererTemplateHelpers implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return array(
          'JsTemplate'
        );
    }

    private static function getCache()
    {
        return SS_Cache::factory('JSRendererCache', 'Output', array(
            'automatic_serialization' => true,
        ));
    }

    public static function JsTemplate($cacheKey)
    {
        if (!$cacheKey) {
            return null;
        }
        return self::getCache()->load(md5($cacheKey));
    }
}
