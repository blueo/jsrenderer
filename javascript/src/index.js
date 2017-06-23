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

function checkForJob() {
  fetch('/admin/pages/getJsRenderJob', {
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
  })
  .then(response => response.json())
  .then((job) => {
    const workers = [];
    if (job) {
      const { Worker: workerpath } = job;
      const processingJob = new Worker(workerpath);
      workers.push(processingJob);
      processingJob.postMessage(job);
      processingJob.onmessage = (...args) => saveHtmlToCache.apply(this, args).then(checkForJob);
    } else {
      console.log('No render job found')
    }
  });
}


if (global.window.Worker) {
  checkForJob();
}
