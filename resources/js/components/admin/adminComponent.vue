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
            @click="adminMenu = item.name"
            :title="item.text"
          >
            <div>
              <v-list-item-icon>
                <v-icon v-text="item.icon"></v-icon>
              </v-list-item-icon>
            </div>
            <v-list-item-content>
              <v-list-item-title></v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>

        <v-list-item-group class="mt-10">
          <v-list-item @click="$emit('logout')" title="Exit">
            <v-list-item-icon>
              <v-icon>mdi-logout</v-icon>
            </v-list-item-icon>
          </v-list-item>
          <v-list-item :title="user.fio">
            <v-list-item-content>
              <v-list-item-title>{{ user.fio }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer>

    <v-main class="">
      <!-- grey lighten-2 -->
      <v-container fluid>
        <!-- <v-row> -->
        <!-- table -->
        <component :user="$props.user" :is="adminComponent" />
        <!-- <tablenewlid></tablenewlid> -->
        <!-- </v-row> -->
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
const users = () => import("./users.vue");
const importcsvnew = () => import("./importcsvnew.vue");
const statusLid = () => import("./statusLid.vue");
// const workPlaces = () => import("./workPlaces.vue");
const providers = () => import("./providers.vue");
const mlids = () => import("../manager/mlids.vue");
const lids = () => import("../crmanager/lids.vue");
//const lidsplus = () => import("../crmanager/lidsplus.vue");
const calls = () => import("../crmanager/calls.vue");
const lids3 = () => import("../crmanager/lids3.vue");
const report = () => import("./report.vue");
const reportPie = () => import("./reportPie.vue");
const reportBTC = () => import("./reportBTC.vue");

export default {
  props: ["user"],
  data: () => ({
    drawer: null,
    selectedItem: 0,

    items: [
      {
        text: "Imports",
        name: "importcsvnew",
        icon: "mdi-arrow-down-bold-box-outline",
      },
      { text: "Пользователи", name: "users", icon: "mdi-account" },
      {
        text: "Статусы лидов",
        name: "statusLid",
        icon: "mdi-format-list-checks",
      },
      { text: "Поставщики", name: "providers", icon: "mdi-library" },
      // { text: "Рабочие места", name: "workPlaces", icon: "mdi-sitemap" },
      { text: "Распределение", name: "lids", icon: "mdi-account-arrow-left" },
      //{ text: "Распределение2", name: "lidsplus", icon: "mdi-filter-outline" },
      { text: "Распределение3", name: "lids3", icon: "mdi-sitemap" },
      { text: "Звонки", name: "calls", icon: "mdi-headset-dock" },
      { text: "Управление", name: "mlids", icon: "mdi-phone-log-outline" },
      { text: "Отчёт", name: "report", icon: "mdi-receipt" },
      { text: "Отчёты", name: "reportPie", icon: "mdi-timetable" },
      { text: "Отчёт по BTC", name: "reportBTC", icon: "mdi-cash" },
    ],
    adminMenu: "importcsvnew",
  }),
  computed: {
    adminComponent() {
      if (this.adminMenu == "importcsvnew") return importcsvnew;
      if (this.adminMenu == "users") return users;
      if (this.adminMenu == "statusLid") return statusLid;
      // if (this.adminMenu == "workPlaces") return workPlaces;
      if (this.adminMenu == "providers") return providers;
      if (this.adminMenu == "mlids") return mlids;
      if (this.adminMenu == "lids") return lids;
      //if (this.adminMenu == "lidsplus") return lidsplus;
      if (this.adminMenu == "lids3") return lids3;
      if (this.adminMenu == "calls") return calls;
      if (this.adminMenu == "report") return report;
      if (this.adminMenu == "reportPie") return reportPie;
      if (this.adminMenu == "reportBTC") return reportBTC;
    },
  },
  mounted: function () {
    if (localStorage.adminMenu) {
      this.adminMenu = localStorage.adminMenu;
      this.selectedItem = this.items
        .map((i) => i.name)
        .indexOf(localStorage.adminMenu);
    }
    if (this.$props.user.group_id > 0 || this.$props.user.office_id > 0) {
      this.items = this.items.filter((i) => {
        return !["statusLid", "providers", "lids"].includes(i.name);
      });
    }
  },
  watch: {
    adminMenu(newName) {
      localStorage.adminMenu = newName;
    },
  },
  methods: {},
};
</script>

<style>
</style>
