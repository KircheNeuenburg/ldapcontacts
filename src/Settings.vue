<template>
	<div id="ldapcontacts-settings" class="section" app-name="ldapcontacts">
		<div v-if="loading" class="loading" />

		<div>
			<h2>{{ text.ldapContacts }}</h2>

			<h3>{{ text.ldapAttributes }}</h3>
			<h6>{{ text.userLdapAttributes }}</h6>
			<table>
				<thead>
					<tr>
						<th><b>{{ text.ldapAttribute }}</b></th>
						<th><b>{{ text.label }}</b></th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(attribute, index) in settings.userLdapAttributes" :key="index">
						<td><input v-model="attribute.name" :placeholder="text.ldapAttribute"></td>
						<td><input v-model="attribute.label" :placeholder="text.label"></td>
						<td>
							<Actions>
								<ActionButton icon="icon-delete" @click="removeAttribute(index)">
									{{ text.delete }}
								</ActionButton>
							</Actions>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td>
							<Actions>
								<ActionButton icon="icon-add" @click="addAttribute">
									{{ text.addAttribute }}
								</ActionButton>
							</Actions>
						</td>
						<td class="save-button-wrapper">
							<button @click="saveLdapAttributes">
								{{ text.saveAttributes }}
							</button>
							<i v-if="savingLdapAttributes" class="icon-loading" />
						</td>
						<td />
					</tr>
				</tfoot>
			</table>

			<div class="msg-container">
				<div v-for="(message, index) in messageList"
					:key="index"
					class="msg"
					:class="message.type">
					{{ message.message }}
				</div>
			</div>

			<div id="hideUsers" class="container">
				<h3>{{ text.hidden_users }}</h3>
				<div class="search-container">
					<span class="search">
						<input v-model="hiddenUserSearchInput"
							:placeholder="text.hideUser"
							type="search"
							@keyup="searchVisibleUsers">
						<span class="abort" @click="abortSearch('group')" />
					</span>
					<div class="search-suggestion-container">
						<div v-for="user in hideUserSearchSuggestions"
							:key="user.uuid"
							class="suggestion"
							@click="hideUser(user.uuid)">
							{{ user.title }}
						</div>
					</div>
				</div>
				<div class="hidden-entity-container">
					<span v-for="user in hiddenUsersList" :key="user.uuid" class="hidden-entity">
						<span class="name">{{ user.title }}</span>
						<span class="remove" @click="unhideUser(user.uuid)">X</span>
					</span>
					<b v-if="hiddenUsersList.length < 1">{{ text.noUsersHidden }}</b>
				</div>
			</div>

			<div id="hideGroups" class="container">
				<h3>{{ text.hidden_groups }}</h3>
				<div class="search-container">
					<span class="search">
						<input v-model="hiddenGroupSearchInput"
							:placeholder="text.hideGroup"
							type="search"
							@keyup="searchVisibleGroups">
						<span class="abort" @click="abortSearch('user')" />
					</span>
					<div class="search-suggestion-container">
						<div v-for="group in hideGroupSearchSuggestions"
							:key="group.uuid"
							class="suggestion"
							@click="hideGroup(group.uuid)">
							{{ group.title }}
						</div>
					</div>
				</div>
				<div class="hidden-entity-container">
					<span v-for="group in hiddenGroupsList" :key="group.uuid" class="hidden-entity">
						<span class="name">{{ group.title }}</span>
						<span class="remove" @click="unhideGroup(group.uuid)">X</span>
					</span>
					<b v-if="hiddenGroupsList.length < 1">{{ text.noGroupsHidden }}</b>
				</div>
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
	name: 'Settings',
	components: {
		ActionButton,
		Actions,
	},
	data() {
		return {
			loading: true,
			loadingFunctionsDone: 0,
			savingLdapAttributes: false,
			baseUrl: this.generateUrl('/apps/ldapcontacts'),
			text: {
				ldapContacts: t('ldapcontacts', 'LDAP Contacts'),
				userLdapAttributes: t('ldapcontacts', 'Define LDAP attributes the users can see and edit'),
				ldapAttribute: t('ldapcontacts', 'LDAP Attribute'),
				ldapAttributes: t('ldapcontacts', 'LDAP Attributes'),
				label: t('ldapcontacts', 'Label'),
				addAttribute: t('ldapcontacts', 'Add Attribute'),
				hideUser: t('ldapcontacts', 'Hide User'),
				hideGroup: t('ldapcontacts', 'Hide Group'),
				hidden_users: t('ldapcontacts', 'Hidden Users'),
				hidden_groups: t('ldapcontacts', 'Hidden Groups'),
				noUsersHidden: t('ldapcontacts', 'No users are hidden'),
				noGroupsHidden: t('ldapcontacts', 'No groups are hidden'),
				errorOccured: t('ldapcontacts', 'An error occured, please try again later'),
				saveAttributes: t('ldapcontacts', 'Save Attributes'),
				delete: t('ldapcontacts', 'Delete'),
			},
			settings: {
				userLdapAttributes: {},
			},
			hiddenUserSearchInput: '',
			hiddenGroupSearchInput: '',
			hideUserSearchSuggestions: {},
			hideGroupSearchSuggestions: {},
			visibleUsersList: {},
			hiddenUsersList: {},
			visibleGroupsList: {},
			hiddenGroupsList: {},
			messageList: [],
		}
	},
	computed: {},
	beforeMount() {
		this.load()
	},
	methods: {
		load(usersOnly = false) {
			// have at least one user attribute visible
			if (!usersOnly && this.objectIsEmpty(this.settings.userLdapAttributes)) this.addAttribute()
			// load current settings
			if (!usersOnly) this.loadSettings().then(this.checkLoadingDone)
			this.loadUsers().then(this.checkLoadingDone)
			this.loadGroups().then(this.checkLoadingDone)
			this.loadHiddenUsers().then(this.checkLoadingDone)
			this.loadHiddenGroups().then(this.checkLoadingDone)
		},
		checkLoadingDone(response) {
			if (typeof response === 'undefined') return

			if (response.data.status === 'success') {
				this.loadingFunctionsDone++
				if (this.loadingFunctionsDone >= 4) {
					this.loading = false
				}
			}
		},
		addAttribute() {
			const newAttribute = {
				name: '',
				label: '',
			}
			this.$set(this.settings.userLdapAttributes, this.getHighestIndex(this.settings.userLdapAttributes) + 1, newAttribute)
		},
		getHighestIndex(obj) {
			if (this.objectIsEmpty(obj)) return -1
			else return Number(Object.keys(obj).reduce((a, b) => obj[a] > obj[b] ? a : b))
		},
		objectIsEmpty(obj) {
			return Object.keys(obj).length === 0
		},
		removeAttribute(index) {
			this.$delete(this.settings.userLdapAttributes, index)
		},
		hideUser(userId) {
			this.hideEntity('user', userId)
		},
		hideGroup(groupId) {
			this.hideEntity('group', groupId)
		},
		hideEntity(type, id) {
			const self = this

			Axios.get(self.baseUrl + '/admin/hide/' + type + '/' + id)
				.then(function(response) {
					if (response.data.status === 'success') {
						self.load(true)
					}

					self.displayMessage(response.data.message, response.data.status)
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})

			this.abortSearch(type)
		},
		unhideUser(userId) {
			this.unhideEntity('user', userId)
		},
		unhideGroup(groupId) {
			this.unhideEntity('group', groupId)
		},
		unhideEntity(type, uuid) {
			const self = this
			Axios.get(self.baseUrl + '/admin/show/' + type + '/' + uuid)
				.then(function(response) {
					if (response.data.status === 'success') {
						self.load(true)
					}

					self.displayMessage(response.data.message, response.data.status)
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})

			this.abortSearch(type)
		},
		searchVisibleUsers() {
			this.search('user')
		},
		searchVisibleGroups() {
			this.search('group')
		},
		search(type) {
			const self = this
			let searchTerms = []
			let searchList = {}
			const searchResults = {}

			switch (type) {
			case 'user':
				self.hideUserSearchSuggestions = {}
				searchTerms = this.hiddenUserSearchInput.split(' ')
				searchList = self.visibleUsersList
				break
			case 'group':
				self.hideGroupSearchSuggestions = {}
				searchTerms = this.hiddenGroupSearchInput.split(' ')
				searchList = self.visibleGroupsList
				break
			default:
				return
			}

			// filter out empty search terms
			const temp = []
			for (const term of searchTerms) term && temp.push(term.toLowerCase())
			searchTerms = temp

			// perform the search
			if (searchTerms.length > 0) {
				$.each(searchList, function(i, item) {
					let hits = 0
					$.each(searchTerms, function(i, term) {
						switch (type) {
						case 'user':
							$.each(item.ldapAttributes, function(attribute, attributeValue) {
								if (attributeValue.toLowerCase().includes(term)) {
									hits++
									return false
								}
							})
							break
						case 'group':
							if (item.title.toLowerCase().includes(term)) hits++
							break
						}
					})
					if (hits >= searchTerms.length) searchResults[item.uuid] = item
				})
			}

			switch (type) {
			case 'user':
				self.hideUserSearchSuggestions = searchResults
				break
			case 'group':
				self.hideGroupSearchSuggestions = searchResults
				break
			}
		},
		abortSearch(type) {
			if (type === 'user') {
				this.hiddenUserSearchInput = ''
			} else {
				this.hiddenGroupSearchInput = ''
			}

			this.search(type)
		},
		loadSettings() {
			const self = this

			return Axios.get(self.baseUrl + '/settings')
				.then(function(response) {
					if (response.data.status === 'success') {
						$.each(response.data.data, function(settingKey, settingValue) {
							self.settings[settingKey] = settingValue
						})
						// have at least one user attribute visible
						if (self.objectIsEmpty(self.settings.userLdapAttributes)) self.addAttribute()
					} else {
						self.displayMessage(response.data.message, response.data.status)
					}

					return response
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
		},
		loadUsers() {
			const self = this

			return Axios.get(self.baseUrl + '/load')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.visibleUsersList = response.data.data
					} else {
						self.displayMessage(response.data.message, response.data.status)
					}

					return response
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
		},
		loadHiddenUsers() {
			const self = this

			return Axios.get(self.baseUrl + '/load/hidden')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.hiddenUsersList = response.data.data
					} else {
						self.displayMessage(response.data.message, response.data.status)
					}

					return response
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
		},
		loadGroups() {
			const self = this

			return Axios.get(self.baseUrl + '/groups')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.visibleGroupsList = response.data.data
					} else {
						self.displayMessage(response.data.message, response.data.status)
					}

					return response
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
		},
		loadHiddenGroups() {
			const self = this

			return Axios.get(self.baseUrl + '/groups/hidden')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.hiddenGroupsList = response.data.data
					} else {
						self.displayMessage(response.data.message, response.data.status)
					}

					return response
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
		},
		saveLdapAttributes() {
			const self = this
			if (self.savingLdapAttributes) return
			else self.savingLdapAttributes = true

			Axios.post(self.baseUrl + '/settings/update', { settings: { userLdapAttributes: self.settings.userLdapAttributes } })
				.then(function(response) {
					self.displayMessage(response.data.message, response.data.status)
				})
				.catch(function(response) {
					self.displayMessage(self.text.errorOccured, 'error')
				})
				.finally(function() {
					self.savingLdapAttributes = false
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
