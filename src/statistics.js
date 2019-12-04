import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

import Statistics from './Statistics'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#ldapcontacts_statistics',
	render: h => h(Statistics)
})
