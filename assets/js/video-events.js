function VideoEventReporter (module) {
    if (module.getAttribute('data-events') !== 'true') return;
    this.iframe = module.querySelector('iframe');
    this.videoId = new URL(this.iframe.getAttribute('src')).pathname;
}

VideoEventReporter.prototype.init = function () {
    let context = this;
    if (!this.videoId) return;

    let player = new Vimeo.Player(this.iframe);

    const VIDEO_PLAY = 'play';
    const VIDEO_ENDED = 'ended';

    player.on('play', function(eventData) {
        context
            .reportVideoEvent(VIDEO_PLAY, eventData)
            .then((response) => context.handleResponse(response));
    });
    player.on('ended', function(eventData) {
        context
            .reportVideoEvent(VIDEO_ENDED, eventData)
            .then((response) => context.handleResponse(response));
    });
}

VideoEventReporter.prototype.handleResponse = function(response) {
    if (response.ok) {
        response.json().then(data => this.iframe.dispatchEvent(new CustomEvent('videoEventReporter.response', {response: data})));
    } else {
        // an error occurred
    }
}

VideoEventReporter.prototype.reportVideoEvent = async function(event, additionalData) {
    return await fetch('/ajax/video-event', {
        method: "POST",
        body: Object.toFormData({
            videoId: this.videoId,
            urlPath: new URL(document.URL).pathname,
            type: event,
            additionalData: additionalData ?? {},
        }),
    });
}

function initVideoEventReporter() {
    const $videoContainers = document.querySelectorAll('.video-container')
    if ($videoContainers) {
        for(let i = 0; i < $videoContainers.length; i++) {
            new VideoEventReporter($videoContainers[i]).init();
        }
    }
}

export {
    initVideoEventReporter,
}
