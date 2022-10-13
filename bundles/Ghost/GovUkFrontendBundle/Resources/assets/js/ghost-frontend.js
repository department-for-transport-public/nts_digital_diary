import {initAll} from 'govuk-frontend'

import doubleSubmitPrevention from './double-submit-prevention'
import radiosOverride from './radios'
import initSessionReminder from './session-reminder'

import './show-password'

function ghostInitAll(options) {
    radiosOverride()
    initAll(options)

    // Set the options to an empty object by default if no options are passed.
    options = typeof options !== 'undefined' ? options : {}

    // Allow the user to initialise GOV.UK Frontend in only certain sections of the page
    // Defaults to the entire document if nothing is set.
    const scope = typeof options.scope !== 'undefined' ? options.scope : document

    const $showPasswords = scope.querySelectorAll('[data-module="show-password"]')
    const ShowPassword = window.GOVUK.Modules.ShowPassword
    if ($showPasswords) {
        for(let i = 0; i < $showPasswords.length; i++) {
            new ShowPassword($showPasswords[i]).init()
        }
    }

    doubleSubmitPrevention()
    initSessionReminder()
}

export {
    ghostInitAll,
}