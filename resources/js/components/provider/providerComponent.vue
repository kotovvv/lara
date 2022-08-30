<template>
  <v-app id="provider">
    <v-container>
      <v-row>
        <h2 class="col col-12 text-center">Постачальник {{ user.name }}</h2>
        <div class="col-12 text-center">Start date {{ start_date }}</div>
        <div class="title col-12 text-center">FTD Statistic</div>
      </v-row>
      <v-row>
        <v-col cols="6">
          <div class="title_all">All time</div>
          <v-progress-linear
            :active="pie1"
            :indeterminate="pie1"
            color="deep-purple accent-4"
          ></v-progress-linear>
          <PieChart :datap="chartDataAll" />
          <v-row>
            <v-col>
              All lids: {{ allLidsAll }}
              <div class="wrp__statuses">
                <template v-for="(i, x) in statuses_all">
                  <div class="status_wrp" :key="x">
                    <b
                      :style="{
                        background: i.color,
                        outline: '1px solid' + i.color,
                      }"
                      >{{ i.hm }}</b
                    >
                    <span>{{ i.name }}</span>
                  </div>
                </template>
              </div>
            </v-col>
          </v-row>
        </v-col>
        <v-col cols="6">
          <v-row class="mb-1">
            <v-col cols="6">
              <v-menu
                v-model="dateShowFrom"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateFrom"
                    label="Start day"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                    hide-details
                    class="border pl-10"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="dateFrom"
                  @input="
                    dateShowFrom = false;
                    getPieTime();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
            <v-col cols="6">
              <v-menu
                v-model="dateShowTo"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateTo"
                    label="Stop day"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                    hide-details
                    class="border pl-10"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="dateTo"
                  @input="
                    dateShowTo = false;
                    getPieTime();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
          <v-progress-linear
            :active="pie2"
            :indeterminate="pie2"
            color="deep-purple accent-4"
          ></v-progress-linear>
          <PieChart :datap="chartDataTime" />
          <v-row>
            <v-col>
              All lids: {{ allLidsTime }}
              <div class="wrp__statuses">
                <template v-for="(i, x) in statuses_time">
                  <div class="status_wrp" :key="x">
                    <b
                      :style="{
                        background: i.color,
                        outline: '1px solid' + i.color,
                      }"
                      >{{ i.hm }}</b
                    >
                    <span>{{ i.name }}</span>
                  </div>
                </template>
              </div>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>

    <v-container fluid class="mt-10">
      <v-row>
        <v-col cols="col-4">
          <v-menu
            v-model="tablDateShowFrom"
            :close-on-content-click="false"
            :nudge-right="40"
            transition="scale-transition"
            offset-y
            min-width="auto"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="tablDateFrom"
                label="Start day"
                readonly
                v-bind="attrs"
                v-on="on"
                class="border pl-10"
                hide-details
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="tablDateFrom"
              @input="
                tablDateShowFrom = false;
                getDataTime();
              "
            ></v-date-picker> </v-menu
        ></v-col>
        <v-col cols="col-4">
          <v-menu
            v-model="tablDateShowTo"
            :close-on-content-click="false"
            :nudge-right="40"
            transition="scale-transition"
            offset-y
            min-width="auto"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="tablDateTo"
                label="Stop day"
                readonly
                v-bind="attrs"
                v-on="on"
                class="border pl-10"
                hide-details
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="tablDateTo"
              class="border"
              @input="
                tablDateShowTo = false;
                getDataTime();
              "
            ></v-date-picker> </v-menu
        ></v-col>
        <v-col cols="col-4"
          ><v-text-field
            label="Text search"
            class="border px-5 mt-0"
            hide-details="auto"
            single-line
            v-model="text_search"
          ></v-text-field
        ></v-col>
        <v-col cols="col-4">
          <!-- @change="filterStatuses" -->
          <v-select
            ref="filterStatus"
            color="red"
            v-model="filterStatus"
            :items="statuses"
            item-text="name"
            item-value="id"
            label="Status filter"
            outlined
            rounded
            hide-details
            :multiple="true"
          >
            <template v-slot:selection="{ item, index }">
              <span v-if="index === 0">{{ item.name }} </span>
              <span v-if="index === 1" class="grey--text text-caption">
                (+{{ filterStatus.length - 1 }} )
              </span>
            </template>
            <template v-slot:item="{ item, attrs }">
              <v-badge
                :value="attrs['aria-selected'] == 'true'"
                color="#7620df"
                dot
                left
              >
                <i
                  :style="{
                    background: item.color,
                    outline: '1px solid grey',
                  }"
                  class="sel_stat mr-4"
                ></i>
              </v-badge>
              {{ item.name }}
            </template>
          </v-select>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12">
          <v-progress-linear
            :active="loading"
            :indeterminate="loading"
            color="deep-purple accent-4"
          ></v-progress-linear>
          <v-data-table
            :headers="headers"
            :items="filteredItems"
            :search="text_search"
            @click:row="clickrow"
          ></v-data-table>
        </v-col>
        <v-dialog v-model="popup" max-width="500">
          <v-card>
            <v-card-title class="text-h5"> History statuses </v-card-title>
          <v-progress-linear
            :active="history_load"
            :indeterminate="history_load"
            color="deep-purple accent-4"
          ></v-progress-linear>
            <v-card-text>
              <v-simple-table>
                <template v-slot:default>
                  <thead>
                    <tr>
                      <th class="text-left">Date</th>
                      <th class="text-left">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in history" :key="item.id">
                      <td>{{ item.date }}</td>
                      <td>{{ item.status }}</td>
                    </tr>
                  </tbody>
                </template>
              </v-simple-table>
            </v-card-text>
          </v-card>
        </v-dialog>
      </v-row>
    </v-container>
  </v-app>
</template>

<script>
import PieChart from "./pieComponents";
import axios from "axios";
import _ from "lodash";
export default {
  components: {
    PieChart,
  },
  data: () => ({
    popup: false,
    pie1: false,
    pie2: false,
    loading: false,
    history_load: false,
    user: {},
    dateFrom: new Date(new Date().setDate(new Date().getDate() - 7))
      .toISOString()
      .substring(0, 10),
    dateTo: new Date().toISOString().substring(0, 10),
    dateShowFrom: false,
    dateShowTo: false,
    tablDateFrom: new Date(new Date().setDate(new Date().getDate() - 7))
      .toISOString()
      .substring(0, 10),
    tablDateTo: new Date().toISOString().substring(0, 10),
    tablDateShowFrom: false,
    tablDateShowTo: false,
    start_date: "",
    dateAll: [],
    dateTime: [],
    statuses_all: [],
    statuses_time: [],
    statuses: [],
    allLidsAll: "0",
    allLidsTime: "0",
    text_search: "",
    filterStatus: [],
    history: [],
    headers: [
      { text: "Name", value: "name" },
      { text: "Email", value: "email" },
      { text: "Phone.", value: "tel" },
      { text: "Start data", value: "created_at" },
      { text: "Edit date", value: "updated_at" },
      { text: "Status", value: "status_name" },
    ],
    lids: [],
    chartDataAll: {
      labels: [],
      datasets: [
        {
          backgroundColor: [],
          data: [],
        },
      ],
    },
    chartDataTime: {
      labels: [],
      datasets: [
        {
          backgroundColor: [],
          data: [],
        },
      ],
    },
  }),
  computed: {
    filteredItems() {
      return this.lids.filter((i) => {
        return (
          !this.filterStatus.length || this.filterStatus.includes(i.status_id)
        );
      });
    },
  },
  methods: {
    clickrow(item, row) {
      const self = this;
      self.history = []
      self.history_load = true
      axios
        .get("api/historyLid/" + item.id)
        .then(function (res) {
          self.history = res.data.history;
          if (item.status_id != 10) {
            self.history = self.history.filter((el) => el.status_id != 10);
          }
          self.history_load = false
        })
        .catch(function (error) {
          console.log(error);
        });
      this.popup = true;
    },
    setUser() {
      this.user = this.$attrs.user;
    },
    getDataTime() {
      const self = this;
      const provider_id = this.user.id;
      self.loading = true;
      axios
        .get(
          "api/getDataTime/" +
            provider_id +
            "/" +
            self.tablDateFrom +
            "/" +
            self.tablDateTo
        )
        .then(function (res) {
          self.lids = res.data.data;
          let g_status = [];
          g_status = Object.entries(_.groupBy(self.lids, "status_name"));
          g_status.forEach((el) => {
            self.statuses.push({
              name: el[0],
              color: el[1][0].color,
              id: el[1][0].status_id,
            });
          });

          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getPieAll() {
      const self = this;
      const provider_id = this.user.id;
      self.pie1 = true;
      axios
        .get("api/pieAll/" + provider_id)
        .then(function (res) {
          self.chartDataAll.labels = res.data.labels;
          self.chartDataAll.datasets[0].backgroundColor =
            res.data.backgroundColor;
          self.chartDataAll.datasets[0].data = res.data.data;
          self.start_date = res.data.start_date;
          self.statuses_all = res.data.statuses;
          self.allLidsAll = self.statuses_all.reduce(
            (all, el) => all + el.hm,
            0
          );
          self.pie1 = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getPieTime() {
      const self = this;
      const provider_id = this.user.id;
      self.pie2 = true;
      axios
        .get(
          "api/pieTime/" + provider_id + "/" + self.dateFrom + "/" + self.dateTo
        )
        .then(function (res) {
          self.chartDataTime.labels = res.data.labels;
          self.chartDataTime.datasets[0].backgroundColor =
            res.data.backgroundColor;
          self.chartDataTime.datasets[0].data = res.data.data;
          self.statuses_time = res.data.statuses;
          self.allLidsTime = self.statuses_time.reduce(
            (all, el) => all + el.hm,
            0
          );
          self.pie2 = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
  mounted() {
    this.setUser();
    this.getPieAll();
    this.getPieTime();
    this.getDataTime();
  },
};
</script>

<style scoped>
.title_all {
  height: 73px;
  text-align: center;
  font-size: 1.4rem;
}
</style>
