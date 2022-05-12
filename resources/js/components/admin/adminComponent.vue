<template>
  <v-app id="inspire">
    <v-navigation-drawer
      v-model="drawer"
      fixed
      parmament
      dark
      :mini-variant=true
      width="64px"
      :app=true
    >
      <!-- menu -->
      <v-list>
        <v-subheader></v-subheader>
        <v-list-item-group v-model="selectedItem" color="primary">
          <v-list-item
            v-for="(item, i) in items"
            :key="i"
            @click="adminMenu = item.name"
          >
            <v-list-item-icon>
              <v-icon v-text="item.icon"></v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title></v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>

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
const users = () => import("./users");
const importcsv = () => import("./importcsv");
const statusLid = () => import("./statusLid");
// const workPlaces = () => import("./workPlaces");
const providers = () => import("./providers");
const mlids = () => import("../manager/mlids");
const lids = () => import("../crmanager/lids");
const lidsplus = () => import("../crmanager/lidsplus");
const report = () => import("./report");

export default {
  props: ["user"],
  data: () => ({
    drawer: null,
    selectedItem: 0,

    items: [
      {
        text: "Импорт CSV",
        name: "importcsv",
        icon: "mdi-arrow-down-bold-box-outline",
      },
      { text: "Пользователи", name: "users", icon: "mdi-account" },
      {
        text: "Статусы лидов",
        name: "statusLid",
        icon: "mdi-format-list-checks",
      },
      {
        text: "Поставщики",
        name: "providers",
        icon: "mdi-contact-phone-outline",
      },
      // { text: "Рабочие места", name: "workPlaces", icon: "mdi-sitemap" },
      { text: "Распределение", name: "lids", icon: "mdi-account-arrow-left" },
      { text: "Распределение2", name: "lidsplus", icon: "mdi-filter-outline" },
      { text: "Управление", name: "mlids", icon: "mdi-phone-log-outline" },
      { text: "Отчёт", name: "report", icon: "mdi-receipt" },
    ],
    adminMenu: "",
  }),
  computed: {
    adminComponent() {
      if (this.adminMenu == "importcsv") return importcsv;
      if (this.adminMenu == "users") return users;
      if (this.adminMenu == "statusLid") return statusLid;
      // if (this.adminMenu == "workPlaces") return workPlaces;
      if (this.adminMenu == "providers") return providers;
      if (this.adminMenu == "mlids") return mlids;
      if (this.adminMenu == "lids") return lids;
      if (this.adminMenu == "lidsplus") return lidsplus;
      if (this.adminMenu == "report") return report;
    },
  },
  mounted: function () {
    if (localStorage.adminMenu) {
      this.adminMenu = localStorage.adminMenu;
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
