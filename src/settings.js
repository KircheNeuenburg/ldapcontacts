import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

import Settings from './Settings'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#ldapcontacts_settings',
	render: h => h(Settings)
})
