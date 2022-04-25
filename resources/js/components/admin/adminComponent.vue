<template>
  <v-app id="inspire">
    <v-navigation-drawer
      v-model="drawer"
      fixed
      parmament
      dark
      mini-variant="true"
      width="64px"
      app="true"
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
.v-list-item.v-item--active {
  background: #fff;
  border-top-left-radius: 16px;
  border-bottom-left-radius: 16px;
}
#inspire .theme--dark.v-navigation-drawer {
  background-color: #7620df;
}
#inspire .v-navigation-drawer {
  overflow: inherit;
}
#inspire .v-navigation-drawer:before,
#inspire .v-navigation-drawer:after,
#inspire .v-list-item.v-item--active:before,
#inspire .v-list-item.v-item--active:after {
  content: "";
  display: block;
  width: 16px;
  height: 16px;
  position: absolute;
  right: -16px;
  top: 0;
  background: #7620df;
  clip-path: polygon(
    0% 98%,
    0% 98%,
    0% 0%,
    0% 0%,
    100% 0%,
    100% 0%,
    82% 1%,
    82% 1%,
    78% 2%,
    78% 2%,
    73% 3%,
    73% 3%,
    68% 4%,
    68% 4%,
    63% 6%,
    63% 6%,
    58% 8%,
    58% 8%,
    54% 10%,
    54% 10%,
    50% 12%,
    50% 12%,
    47% 15%,
    47% 15%,
    43% 17%,
    43% 17%,
    40% 19%,
    40% 19%,
    36% 22%,
    36% 22%,
    33% 25%,
    33% 25%,
    30% 28%,
    30% 28%,
    27% 31%,
    27% 31%,
    24% 35%,
    24% 35%,
    21% 38%,
    21% 38%,
    18% 42%,
    18% 42%,
    15% 46%,
    15% 46%,
    13% 50%,
    13% 50%,
    10% 54%,
    10% 54%,
    8% 58%,
    8% 58%,
    6% 62%,
    6% 62%,
    5% 67%,
    5% 67%,
    3% 72%,
    3% 72%,
    2% 77%,
    2% 77%,
    1% 83%,
    1% 83%
  );
}
#inspire .v-navigation-drawer:before {
  right: -16px;
  top: 0;
}
#inspire .v-navigation-drawer:after {
  right: -16px;
  top: calc(100% - 16px);
  transform: rotateZ(-90deg);
}
#inspire .v-list-item.v-item--active:before,
#inspire .v-list-item.v-item--active:after {
  background: #fff;
  opacity: 1;
}
#inspire .v-list-item.v-item--active:before {
  left: calc(100% - 16px);
  transform: rotateZ(180deg);
  top: -15px;
}
#inspire .v-list-item.v-item--active:after {
  left: calc(100% - 16px);
  transform: rotateZ(-270deg);
  bottom: -16px;
  min-height: auto;
  top: unset;
}
</style>