<template>
	<div>
		<div v-if="loading" class="loading" />

		<div v-else-if="editMode" id="editContactDetails">
			<table v-if="contactDetails.uuid">
				<tbody>
					<tr v-for="attribute in settings.userLdapAttributes" :key="attribute.name">
						<td>{{ attribute.label }}</td>
						<td>
							<input v-model="contactDetails.ldapAttributes[ attribute.name ]">
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2" class="save-button-wrapper">
							<button @click="updateOwnContactDetails">
								{{ text.save }}
							</button>
							<i v-if="updatingOwnContactDetails" class="icon-loading" />
						</td>
					</tr>
				</tfoot>
			</table>

			<h2 v-else>
				{{ text.errorOccured }}
			</h2>
		</div>

		<div v-else id="contactDetails">
			<table v-if="contactDetails.uuid">
				<tbody>
					<tr v-for="attribute in settings.userLdapAttributes" :key="attribute.name">
						<td>{{ attribute.label }}</td>
						<td>
							<p :ref="attribute.name">
								{{ contactDetails.ldapAttributes[ attribute.name ] }}
							</p>
						</td>
						<td>
							<Actions>
								<ActionButton icon="icon-clippy" @click="copyToClipboard(contactDetails.ldapAttributes[ attribute.name ])">
									{{ text.copyToClipboard }}
								</ActionButton>
							</Actions>
						</td>
					</tr>

					<tr>
						<td>{{ text.groups }}</td>
						<td>
							<p v-for="group in contactDetails.groups" :key="group.uuid">
								{{ group.title }}
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<h3 v-else>
				{{ text.selectContactFromTheLeftToSeeDetails }}
			</h3>
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
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import $ from 'jquery'
import Axios from 'axios'
Axios.defaults.headers.common.requesttoken = OC.requestToken

export default {
	components: {
		Actions,
		ActionButton
	},
	props: {
		contactDetails: {
			title: 'contactDetails',
			type: Object,
			default: function() { return {} }
		},
		editMode: {
			title: 'editMode',
			type: Number,
			default: function() { return 0 }
		}
	},
	data() {
		return {
			loading: true,
			text: {
				firstName: t('ldapcontacts', 'First Name'),
				lastName: t('ldapcontacts', 'Last Name'),
				selectContactFromTheLeftToSeeDetails: t('ldapcontacts', 'Select a contact from the left to see details'),
				copyToClipboard: t('ldapcontacts', 'Copy to clipboard'),
				groups: t('ldapcontacts', 'Groups'),
				errorOccured: t('ldapcontacts', 'An error occured, please try again later'),
				save: t('ldapcontacts', 'Save')
			},
			baseUrl: OC.generateUrl('/apps/ldapcontacts'),
			settings: {},
			updatingOwnContactDetails: false,
			messageList: []
		}
	},
	computed: {},
	watch: {},
	beforeMount() {
		this.loadSettings()
	},
	methods: {
		loadSettings() {
			var self = this

			Axios.get(self.baseUrl + '/settings')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.settings = response.data.data
						self.loading = false
					} else {
						console.debug('/settings error')
					}
				})
				.catch(function(response) {
					console.debug('/settings failed')
					console.debug(response)
				})
		},
		copyToClipboard(text) {
			var input = $(document.createElement('input'))
			$('body').append(input)
			input.val(text).select()
			document.execCommand('copy')
			input.remove()
		},
		displayMessage(message, type) {
			var self = this
			var messageObject = {
				message: message,
				type: type
			}

			self.messageList.push(messageObject)
			setTimeout(function() { self.messageList.pop() }, 3000)
		},
		updateOwnContactDetails() {
			var self = this
			if (self.updatingOwnContactDetails) return
			else self.updatingOwnContactDetails = true

			Axios.post(self.baseUrl + '/own', { data: self.contactDetails })
				.then(function(response) {
					self.displayMessage(response.data.message, response.data.status)
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
					console.debug('/own post failed')
					console.debug(response)
				})
				.finally(function() {
					self.updatingOwnContactDetails = false
				})
		}
	}
}
</script>
