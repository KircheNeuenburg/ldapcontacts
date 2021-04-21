import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

import SettingsPersonal from './SettingsPersonal'

Vue.prototype.generateUrl = generateUrl
Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#ldapcontacts_settings-personal',
	render: h => h(SettingsPersonal),
})
