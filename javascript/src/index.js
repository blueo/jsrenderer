import often from 'often';
import debug from 'debug';
import energy from 'energy';

const log = debug('jsrenderer');
const emitter = energy();
/**
 * JsRenderer Control module
 * this module is responsible for checking for new render jobs
 * submitting those jobs for processing, and saving their results
 * back into the tempate cache
 */


/**
 * Save worker results back into CMS tempate cache
 */
function saveHtmlToCache({data: { html, key } }) {
  return fetch('/admin/pages/storeRenderedTemplate', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      method: 'POST',
      body: JSON.stringify({ html: JSON.stringify(html), key }),
    })
    .then(response => response.json());
}

function checkForJob(control) {
  log('checking for job');
  return fetch('/admin/pages/getJsRenderJob', {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
  })
  .then(response => response.json())
  .then((job) => {
    log('got job response');
    const workers = [];
    if (job) {
      log('render job found', job);
      const { Worker: workerpath } = job;
      try {
        const processingJob = new Worker(workerpath);
        workers.push(processingJob);
        processingJob.postMessage(job);
        processingJob.onmessage = (...args) => saveHtmlToCache
          .apply(this, args)
          .then(() => log('finished saving render'))
          .error(e => log('error saving render', e));
        processingJob.onerror = (e) => {
          log('worker error', e);
          control.stop();
        }
      } catch (e) {
        log('error starting worker', e);
        control.stop();
      }
    } else {
      log('no render job found');
    }
  });
}


if (global.window.Worker) {
  const heartbeat = often(function() {
    emitter.emit('heartbeat', this);
  }).wait(30000).start();

  emitter.on('heartbeat', control => checkForJob(control));
} else {
  log('browser does not support web workers');
}
