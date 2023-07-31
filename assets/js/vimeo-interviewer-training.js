function InterviewerTrainingVideoWorkflow ($module) {
    this.$trainingRecordId = $module.getAttribute('data-training-record');
    this.$iframe = $module.querySelector('iframe');
}

InterviewerTrainingVideoWorkflow.prototype.init = function () {
    let context = this;
    if (!this.$trainingRecordId) return;

    const TRANSITION_START = 'start';
    const TRANSITION_COMPLETE = 'complete';

    let $player = new Vimeo.Player(this.$iframe);

    $player.on('play', function() {
        context.updateTrainingStatus(TRANSITION_START)
            .then(context.handleResponse);
    });
    $player.on('ended', function() {
        context.updateTrainingStatus(TRANSITION_COMPLETE)
            .then(context.handleResponse);
    });
}

InterviewerTrainingVideoWorkflow.prototype.handleResponse = function(response) {
    if (response.status !== 200) return;

    response.json().then(data => {
        let $statusTag = document.querySelector('#training-module-status .govuk-tag');
        $statusTag.textContent = data['newState'].text
        $statusTag.classList.remove('govuk-tag--blue', 'govuk-tag--green', 'govuk-tag--orange');
        $statusTag.classList.add('govuk-tag--' + data['newState'].color)
    });
}

InterviewerTrainingVideoWorkflow.prototype.updateTrainingStatus = async function(transition) {
    return await fetch(document.documentURI + "/" + transition, {
        method: "POST",
    });
}

function initInterviewerTraining() {
    const $videoContainers = document.querySelectorAll('.video-container')
    if ($videoContainers) {
        for(let i = 0; i < $videoContainers.length; i++) {
            new InterviewerTrainingVideoWorkflow($videoContainers[i]).init();
        }
    }
}

export {
    initInterviewerTraining,
}
