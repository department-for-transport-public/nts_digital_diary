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

            if (!$target) {
                continue;
            }

            $target = $target.parentElement;
            var inputIsChecked = $input.checked;
            $target.classList.toggle('govuk-radios__conditional--hidden', inputIsChecked);
        }
    }
}

export default radiosOverride;