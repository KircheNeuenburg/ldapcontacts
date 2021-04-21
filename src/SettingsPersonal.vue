<template>
	<div id="ldapcontacts_settings-personal" class="section">
		<h2>{{ text.contacts }}</h2>
		<div v-if="loading" class="loading" />
		<div v-else>
			<label for="ldapcontacts-order-by">{{ text.orderContactsBy }}</label>
			<select id="ldapcontacts-order-by" v-model="orderBy" @change="saveSettingsPersonal('order_by', orderBy)">
				<option />
				<option v-for="attribute in userLdapAttributes"
					:key="attribute.name"
					:selected="attribute.name === orderBy"
					:value="attribute.name">
					{{ attribute.label }}
				</option>
			</select>
		</div>

		<div class="msg-container">
			<div v-for="(message, index) in messageList"
				:key="index"
				class="msg"
				:class="message.type">
				{{ message.message }}
			</div>
		</div>
	</div>
</template>

<script>
import Axios from 'axios'
Axios.defaults.headers.common.requesttoken = OC.requestToken

export default {
	name: 'SettingsPersonal',
	components: {},
	data() {
		return {
			loading: true,
			text: {
				contacts: t('ldapcontacts', 'Contacts'),
				orderContactsBy: t('ldapcontacts', 'Order Contacts by:'),
			},
			baseUrl: this.generateUrl('/apps/ldapcontacts'),
			orderBy: '',
			userLdapAttributes: {},
			messageList: [],
		}
	},
	beforeMount() {
		this.loadSettings()
		this.loadSettingsPersonal()
	},
	methods: {
		loadSettings() {
			const self = this

			Axios.get(self.baseUrl + '/settings')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.userLdapAttributes = response.data.data.userLdapAttributes
					} else {
						console.debug('/settings error')
						self.displayMessage(response.data.message, response.data.status)
					}
				})
				.catch(function(response) {
					console.debug('/settings failed')
					console.debug(response)
				})
		},
		loadSettingsPersonal() {
			const self = this

			Axios.get(self.baseUrl + '/settings/personal/order_by')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.orderBy = response.data.data
						self.loading = false
					} else {
						console.debug('/settings/personal/order_by error')
						self.displayMessage(response.data.message, response.data.status)
					}
				})
				.catch(function(response) {
					console.debug('/settings/personal/order_by failed')
					console.debug(response)
				})
		},
		saveSettingsPersonal(key, value) {
			const self = this

			const data = {
				key,
				value,
			}

			Axios.post(self.baseUrl + '/settings/personal', data)
				.then(function(response) {
					if (response.data.status === 'success') {
						console.debug('/settings/personal success')
						self.displayMessage(response.data.message, response.data.status)
					} else {
						console.debug('/settings/personal error')
						self.displayMessage(response.data.message, response.data.status)
					}
				})
				.catch(function(response) {
					console.debug('/settings/personal failed')
					console.debug(response)
				})
		},
		displayMessage(message, type) {
			const self = this
			const messageObject = {
				message,
				type,
			}

			self.messageList.push(messageObject)
			setTimeout(function() { self.messageList.pop() }, 3000)
		},
	},
}
</script>
