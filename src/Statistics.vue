<template>
	<div id="ldapcontacts_statistics">
		<div v-if="loading" class="icon-loading" />

		<div v-for="chartWrapper in chartWrapperList" :key="chartWrapper.id" class="stat">
			<h2 class="title">
				{{ chartWrapper.title }}
			</h2>

			<canvas :id="chartWrapper.id" />

			<h3 class="total">
				{{ text.total }} {{ chartWrapper.total }}
			</h3>
		</div>
	</div>
</template>

<script>
import $ from 'jquery'
import Axios from 'axios'
Axios.defaults.headers.common.requesttoken = OC.requestToken

const Chart = require('./Chart.min.js')

export default {
	name: 'Statistics',
	components: {},
	data() {
		return {
			loading: true,
			statistics: {},
			baseUrl: this.generateUrl('/apps/ldapcontacts'),
			chartWrapperList: [],
			charts: [],
			text: {
				total: t('ldapcontacts', 'Total:'),
			},
			graphs: {
				bgColors: [
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 99, 132, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)',
				],
				borderColors: [
					'rgba(54, 162, 235, 1)',
					'rgba(255,99,132,1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)',
				],
				borderWidth: 1,
			},
			dataLabels: {
				entries: t('ldapcontacts', 'Entries'),
				entries_filled: t('ldapcontacts', 'Filled'),
				entries_empty: t('ldapcontacts', 'Empty'),
				entries_filled_percent: t('ldapcontacts', 'Filled'),
				entries_empty_percent: t('ldapcontacts', 'Empty'),
				users: t('ldapcontacts', 'Users'),
				users_filled_entries: t('ldapcontacts', 'Users with some filled entries'),
				users_empty_entries: t('ldapcontacts', 'Users with only empty entries'),
				users_filled_entries_percent: t('ldapcontacts', 'Users with some filled entries'),
				users_empty_entries_percent: t('ldapcontacts', 'Users with only empty entries'),
			},
			statTitles: {
				entries: t('ldapcontacts', 'Entries filled'),
				user_entries: t('ldapcontacts', 'Users with filled entries'),
			},
		}
	},
	computed: {},
	beforeMount() {
		this.loadStatistics()
	},
	methods: {
		loadStatistics() {
			const self = this

			Axios.get(self.baseUrl + '/statistics')
				.finally(function() {
					self.loading = false
				})
				.then(function(response) {
					if (response.data.status === 'success') {
						self.statistics = response.data.data
						self.renderAll()
					}
				})
		},
		renderAll() {
			this.renderEntriesStat()
			this.renderUsersEntriesStat()
		},
		renderEntriesStat() {
			this.renderBarGraph('entries', 'entries_stat', ['entries_filled', 'entries_empty'], this.statistics.entries)
		},
		renderUsersEntriesStat() {
			this.renderBarGraph('user_entries', 'users_entries_stat', ['users_filled_entries', 'users_empty_entries'], this.statistics.users)
		},
		renderBarGraph(title, id, dataKeys, total) {
			const self = this
			const canvasId = 'ldapcontacts_statistics_' + id
			title = self.statTitles[title]

			self.chartWrapperList.push({
				id: canvasId,
				title,
				total,
			})

			// get all data values
			const data = []
			$.each(dataKeys, function(k, key) {
				data.push(self.statistics[key])
			})
			// get all data labels
			const labels = []
			$.each(dataKeys, function(k, key) {
				labels.push(self.dataLabels[key])
			})

			self.$nextTick(function() {
				const ctx = document.getElementById(canvasId).getContext('2d')

				self.charts.push(new Chart(ctx, {
					type: 'pie',
					data: {
						datasets: [
							{
								data,
								backgroundColor: self.graphs.bgColors,
								borderColor: self.graphs.borderColors,
								borderWidth: self.graphs.borderWidth,
							},
						],
						labels,
					},
					options: {

					},
				}))
			})
		},
	},
}
</script>
