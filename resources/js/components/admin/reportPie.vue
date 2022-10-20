<template>
  <div>
    <v-container>
      <v-row no-gutters justify="space-around">
        <v-col cols="3">
          <v-select
            :items="offices"
            v-model="selected_office_ids"
            item-text="name"
            item-value="id"
            multiple
            label="Offices"
            class="border px-5"
          ></v-select>
        </v-col>
        <div class="col-12 text-center">...</div>
      </v-row>
      <v-row no-gutters justify="space-around">
                  <v-progress-linear
            :active="pie1"
            :indeterminate="pie1"
            color="deep-purple accent-4"
          ></v-progress-linear>
      </v-row>
      <v-row no-gutters justify="space-around">
        <v-col cols="6">
          <v-row>
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
      </v-col>
      </v-row>
      <PieChart :datap="chartDataTime" />
    </v-container>
  </div>
</template>

<script>
import PieChart from "../provider/pieComponents";
import axios from "axios";
import _ from "lodash";
export default {
  components: {
    PieChart,
  },
  created(){
    this.getOffices()
  },
  data: () => ({
    pie1: false,
    loading: false,
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

    chartDataTime: {
      labels: [],
      datasets: [
        {
          backgroundColor: [],
          data: [],
        },
      ],
    },
    offices: [],
    selected_office_ids: [],
  }),
  methods: {
    getOffices() {
      let self = this;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
          self.selected_office_ids.push(self.offices[0].id)
        })
        .catch((error) => console.log(error));
    },
    getPieTime() {
      const self = this;
    },
  },
};
</script>
