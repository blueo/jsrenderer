<?php

class JsRenderJob extends DataObject
{
    private static $db = array(
        'Name' => 'Text',
        'Worker' => 'Text',
        'Complete' => 'Boolean'
    );
}
