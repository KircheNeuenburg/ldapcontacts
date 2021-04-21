import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import Main from './Main'

require('../css/style.scss')

Vue.prototype.generateUrl = generateUrl
Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#app',
	render: h => h(Main),
})
