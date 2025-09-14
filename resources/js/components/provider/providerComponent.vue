<template>
  <v-app id="provider">
    <v-snackbar v-model="snackbar" top right timeout="-1">
      <v-card-text v-html="message"></v-card-text>
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>
    <v-container>
      <v-row>
        <v-spacer></v-spacer>
        <v-col>
          <v-btn @click="$emit('logout')" title="Exit"><v-icon>mdi-logout</v-icon></v-btn></v-col></v-row>
      <v-row>
        <v-tabs v-model="tab">
          <v-tab>Report</v-tab>
          <v-tab>Import</v-tab>
          <v-tab v-if="$attrs.user.showInfo == 1">Import info</v-tab>
          <v-tab>CHECK DUBLIKATE MAIL</v-tab>
        </v-tabs>
      </v-row>
    </v-container>
    <v-tabs-items v-model="tab">
      <v-tab-item>
        <v-container>
          <v-row>
            <h2 class="col col-12 text-center">Provider {{ user.name }}</h2>
            <div class="col-12 text-center">Start date {{ start_date }}</div>
            <div class="title col-12 text-center">FTD Statistic</div>
          </v-row>
          <v-row>
            <v-col cols="6">

              <v-progress-linear :active="pie2" :indeterminate="pie2" color="deep-purple accent-4"></v-progress-linear>
              <PieChart :datap="chartDataTime" />
              <v-row>
                <v-col>
                  All lids: {{ allLidsTime }}
                  <div class="wrp__statuses">
                    <template v-for="i in statuses_time">
                      <div class="status_wrp" :class="{ active: filterStatus.includes(i.status_id) }" :key="i.status_id"
                        @click.stop="changeFilterStatus(i.status_id)">
                        <b :style="{
                          background: i.color,
                          outline: '1px solid ' + i.color,
                        }">{{ i.hm }}</b>
                        <span>{{ i.name }}</span>
                        <v-btn v-if="filterStatus.includes(i.status_id)" icon x-small>
                          <v-icon>mdi-close</v-icon>
                        </v-btn>
                      </div>
                    </template>
                  </div>
                </v-col>
              </v-row>
            </v-col>
            <v-col cols="6">

              <v-progress-linear :active="pie1" :indeterminate="pie1" color="deep-purple accent-4"></v-progress-linear>
              <PieChart :datap="chartDataAll" />
              <v-row>
                <v-col>
                  All time All lids: {{ allLidsAll }}
                  <div class="wrp__statuses">
                    <template v-for="(i, x) in statuses_all">
                      <div class="status_wrp" :key="x">
                        <b :style="{
                          background: i.color,
                          outline: '1px solid' + i.color,
                        }">{{ i.hm }}</b>
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
              <v-menu v-model="dateShowFrom" :close-on-content-click="false" :nudge-right="40"
                transition="scale-transition" offset-y min-width="auto">
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field v-model="dateFrom" label="Start day" readonly v-bind="attrs" v-on="on"
                    class="border pl-10" hide-details></v-text-field>
                </template>
                <v-date-picker v-model="dateFrom" @input="
                  dateShowFrom = false;
                getPieTime();
                getDataTime();
                "></v-date-picker> </v-menu></v-col>
            <v-col cols="col-4">
              <v-menu v-model="dateShowTo" :close-on-content-click="false" :nudge-right="40"
                transition="scale-transition" offset-y min-width="auto">
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field v-model="dateTo" label="Stop day" readonly v-bind="attrs" v-on="on" class="border pl-10"
                    hide-details></v-text-field>
                </template>
                <v-date-picker v-model="dateTo" class="border" @input="
                  dateShowTo = false;
                getPieTime();
                getDataTime();
                "></v-date-picker> </v-menu></v-col>
            <v-col cols="col-4"><v-text-field label="Text search" class="border px-5 mt-0" hide-details="auto"
                single-line v-model="text_search"></v-text-field></v-col>
            <v-col cols="col-4">
              <!-- @change="filterStatuses" -->
              <v-select ref="filterStatus" color="red" v-model="filterStatus" :items="statuses" item-text="name"
                item-value="id" label="Status filter" outlined rounded hide-details :multiple="true">
                <template v-slot:selection="{ item, index }">
                  <span v-if="index === 0">{{ item.name }} </span>
                  <span v-if="index === 1" class="grey--text text-caption">
                    (+{{ filterStatus.length - 1 }} )
                  </span>
                </template>
                <template v-slot:item="{ item, attrs }">
                  <v-badge :value="attrs['aria-selected'] == 'true'" color="#7620df" dot left>
                    <i :style="{
                      background: item.color,
                      outline: '1px solid grey',
                    }" class="sel_stat mr-4"></i>
                  </v-badge>
                  {{ item.name }}
                </template>
              </v-select>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-progress-linear :active="loading" :indeterminate="loading"
                color="deep-purple accent-4"></v-progress-linear>
              <v-data-table :headers="headers" :items="filteredItems" :search="text_search" show-expand
                :expanded.sync="expanded" expand-icon="" @click:row="clickrow">
                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <logtel :lid_id="item.id" :key="item.id" />
                  </td>
                </template>
              </v-data-table>
            </v-col>
            <v-dialog v-model="popup" max-width="500">
              <v-card>
                <v-card-title class="text-h5"> History statuses </v-card-title>
                <v-progress-linear :active="history_load" :indeterminate="history_load"
                  color="deep-purple accent-4"></v-progress-linear>
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
      </v-tab-item>
      <v-tab-item>
        <v-container>
          <v-row>
            <v-col cols="3">
              <v-file-input v-model="files" ref="fileupload" label="загрузить CSV" show-size truncate-length="24"
                @change="onFileChange"></v-file-input>
            </v-col>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="provider_importlid" v-if="parse_csv.length">Upload</v-btn>
            <v-col cols="2" v-else>
              <download-csv :data="demo" delimiter=";" :name="'leads.csv'">
                <v-btn depressed>Download demo</v-btn>
              </download-csv>
            </v-col>
          </v-row>
        </v-container>
        <v-data-table :headers="headers_import" item-key="tel+afilyator" :items="parse_csv" ref="datatable"
          :loading="loading"></v-data-table>
      </v-tab-item>
      <!-- Import info -->
      <v-tab-item>
        <importInfo :user="user" />
      </v-tab-item>
      <v-tab-item>
        <checkDuplicate :user="user" />
      </v-tab-item>
    </v-tabs-items>
  </v-app>
</template>

<script>
import PieChart from "./pieComponents";
import axios from "axios";
import _ from "lodash";
import JsonCSV from "vue-json-csv";
import checkDuplicate from "./checkDuplicate.vue";
import importInfo from "./importInfo.vue";
import logtel from "../manager/logtel.vue";

export default {
  components: {
    PieChart,
    downloadCsv: JsonCSV,
    logtel,
    checkDuplicate,
    importInfo,
  },
  data: () => ({
    expanded: [],
    filterstatuses: [],
    message: "",
    snackbar: false,
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
    headers_import: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Тел.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
    ],
    headers: [
      { text: "Name", value: "name" },
      { text: "Email", value: "email" },
      { text: "Phone.", value: "tel" },
      { text: "Start data", value: "created_at" },
      { text: "Edit date", value: "updated_at" },
      { text: "Status", value: "status_name" },
    ],
    parse_header: [],
    parse_csv: [],
    parse_txt_emails: [],
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
    tab: 0,
    files: [],
    demo: [
      {
        name: "Myname",
        email: "myemail@test.com",
        tel: 1234567890,
        afilyator: "Some afilyator",
      },
    ],
    start_date: "",
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
    changeFilterStatus(el_id) {
      if (this.filterStatus.includes(el_id)) {
        this.filterStatus = this.filterStatus.filter((i) => i != el_id);
      } else {
        this.filterStatus.push(el_id);
      }

    },
    provider_importlid() {
      let self = this;
      let data = {};
      self.loading = true;
      self.message = "";
      data.provider_id = self.user.id;
      data.lids = self.parse_csv;

      axios
        .post("api/provider_importlid", data)
        .then(function (res) {
          if (res.status == 200) {
            self.snackbar = true;
            self.message = res.data;
            self.loading = false;
            self.parse_csv = [];
          }
          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    onFileChange(f) {
      if (f == null) return;
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
        "text/plain",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      } else {
        this.files = [];
      }
    },
    txt2Array(txt) {
      let vm = this;
      vm.list_email = txt;
      // let lines = vm.list_email.replace(/[\r]/gm, '').split("\n");
    },
    csvJSON(csv) {
      var vm = this;
      var lines = csv.split("\n");
      var result = [];
      var headers = lines[0].split(";");

      headers = ["name", "email", "tel", "afilyator"];

      lines.map(function (line, indexLine) {
        //if (indexLine < 1) return; // Jump header line
        var obj = {};
        line = line.trim();
        var currentline = line.split(";");

        headers.map(function (header, indexHeader) {
          obj[header] = currentline[indexHeader];
        });
        result.push(obj);
      });
      return result; // JavaScript object
    },
    createInput(file) {
      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        var vm = this;
        reader.onload = (e) => {
          resolve((vm.fileinput = reader.result));
        };
        reader.readAsText(file);
      });

      promise.then(
        (result) => {
          let vm = this;
          if (vm.tab == 2) {
            vm.parse_txt_emails = vm.txt2Array(this.fileinput);
          } else {
            vm.parse_csv = vm.csvJSON(this.fileinput);
          }
        },
        (error) => {
          console.log(error);
        }
      );
    },
    clickrow(item, row) {
      // this.tel = item.tel;
      // this.lid_id = item.id;
      if (!row.isExpanded) {
        this.expanded = [item];
      } else {
        this.expanded = [];
      }

      /* if (!row.isSelected) {
        this.tel = item.tel;
        this.lid_id = item.id;
        this.expanded = [item];
      } else this.tel = "";
       row.select(!row.isSelected); */
    },
    // clickrow(item, row) {
    //   const self = this;
    //   self.history = [];
    //   self.history_load = true;
    //   axios
    //     .get("api/historyLid/" + item.id)
    //     .then(function (res) {
    //       self.history = res.data.history;
    //       if (item.status_id != 10) {
    //         self.history = self.history.filter((el) => el.status_id != 10);
    //       }
    //       self.history_load = false;
    //     })
    //     .catch(function (error) {
    //       console.log(error);
    //     });
    //   this.popup = true;
    // },

    setUser() {
      this.user = this.$attrs.user;
      // Add conditional header after user is set
      if (this.user.showInfo == 1) {
        this.headers.splice(3, 0, { text: "Сообщение", value: "text" });
        this.headers.splice(4, 0, { text: "Депозит", value: "depozit" });
      }
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
          self.dateFrom +
          "/" +
          self.dateTo
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

.status_wrp.active {
  box-shadow: 0px 0px 9.5px 5px rgb(0, 255, 98);
}
</style>
