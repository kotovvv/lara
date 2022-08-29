<template>
  <v-app id="provider">
    <v-container>
      <v-row>
        <h2 class="col col-12 text-center">Постачальник {{ user.name }}</h2>
        <div class="title text-center">FTD Statistic</div>
      </v-row>
      <v-row>
        <v-col cols="6">
          <div class="title_all">All time</div>
          <PieChart :datap="chartData"/>
        </v-col>
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
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="dateFrom"
                  @input="dateShowFrom = false"
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
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="dateTo"
                  @input="dateShowTo = false"
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
          <PieChart :datap="chartData"/>
        </v-col>
      </v-row>
    </v-container>
  </v-app>
</template>

<script>
import PieChart from './pieComponents'


export default {
  components: {
    PieChart
  },
  data: () => ({
    user: {},
    dateFrom: new Date(new Date().setDate(new Date().getDate() - 14))
      .toISOString()
      .substring(0, 10),
    dateTo: new Date().toISOString().substring(0, 10),
    dateShowFrom: false,
    dateShowTo: false,
chartData: {
        labels: ['VueJs', 'EmberJs', 'ReactJs', 'AngularJs'],
        datasets: [
          {
            backgroundColor: ['#41B883', '#E46651', '#00D8FF', '#DD1B16'],
            data: [40, 20, 80, 10]
          }
        ]
      }
  }),
  methods: {
    setUser() {
      this.user = this.$attrs.user;
    },
  },
  mounted() {
    this.setUser()
  },

};
</script>

<style scoped>
.title_all{
  height:73px;
  text-align: center;
font-size: 1.4rem;
}
</style>
