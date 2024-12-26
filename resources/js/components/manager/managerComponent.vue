<template>
  <v-app id="inspire">
    <v-navigation-drawer
      v-model="drawer"
      fixed
      parmament
      dark
      :mini-variant="true"
      width="64px"
      :app="true"
    >
      <!-- menu -->
      <v-list>
        <v-subheader></v-subheader>
        <v-list-item-group v-model="selectedItem" color="primary">
          <v-list-item
            v-for="(item, i) in items"
            :key="i"
            @click="managerMenu = item.name"
          >
            <v-list-item-icon>
              <v-icon v-text="item.icon"></v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title></v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
        <v-list-droup>
          <v-list-item>
            <v-list-item-icon>
              <v-icon @click="sunmoon">{{
                themeDark
                  ? "mdi-moon-waning-crescent"
                  : "mdi-white-balance-sunny"
              }}</v-icon>
            </v-list-item-icon>
          </v-list-item>
        </v-list-droup>

        <v-list-item-group class="mt-10">
          <v-list-item @click="$emit('login', {})">
            <v-list-item-icon>
              <v-icon>mdi-logout</v-icon>
            </v-list-item-icon>
          </v-list-item>
          <v-list-item>
            <v-list-item-content>
              <v-list-item-title>{{ user.fio }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <v-container fluid>
        <!-- <v-row> -->
        <!-- table -->
        <component :user="user" :is="managerComponent" />
        <!-- <tablenewlid></tablenewlid> -->
        <!-- </v-row> -->
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
const mlids = () => import("./mlids.vue");
const report = () => import("./report.vue");

export default {
  props: ["user"],
  data: () => ({
    drawer: null,
    selectedItem: 0,
    managerMenu: "report",
    themeDark: false,
    items: [
      { text: "Отчёты", name: "report", icon: "mdi-timetable" },
      { text: "Manager", name: "mlids", icon: "mdi-phone-log-outline" },
    ],
  }),
  mounted() {
    if (localStorage.themeDark) {
      if (localStorage.themeDark == "true") {
        this.$vuetify.theme.dark = true;
        this.themeDark = true;
      } else {
        this.$vuetify.theme.dark = false;
        this.themeDark = false;
      }
    }
  },
  computed: {
    managerComponent() {
      if (this.managerMenu == "mlids") return mlids;
      if (this.managerMenu == "report") return report;
    },
  },
  watch: {
    themeDark(newName) {
      localStorage.themeDark = newName;
      this.$vuetify.theme.dark = newName;
    },
  },
  methods: {
    sunmoon() {
      this.themeDark = !this.themeDark;
      this.managerMenu = "mlids";
      this.$nextTick(() => {
        this.managerMenu = "";
        this.$nextTick(() => {
          this.managerMenu = "mlids";
        });
      });
    },
  },
};
</script>
