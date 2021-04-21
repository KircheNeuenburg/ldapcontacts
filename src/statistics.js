import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import Statistics from './Statistics'

require('../css/statistics.scss')

Vue.prototype.generateUrl = generateUrl
Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#ldapcontacts_statistics',
	render: h => h(Statistics),
})
