import './js/extensions';
import {ghostInitAll} from '../bundles/Ghost/GovUkFrontendBundle/Resources/assets/js/ghost-frontend';
import {initInterviewerTraining} from './js/vimeo-interviewer-training';
import {initViewportSizeMonitor} from './js/viewport-size-monitor';
import {initVideoEventReporter} from './js/video-events';

import './scss/govuk-frontend.scss'
import './scss/app.scss'

ghostInitAll();
initInterviewerTraining();
initViewportSizeMonitor();
initVideoEventReporter();
