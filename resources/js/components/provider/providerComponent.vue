<template>
  <div>
    <v-container>
      <v-row>
        <h2 class="col col-12 text-center">Постачальник {{ user.name }}</h2>
        <div class="title text-center">FTD Statistic</div>
      </v-row>
      <v-row>
        <v-col cols="6">
          <div class="title">All time</div>
        </v-col>
        <v-col cols="6">
          <v-row>
            <v-col cols="6">
<v-menu
        ref="menu"
        v-model="menu"
        :close-on-content-click="false"
        :return-value.sync="date"
        transition="scale-transition"
        offset-y
        min-width="auto"
      >
        <template v-slot:activator="{ on, attrs }">
          <v-text-field
            v-model="date"
            label="Start date"
            prepend-icon="mdi-calendar"
            readonly
            v-bind="attrs"
            v-on="on"
          ></v-text-field>
        </template>
        <v-date-picker
          v-model="date"
          no-title
          scrollable
        >
          <v-spacer></v-spacer>
          <v-btn
            text
            color="primary"
            @click="menu = false"
          >
            Cancel
          </v-btn>
          <v-btn
            text
            color="primary"
            @click="$refs.menu.save(date)"
          >
            OK
          </v-btn>
        </v-date-picker>
      </v-menu>
            </v-col>
          </v-row>
        </v-col>
  </v-row>
    </v-container>
  </div>
</template>

<script>
export default {
  data() {
    return {
      user: {},
      dateFrom: new Date(new Date().setDate(new Date().getDate() - 14))
        .toISOString()
        .substring(0, 10),
      date: '',
      dateShowFrom: false,
      menu: false,
      dateShowTo: false,
      dateTo: new Date().toISOString().substring(0, 10),
    };
  },
  methods: {
    setUser() {
      this.user = this.$attrs.user;
    },
  },
  mounted() {
    this.setUser();
  },
};
</script>
