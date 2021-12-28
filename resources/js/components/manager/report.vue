<template>
  <div>
    <v-row>
      <v-col cols="4"> <h2>Сегодня</h2> </v-col>
      <v-col cols="8">
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">FTD</th>
                <th class="text-left">Сумма</th>
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
      </v-col>
    </v-row>
 <hr>
    <v-row>
      <v-col cols="4">
        <h2>Месяц</h2>
        <v-card-text>
          <v-sheet color="rgba(0, 0, 0, .12)">
            <v-sparkline
              :value="value"
              color="rgba(255, 255, 255, .7)"
              height="100"
              padding="24"
              stroke-linecap="round"
              smooth
            >
              <template v-slot:label="item">
                {{ item.value }}
              </template>
            </v-sparkline>
          </v-sheet>
        </v-card-text>
      </v-col>
      <v-col cols="8">
        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">FTD</th>
                <th class="text-left">Сумма</th>
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
export default {
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
    value: [],
  }),
  mounted: function () {
    this.getBalansMonth();
    this.getStatusesMonth();
    this.getDepozitsMonth();
  },
  methods: {
    monthStatus() {},
    getBalansMonth() {
      let self = this;
      self.value = [];
      axios
        .get("api/getBalansMonth/" + self.$attrs.user.id)
        .then((res) => {
          self.BalansMonth = res.data;
          self.todayReport.sum = _.sumBy(_.filter(res.data,{'date':(new Date()).toISOString().slice(0,10)}),'balans')
          self.monthReport.sum = _.sumBy(res.data,'balans')
          self.BalansMonth.map((i) => {
            self.value.push(i.balans);
          });
          if (self.value.length == 1) self.value.unshift(0);
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
          // self.monthReport.hmcall = _.sumBy(self.StatusesMonth,'cols')
          // self.monthReport.alltimecall = _.sumBy(self.StatusesMonth,'duration')
          // let today = _.filter(self.StatusesMonth,{'date':(new Date()).toISOString().slice(0,10)})
          // self.todayReport.hmcall = _.sumBy(today,'cols')
          // self.todayReport.alltimecall = _.sumBy(today,'duration')

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
