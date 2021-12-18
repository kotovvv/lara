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
              <tr v-for="(item, x) in todayReport" :key="x">
                <td>{{ item.ftd }}</td>
                <td>{{ item.sum }}</td>
                <td>{{ item.hmcall }}</td>
                <td>{{ item.alltimecall }}</td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </v-col>
    </v-row>
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
              <tr v-for="(item, x) in monthReport" :key="x">
                <td>{{ item.ftd }}</td>
                <td>{{ item.sum }}</td>
                <td>{{ item.hmcall }}</td>
                <td>{{ item.alltimecall }}</td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
import _ from "lodash";
export default {
  data: () => ({
    todayReport: {},
    monthReport: {},
    BalansMonth: {},
    StatusesMonth: {},
    DepozitsMonth: {},
    value: [423, 446, 675, 510, 590, 610, 760],
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
      axios
        .get("api/getBalansMonth/" + self.$attrs.user.id)
        .then((res) => {
          self.BalansMonth = res.data;
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
        })
        .catch(function (error) {
          console.log(error);
        });
    },
  },
};
</script>
