import './scss/app.scss'

import {ghostFrontendInitAll} from '@ghost/govuk-frontend-bundle/assets/js/ghost-frontend'
import {ghostCoreInitAll} from '@ghost/govuk-core-bundle/assets/js/ghost-core'
import {initialise as initialiseAutoTotal} from './js/table-auto-total'

ghostFrontendInitAll()
ghostCoreInitAll()

initialiseAutoTotal()
