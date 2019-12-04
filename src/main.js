import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

import Main from './Main'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

export default new Vue({
	el: '#app',
	render: h => h(Main)
})
