import {Radios} from "govuk-frontend";
import nodeListForEach from "./node-list";

function radiosOverride() {
    var oldInit = Radios.prototype.init;
    Radios.prototype.init = function() {
        nodeListForEach(this.$inputs, function($input) {
            var targets = $input.getAttribute('data-aria-hide-controls');
            var activeTargets = [];

            if (!targets) {
                return
            }

            targets = JSON.parse(targets);

            for(var i=0; i<targets.length; i++) {
                if (document.querySelector('#' + targets[i])) {
                    activeTargets.push(targets[i]);
                }
            }

            if (activeTargets.length === 0) {
                return;
            }

            $input.setAttribute('aria-controls', activeTargets.join(' '));
        })

        oldInit.apply(this);
    }

    var syncConditionalRevealWithInputState = Radios.prototype.syncConditionalRevealWithInputState;
    Radios.prototype.syncConditionalRevealWithInputState = function ($input) {
        var ariaControls = $input.getAttribute('aria-controls');

        if (!ariaControls) {
            return;
        }

        ariaControls = ariaControls.split(' ');

        // if the hide attribute is not present, we must be using the original conditional method
        if (!$input.getAttribute('data-aria-hide-controls')) {
            syncConditionalRevealWithInputState.apply(this, [$input]);
            return;
        }

        for(var i=0; i<ariaControls.length; i++) {
            var $target = document.querySelector('#' + ariaControls[i]);

            $target = $target.parentElement;
            var inputIsChecked = $input.checked;
            $target.classList.toggle('govuk-radios__conditional--hidden', inputIsChecked);
        }
    }

    Radios.prototype.syncAllConditionalReveals = function () {
        var allInputsUnselected = true;
        for(var i=0; i<this.$inputs.length; i++) {
            if (this.$inputs[i].checked) {
                allInputsUnselected = false;
                break;
            }
        }

        if (allInputsUnselected) {
            // If all inputs are unselected, then set the default state
            // (i.e. look for data-hidden-by-default on the targets)
            nodeListForEach(this.$inputs, this.setDefaultStateForTargets.bind(this));
        } else {
            // Otherwise set the state that would be determined from the selection
            nodeListForEach(this.$inputs, this.syncConditionalRevealWithInputState.bind(this))
        }
    }

    Radios.prototype.setDefaultStateForTargets = function($input) {
        if (!$input.getAttribute('data-aria-hide-controls')) {
            return;
        }

        var ariaControls = $input.getAttribute('aria-controls');

        if (!ariaControls) {
            return;
        }

        ariaControls = ariaControls.split(' ');

        for(var i=0; i<ariaControls.length; i++) {
            var $target = document.querySelector('#' + ariaControls[i]);

            $target = $target.parentElement;

            var hiddenByDefault = $target.dataset.hiddenByDefault;

            if (hiddenByDefault !== undefined) {
                $target.classList.toggle('govuk-radios__conditional--hidden', true);
            }
        }
    }
}

export default radiosOverride;