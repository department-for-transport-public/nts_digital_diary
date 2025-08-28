import './js/extensions';

import {ghostFrontendInitAll} from '@ghost/govuk-frontend-bundle/assets/js/ghost-frontend'
import {ghostCoreInitAll} from '@ghost/govuk-core-bundle/assets/js/ghost-core'

import {initHouseholdCompareSync} from "./js/household-compare-sync";
import {initInterviewerTraining} from './js/vimeo-interviewer-training';
import {initViewportSizeMonitor} from './js/viewport-size-monitor';

import './js/show-password';
import stepByStep from './js/step-by-step';

import {initVideoEventReporter} from './js/video-events';
// import './scss/old-govuk-frontend.scss'
import './scss/app.scss'
import './bootstrap';

ghostFrontendInitAll()
ghostCoreInitAll()
initHouseholdCompareSync();
initInterviewerTraining();
initViewportSizeMonitor();
initVideoEventReporter();
stepByStep();