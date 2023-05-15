<template>
	<NcContent :class="{'icon-loading': loading}" app-name="ldapcontacts">
		<NcAppNavigation>
			<div id="navigation-header">
				<div v-if="groupsLoading || loading" class="loading" />

				<div v-else>
					<div class="search-container">
						<input v-model="contactSearchInput"
							:placeholder="text.searchUsers"
							type="search"
							@keyup="updateSearch">
						<span v-if="contactSearchInput" class="abort" @click="abortSearch" />
					</div>

					<select v-model="selectedGroupId" class="select-group" @change="updateSearch">
						<option value="">
							{{ text.all }}
						</option>
						<option v-for="group in availabeGroupsList"
							:key="group.uuid"
							:item="group"
							:value="group.uuid">
							{{ group.title }}
						</option>
					</select>
				</div>
			</div>

			<template #list>
				<li v-if="loading">
					<div class="icon-loading" />
				</li>
				<NcAppNavigationItem
					v-for="contact in visibleContactsList"
					v-else
					:key="contact.uuid"
					:item="contact"
					:title="contact.title"
					:icon="contact.avatar"
					:class="{'active': contact.uuid === activeContact.uuid}"
					@click="showContactDetails(contact)" />
			</template>

			<template #footer>
				<NcAppNavigationSettings>
					<div v-if="loadingOwnContact" class="icon-loading" />
					<button v-else class="has-input-icon-wrapper" @click="editOwnData">
						{{ text.editOwnContactDetails }}
					</button>
				</NcAppNavigationSettings>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<div v-if="loading" class="icon-loading" />
			<ContactDetails v-else
				v-bind="{
					contactDetails: activeContact,
					editMode: editActiveContact
				}"
				@attribute-updated="ldapAttributeUpdated" />
		</NcAppContent>
	</NcContent>
</template>

<script>
import ContactDetails from './components/ContactDetails'
import NcContent from '@nextcloud/vue/dist/Components/NcContent'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem'
import NcAppNavigationSettings from '@nextcloud/vue/dist/Components/NcAppNavigationSettings'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField'
// import CloseIcon from 'vue-material-design-icons/Close.vue'
import SearchIcon from 'vue-material-design-icons/AccountSearchOutline.vue'
import $ from 'jquery'
import Axios from 'axios'
Axios.defaults.headers.common.requesttoken = OC.requestToken

export default {
	name: 'Main',
	components: {
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationSettings,
		ContactDetails,
	},
	data() {
		return {
			loading: true,
			groupsLoading: true,
			loadingOwnContact: true,
			savingOwnContactDetails: false,
			baseUrl: this.generateUrl('/apps/ldapcontacts'),
			activeContact: {},
			availabeContactsList: {},
			visibleContactsList: {},
			contactSearchInput: '',
			availabeGroupsList: {},
			selectedGroupId: '',
			ownContactId: '',
			editActiveContact: 0,
			text: {
				editOwnContactDetails: t('ldapcontacts', 'Edit own contact details'),
				all: t('ldapcontacts', 'All'),
				searchUsers: t('ldapcontacts', 'Search Users'),
			},
		}
	},
	computed: {},
	beforeMount() {
		this.fetchContacts()
		this.fetchGroups()
		this.fetchOwnContact()
	},
	methods: {
		fetchContacts() {
			const self = this

			Axios.get(self.baseUrl + '/load')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.visibleContactsList = self.availabeContactsList = response.data.data
						self.loading = false
					} else {
						console.debug('/load error')
					}
				})
				.catch(function(response) {
					console.debug('/load failed')
					console.debug(response)
				})
		},
		fetchOwnContact() {
			const self = this

			Axios.get(self.baseUrl + '/own')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.ownContactId = response.data.data.uuid
						self.loadingOwnContact = false
					} else {
						console.debug('/own error')
					}
				})
				.catch(function(response) {
					console.debug('/own failed')
					console.debug(response)
				})
		},
		fetchGroups() {
			const self = this

			Axios.get(self.baseUrl + '/groups')
				.then(function(response) {
					if (response.data.status === 'success') {
						self.availabeGroupsList = response.data.data
						self.groupsLoading = false
					} else {
						console.debug('/groups error')
					}
				})
				.catch(function(response) {
					console.debug('/groups failed')
					console.debug(response)
				})
		},
		showContactDetails(contact) {
			this.activeContact = contact
			this.editActiveContact = 0
		},
		abortSearch() {
			this.contactSearchInput = ''
			this.updateSearch()
		},
		editOwnData() {
			const self = this
			self.activeContact = {}

			if (!self.editActiveContact) {
				$.each(self.availabeContactsList, function(i, contact) {
					if (self.ownContactId === contact.uuid) {
						self.activeContact = contact
						return false
					}
				})
			}

			self.editActiveContact ^= 1
		},
		updateSearch() {
			const self = this
			self.visibleContactsList = {}
			let preselectedByGroup = {}

			/** filter by group **/
			if (self.selectedGroupId === '') preselectedByGroup = self.availabeContactsList
			else {
				$.each(self.availabeContactsList, function(i, contact) {
					$.each(contact.groups, function(j, group) {
						if (self.selectedGroupId === group.uuid) {
							preselectedByGroup[contact.uuid] = contact
							return false
						}
					})
				})
			}

			/** filter by search input **/
			// split search terms
			let searchTerms = self.contactSearchInput.split(' ')
			// filter out empty ones
			const temp = []
			for (const term of searchTerms) term && temp.push(term.toLowerCase())
			searchTerms = temp

			// perform the search
			if (searchTerms.length < 1) self.visibleContactsList = preselectedByGroup
			else {
				$.each(preselectedByGroup, function(i, contact) {
					let hits = 0
					$.each(searchTerms, function(i, term) {
						$.each(contact.ldapAttributes, function(attribute, attributeValue) {
							if (attributeValue.toLowerCase().includes(term)) {
								hits++
								return false
							}
						})
					})
					if (hits >= searchTerms.length) self.visibleContactsList[contact.uuid] = contact
				})
			}
		},
		ldapAttributeUpdated(name, value) {
			this.activeContact.ldapAttributes[name] = value
		},
	},
}
</script>
