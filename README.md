# Javascript Template Renderer for Silverstripe

Module to render templates partials from javascript files. Uses Web Workers to run the render whilst a CMS user is logged in. 


## Setup

The module loads a script when you log into the CMS that checks for new render jobs. If it finds one, it will pass the job data to a worker script to process. On the successful completion of a worker, it will save the returned html string into a cache for use within a template.


### Config

Add the `JsRenderer` extension to the `LeftAndMain` class and then define the path to a worker js file on the `JsRenderer` class.

```
LeftAndMain:
  extensions:
    - JsRenderer
JsRenderer:
  workers:
    default: themes/mytheme/dist/js/worker.js
```

Individual pages may use different workers by including a static variable `$jsrenderer_worker` eg:
`private static $jsrenderer_worker = 'default';`

### Creating jobs

The [silverstripe/staticpublishqueue](https://github.com/silverstripe/silverstripe-staticpublishqueue/) module is used to register jobs and prevent multiple scripts running the same job at the same time. There are a number of ways to configure a queued job and you can find the documentation on the module's readme.


### Passing data to a job

There is an extension point for you to add detail to a job before it is sent to the worker script called `updateJsRenderJob`. You can use this to pass any info needed by your script, eg initial state for bootstrapping a redux application. For example, you can create another extension for left and main like so:

```
class MyJsRenderJobExtension extends DataExtension
{
    public function updateJsRenderJob(&$job)
    {
        if ($job['Url'] !== '') {
            $page = SiteTree::get_by_link($job['Url']);
            $siteConfig = singleton('Page_Controller')->getSiteConfig();
            $siteSettings = array (
                'Title' => $siteConfig->Title,
                'BaseURL' => Director::baseURL(),
                'ThemeDir' => '/themes/mytheme',
            );
            $job['siteSettings'] = json_encode($siteSettings);
            $job['ID'] = $page->ID;
        }
        return $job;
    }
}
```


## Template helper

Included is a template helper `JsTemplate` to inject the rendered html into a silverstripe template. Rendered data is saved in the cache with the URL rendered as the key. The template helper expects a URL such as `$Link`: 
```
  <div id="app">$JsTemplate($Link).RAW</div>

```

## Creating a worker file

Worker scripts can be any javascript file that can run in the Worker context. There are some limitations in this context, most significantly, there is no access to the document or window objects. [Mozilla's docs](https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API/Using_web_workers) are a good place to start if you are not familar with workers.

Data from the queued job will be passed to the script using the `postMessage` method from the modules main script. You can listen for this message in your script and post a response like so:

```
onmessage = function(e) {
  console.log('Message received from main script');
  var workerResult = 'Result: ' + (e.data[0] * e.data[1]);
  console.log('Posting message back to main script');
  postMessage(workerResult);
}
```

The main script will send whatever the worker posts back to the server to be saved in the cache. 

## debugging

The main script uses [debug](https://github.com/visionmedia/debug#browser-support) for logging, to turn it on enter `localStorage.debug = '*'` in the browser.
