import '@govuk-prototype-kit/step-by-step/javascripts/step-by-step-navigation'
import '@govuk-prototype-kit/step-by-step/javascripts/step-by-step-polyfills'
import nodeListForEach from "./node-list";

function stepByStep() {
    let $stepNavs = document.querySelectorAll('div[data-module="appstepnav"]');
    nodeListForEach($stepNavs, function ($stepNav) {
        new GOVUK.Modules.AppStepNav($stepNav).init();
    });
}

GOVUK.Modules.AppStepNav.prototype.addButtonstoSteps = function () {}
GOVUK.Modules.AppStepNav.prototype.addShowHideToggle = function () {
    let $toggleLinks = document.querySelectorAll('.app-step-nav__toggle-link')
    nodeListForEach($toggleLinks, function($link) {
        $link.classList.remove('govuk-!-display-none')
    })
}

GOVUK.Modules.AppStepNav.prototype.bindToggleForSteps = function (stepNavTracker) {
    var that = this
    var togglePanels = this.$module.querySelectorAll('.app-step-nav__title')

    for (var i = 0; i < togglePanels.length; i++) {
        togglePanels[i].addEventListener('click', function (event) {
            var stepView = new that.StepView(this.closest('.app-step-nav__step'), that.$module)
            stepView.toggle()

            var stepIsOptional = this.parentNode.hasAttribute('data-optional')
            var toggleClick = new that.StepToggleClick(event, stepView, stepNavTracker, stepIsOptional, that.$module.stepNavSize)
            toggleClick.trackClick()

            that.setShowHideAllText()
            that.rememberStepState(this.closest('.app-step-nav__step'))
        })
    }
}


export default stepByStep;