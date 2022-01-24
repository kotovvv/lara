<template>
  <div>
    <v-row>
      <v-col cols="4">
        <h2>Месяц</h2>
        <div>
<ve-line :data="chartData"></ve-line>
        </div>
      </v-col>
      <v-col cols="8">
              <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">FTD</th>
                <th class="text-left">Сегодня</th>
                <th class="text-left">Количество наборов</th>
                <th class="text-left">Общее время разговоров</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ todayReport.ftd }}</td>
                <td>{{ todayReport.sum }}</td>
                <td>{{ todayReport.hmcall }}</td>
                <td>{{ todayReport.alltimecall }}</td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">FTD</th>
                <th class="text-left">Месяц</th>
                <th class="text-left">Количество наборов</th>
                <th class="text-left">Общее время разговоров</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ monthReport.ftd }}</td>
                <td>{{ monthReport.sum }}</td>
                <td>{{ monthReport.hmcall }}</td>
                <td>{{ monthReport.alltimecall }}</td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>

        <v-card class="mt-5">

          <v-card-title primary-title>
<h3>Статусы</h3>
          </v-card-title>
          <v-card-actions>
            <div>
         <template v-for="(i) in StatusesMonth">
<div class="status_wrp"><span :style="{background: i[1][0].color }">{{ i[0] }}</span><b>{{ i[1].length }}</b> </div>
        </template>
        </div>
          </v-card-actions>
        </v-card>


      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
import _ from "lodash";
import VeLine from 'v-charts/lib/line.common'

import 'v-charts/lib/style.css'
export default {
  components: { VeLine },
  data: () => ({
    todayReport: {
      ftd: "",
      sum: "",
      hmcall: "",
      alltimecall: "",
    },
    monthReport: {
            ftd: "",
      sum: "",
      hmcall: "",
      alltimecall: "",
    },
    BalansMonth: {},
    StatusesMonth: {},
    DepozitsMonth: {},
    chartData: {
        columns: ['date','balans'],
        rows: []
      }
  }),
  mounted: function () {
    this.getBalansMonth();
    this.getStatusesMonth();
    this.getDepozitsMonth();
    this.getCallsMonth()
  },
  methods: {
    monthStatus() {},
    getBalansMonth() {
      let self = this;
      self.chartData.rows = [];
      axios
        .get("api/getBalansMonth/" + self.$attrs.user.id)
        .then((res) => {
          self.BalansMonth = res.data;
          self.todayReport.sum = _.sumBy(_.filter(res.data,{'date':(new Date()).toISOString().slice(0,10)}),'balans')
          self.monthReport.sum = _.sumBy(res.data,'balans')
          self.BalansMonth.map((i) => {
            self.chartData.rows.push({date:i.date,balans:i.balans});
          });
          // if (self.chartData.length == 1) self.chartData.unshift(0);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getStatusesMonth() {
      let self = this;
      axios
        .get("api/getStatusesMonth/" + self.$attrs.user.id)
        .then((res) => {
          self.StatusesMonth = res.data;

          self.monthReport.ftd = _.filter(self.StatusesMonth,{'name' : 'Deposit'}).length
          self.StatusesMonth = Object.entries(_.groupBy(self.StatusesMonth,'name'))
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getDepozitsMonth() {
      let self = this;
      axios
        .get("api/getDepozitsMonth/" + self.$attrs.user.id)
        .then((res) => {
          self.DepozitsMonth = res.data;
          self.todayReport.ftd = _.filter(self.DepozitsMonth,(d)=>{return d.created_at.slice(0,10) ==(new Date()).toISOString().slice(0,10)}).length
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getCallsMonth() {
      let self = this;
      axios
        .get("api/getCallsMonth/" + self.$attrs.user.id)
        .then((res) => {
          //  console.log(res.data.callmonth[0])
          self.monthReport.hmcall = res.data.callmonth[0].count
          let t = (((res.data.callmonth[0].duration)/60)/60).toFixed(2).toString().split('.')
          self.monthReport.alltimecall = t[0]+' час. '+ t[1]+' мин.'
          self.todayReport.hmcall = res.data.callday[0].count
          t = (((res.data.callday[0].duration)/60)/60).toFixed(2).toString().split('.')
          self.todayReport.alltimecall = t[0]+' час. '+ t[1]+' мин.'
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
};
</script>

<style scoped>

.status_wrp span {
  padding: 5px;
  word-break: break-all;
}

.status_wrp b {
  padding: 0 8px;
}

.status_wrp {
  margin-right: 15px;
  border: 1px solid grey;
  padding: 3px 0;
  margin-bottom: 15px;
  display: inline-block;
}

</style>
