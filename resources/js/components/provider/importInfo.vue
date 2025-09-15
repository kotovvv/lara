<template>
  <v-container>
    <v-row>
      <v-col cols="4">
        <div class="d-flex justify-center">

          <v-menu v-model="dateFrom" :close-on-content-click="false" :nudge-right="40" transition="scale-transition"
            offset-y min-width="auto">
            <template v-slot:activator="{ on, attrs }">
              <v-text-field v-model="datetimeFrom" readonly v-bind="attrs" v-on="on" label="Start date"></v-text-field>
            </template>
            <v-date-picker locale="ru-ru" v-model="datetimeFrom" @input="
              dateFrom = false;
            getImportOnDate();
            "></v-date-picker>
          </v-menu>

          <v-menu v-model="dateTo" :close-on-content-click="false" :nudge-right="40" transition="scale-transition"
            offset-y min-width="auto">
            <template v-slot:activator="{ on, attrs }">
              <v-text-field v-model="datetimeTo" readonly v-bind="attrs" v-on="on" label="Stop date"></v-text-field>
            </template>
            <v-date-picker locale="ru-ru" v-model="datetimeTo" @input="
              dateTo = false;
            getImportOnDate();
            "></v-date-picker>
          </v-menu>


        </div>
      </v-col>

    </v-row>
    <v-row>
      <v-col cols="12">
        <v-progress-linear :active="loading" indeterminate color="purple"></v-progress-linear>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <v-tabs v-model="tabimport">
          <v-tab value="cpl">CPL</v-tab>
          <v-tab value="cpa">CPA</v-tab>
        </v-tabs>
        <v-tabs-items v-model="tabimport">
          <v-tab-item :key="'cpl'">
            <v-data-table height="60vh" :headers="headers_cpl" :fixed-header="true" item-key="id" id="cpl" :items="cpl"
              hide-default-header>
              <template v-slot:header="{ props }">
                <tr>
                  <th v-for="header in props.headers" :key="header.value" :style="getHeaderStyle(header.value)"
                    style="text-align: center;font-size: 1rem;">
                    {{ header.text }}
                  </th>
                </tr>
              </template>
              <template v-slot:item="{ item, headers }">
                <tr @click="onCplRowClick(item)" style="cursor: pointer;"
                  :style="{ border: selectedFilter === item.load_mess ? '2px solid #000' : '' }">
                  <td v-for="header in headers" :key="header.value" :style="getCellStyle(header.value, item)"
                    style="text-align: center;">
                    {{ item[header.value] }}
                  </td>
                </tr>
              </template>
            </v-data-table>
          </v-tab-item>
          <v-tab-item :key="'cpa'">
            <v-data-table height="60vh" :headers="headers_cpa" :fixed-header="true" item-key="id" id="cpa" :items="cpa"
              hide-default-header>
              <template v-slot:header="{ props }">
                <tr>
                  <th v-for="header in props.headers" :key="header.value" :style="getHeaderStyle(header.value)"
                    style="text-align: center;font-size: 1rem;">
                    {{ header.text }}
                  </th>
                </tr>
              </template>
              <template v-slot:item="{ item, headers }">
                <tr @click="onCpaRowClick(item)" style="cursor: pointer;"
                  :style="{ border: selectedFilter === item.load_mess ? '2px solid #000' : '' }">
                  <td v-for="header in headers" :key="header.value" :style="getCellStyle(header.value, item)"
                    style="text-align: center;">
                    {{ item[header.value] }}
                  </td>
                </tr>
              </template>
            </v-data-table>
          </v-tab-item>
        </v-tabs-items>
      </v-col>
    </v-row>
    <v-row v-if="filteredLids.length > 0">
      <v-col>
        <p>{{ selectedFilter }}</p>
        <div class="wrp__statuses">
          <template v-for="i in statusesWithCounts">
            <div class="status_wrp" :class="{ active: filterStatus.includes(i.id) }" :key="i.id"
              @click.stop="changeFilterStatus(i.id)">
              <b :style="{
                background: i.color,
                outline: '1px solid ' + i.color,
              }">{{ i.hm }}</b>
              <span>{{ i.name }}</span>
              <v-btn v-if="filterStatus.includes(i.id)" icon x-small>
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </div>
          </template>
        </div>
        <v-data-table :headers="headers_lids" :items="filteredLids" item-key="id" show-expand :expanded.sync="expanded"
          expand-icon="" @click:row="clickrow">
          <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" class="blackborder">
              <logtel :lid_id="item.id" :key="item.id" />
            </td>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
  </v-container>
</template>


<script>
import axios from 'axios';
import Logtel from '../manager/logtel.vue';
export default {
  name: 'LaraImportInfo',
  components: {
    Logtel,
  },

  data() {
    return {
      dateFrom: false,
      dateTo: false,
      datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 14))
        .toISOString()
        .substring(0, 10),
      datetimeTo: new Date().toISOString().substring(0, 10),
      loading: false,
      lids: [],
      statuses: [],
      cpl: [],
      cpa: [],
      tabimport: 'cpl',
      headers_cpl: [
        { text: 'Date', value: 'created_at', align: 'start', width: '120px' },
        { text: 'Название файла', value: 'load_mess', align: 'start' },
        { text: 'GEO', value: 'geo', align: 'start' },
        { text: 'All', value: 'count_all', align: 'center', width: '75px' },
        { text: 'New', value: 'count_status_8', align: 'center', width: '100px' },
        { text: 'RE-NEW', value: 'count_status_33', align: 'center', width: '100px' },
        { text: 'Call back', value: 'count_status_9', align: 'center', width: '100px' },
        { text: 'Deposit', value: 'count_status_10', align: 'center', width: '100px' },
        { text: 'Pending', value: 'count_status_20', align: 'center', width: '100px' },
        { text: 'POTENTIAL', value: 'count_status_32', align: 'center', width: '100px' },
        { text: 'Not interested', value: 'count_status_12', align: 'center', width: '100px' },
        { text: 'No answer', value: 'count_status_7', align: 'center', width: '100px' },
        // { text: 'Source/Campaign', value: 'load_mess', align: 'start' }
      ],
      headers_cpa: [
        { text: 'Date', value: 'created_at', align: 'start', width: '120px' },

        { text: 'All', value: 'count_all', align: 'center', width: '75px' },
        { text: 'New', value: 'count_status_8', align: 'center', width: '100px' },
        { text: 'RE-NEW', value: 'count_status_33', align: 'center', width: '100px' },
        { text: 'Call back', value: 'count_status_9', align: 'center', width: '100px' },
        { text: 'Deposit', value: 'count_status_10', align: 'center', width: '100px' },
        { text: 'Pending', value: 'count_status_20', align: 'center', width: '100px' },
        { text: 'POTENTIAL', value: 'count_status_32', align: 'center', width: '100px' },
        { text: 'Not interested', value: 'count_status_12', align: 'center', width: '100px' },
        { text: 'No answer', value: 'count_status_7', align: 'center', width: '100px' },
        // { text: 'Source/Campaign', value: 'load_mess', align: 'start' }
      ],
      headers_lids: [
        { text: 'Name', value: 'name', align: 'start' },
        { text: 'Email', value: 'email', align: 'start' },
        { text: 'Phone', value: 'tel', align: 'start' },
        { text: 'Status', value: 'status_name', align: 'start' },
        { text: 'Created', value: 'created_at', align: 'start', width: '150px' },
      ],
      selectedFilter: null, // Для хранения выбранного фильтра (CPL ключ или CPA дата)
      expanded: [],
      filterStatus: [],
    };
  },

  mounted() {
    this.getImportOnDate()
    this.setUser();
  },

  computed: {
    headers() {
      return this.tabimport === 'cpl' ? this.headers_cpl : this.headers_cpa;
    },
    filteredLids() {
      if (!this.selectedFilter) {
        return [];
      }

      let filtered = [];

      if (this.tabimport == 0) {
        // Фильтрация по load_mess для CPL
        filtered = this.lids.filter(lid => lid.load_mess === this.selectedFilter);

      } else {
        // Фильтрация по дате для CPA
        filtered = this.lids.filter(lid => lid.created_at.substring(0, 10) === this.selectedFilter);
      }

      // Подсчитываем статусы в отфильтрованных лидах
      const statusCounts = {};
      filtered.forEach(lid => {
        if (statusCounts[lid.status_id]) {
          statusCounts[lid.status_id]++;
        } else {
          statusCounts[lid.status_id] = 1;
        }
      });

      // Обновляем статусы с количеством
      this.statusesWithCounts = this.statuses
        .filter(status => statusCounts[status.id])
        .map(status => ({
          ...status,
          hm: statusCounts[status.id]
        }));


      // Дополнительная фильтрация по статусам, если выбраны статусы
      if (this.filterStatus.length > 0) {
        filtered = filtered.filter(lid => this.filterStatus.includes(lid.status_id));
      }

      return filtered;
    }
  },

  methods: {
    changeFilterStatus(el_id) {
      if (this.filterStatus.includes(el_id)) {
        this.filterStatus = this.filterStatus.filter((i) => i != el_id);
      } else {
        this.filterStatus.push(el_id);
      }

    },
    clickrow(item, row) {
      if (this.$attrs.user.showInfo != 1) {
        return;
      }
      // this.tel = item.tel;
      // this.lid_id = item.id;
      if (!row.isExpanded) {
        this.expanded = [item];
      } else {
        this.expanded = [];
      }

    },
    setUser() {

      // Add conditional header after user is set
      if (this.$attrs.user.showInfo == 1) {
        this.headers_lids.splice(5, 0, { text: "Сообщение", value: "text" });
        this.headers_lids.splice(6, 0, { text: "Депозит", value: "depozit" });
      }
    },
    getImportOnDate() {
      axios.post('/api/getImportOnDate', {
        dateFrom: this.datetimeFrom,
        dateTo: this.datetimeTo,
      })
        .then((response) => {
          this.statuses = response.data.statuses;
          this.lids = response.data.lids;
          this.lids.forEach((lid) => {
            lid.created_at = lid.created_at.substring(0, 19).replace('T', ' ');
            const status = this.statuses.find((s) => s.id === lid.status_id);
            lid.status_name = status ? status.name : 'Unknown';
          });
          // Разделение на CPL и CPA
          const cplLids = this.lids.filter(lid => lid.load_mess && lid.load_mess.trim() !== '');
          const cpaLids = this.lids.filter(lid => !lid.load_mess || lid.load_mess.trim() === '');

          // Обработка CPL - группировка по load_mess
          const cplGrouped = {};
          cplLids.forEach(lid => {
            const key = lid.load_mess;
            if (!cplGrouped[key]) {
              cplGrouped[key] = {
                created_at: lid.created_at,
                count_all: 0,
                count_status_8: 0,
                count_status_33: 0,
                count_status_9: 0,
                count_status_10: 0,
                count_status_20: 0,
                count_status_32: 0,
                count_status_12: 0,
                count_status_7: 0,
                geo: lid.geo,
                load_mess: key
              };
            }
            cplGrouped[key].count_all++;
            if ([8, 33, 9, 10, 20, 32, 12, 7].includes(lid.status_id)) {
              cplGrouped[key][`count_status_${lid.status_id}`]++;
            }
          });
          this.cpl = Object.values(cplGrouped);

          // Обработка CPA - группировка по дням
          const cpaGrouped = {};
          cpaLids.forEach(lid => {
            const dateKey = lid.created_at.substring(0, 10);
            if (!cpaGrouped[dateKey]) {
              cpaGrouped[dateKey] = {
                created_at: dateKey,
                count_all: 0,
                count_status_8: 0,
                count_status_33: 0,
                count_status_9: 0,
                count_status_10: 0,
                count_status_20: 0,
                count_status_32: 0,
                count_status_12: 0,
                count_status_7: 0
              };
            }
            cpaGrouped[dateKey].count_all++;
            if ([8, 33, 9, 10, 20, 32, 12, 7].includes(lid.status_id)) {
              cpaGrouped[dateKey][`count_status_${lid.status_id}`]++;
            }
          });
          this.cpa = Object.values(cpaGrouped);
          this.loading = false;
        })
        .catch((error) => {
          console.error('There was an error!', error);
        });
    },
    getStatusColor(statusId) {
      const status = this.statuses.find(s => s.id === statusId);
      return status ? status.color : '';
    },
    getTextColor(statusId) {
      // Светлый фон — темный текст, иначе белый
      return '#222';
      const light = [8, 20, 32, 33, 34];
      return light.includes(statusId) ? '#222' : '#fff';
    },
    getHeaderStyle(value) {
      if (!value.startsWith('count_status_')) return {};
      const statusId = parseInt(value.replace('count_status_', ''));
      const status = this.statuses.find(s => s.id === statusId);
      return status ? { background: status.color, color: this.getTextColor(statusId) } : {};
    },
    getCellStyle(value, item) {
      if (!value.startsWith('count_status_')) return {};
      const statusId = parseInt(value.replace('count_status_', ''));
      const status = this.statuses.find(s => s.id === statusId);
      return status ? { background: status.color, color: this.getTextColor(statusId) } : {};
    },
    onCplRowClick(item) {
      // Клик по строке CPL - фильтруем лиды по load_mess
      this.filterStatus = [];
      this.selectedFilter = item.load_mess;
    },
    onCpaRowClick(item) {
      // Клик по строке CPA - фильтруем лиды по дате
      this.filterStatus = [];
      this.selectedFilter = item.created_at;
    },
  },
};
</script>

<style scoped>
.status_wrp.active {
  box-shadow: 0px 0px 9.5px 5px rgb(0, 255, 98);
}
</style>
