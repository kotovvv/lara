<template>
  <v-app id="inspire">
    <!-- <v-system-bar app>
      <v-spacer></v-spacer>

      <v-icon>mdi-square</v-icon>

      <v-icon>mdi-circle</v-icon>

      <v-icon>mdi-triangle</v-icon>
    </v-system-bar> -->

    <v-app-bar app>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

      <v-toolbar-title>Пользователь в системе: Admin</v-toolbar-title>
      <v-spacer></v-spacer>
      <button>ВЫХОД</button>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" fixed temporary @click="drawer = null">
      <!-- menu -->
  <v-card
    class="mx-auto"
    max-width="300"
    tile
  >
    <v-list>
      <v-subheader>MENU</v-subheader>
      <v-list-item-group
        v-model="selectedItem"
        color="primary"
      >
        <v-list-item
          v-for="(item, i) in items"
          :key="i"
          @click="adminMenu = item.name"
        >
          <v-list-item-icon>
            <v-icon v-text="item.icon"></v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title v-text="item.text"></v-list-item-title>
          </v-list-item-content>
        </v-list-item>
      </v-list-item-group>
    </v-list>
  </v-card>
    </v-navigation-drawer>

    <v-main class="grey lighten-2">
      <v-container fluid>
        <!-- <v-row> -->
          <!-- table -->
          <component :is="adminComponent" />
          <!-- <tablenewlid></tablenewlid> -->
        <!-- </v-row> -->
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
<<<<<<< HEAD
import tablenewlid from './tablenewlid';
const importcsv  = () => import("./importcsv");
// const tableNewLid = () => import("./components/admin/tablenewlid");
// const User = () => import("./components/User");
// const Member = () => import("./components/Member");
=======
const users = () => import("./users.vue");
const importcsv  = () => import("./importcsv.vue");
const statusLid = () => import("./statusLid.vue");
const workPlaces = () => import("./workPlaces");
>>>>>>> c1ece9d23d6900f554d1b26fb161887767e380a4

export default {
    data: () => ({
       drawer: null,
      selectedItem: 0,
      items: [
        { text: 'Импорт CSV',name:'importcsv', icon: 'mdi-progress-upload' },
        { text: 'Пользователи',name: 'users', icon: 'mdi-account' },
        { text: 'Статусы лидов',name: 'statusLid', icon: 'mdi-format-list-checks' },
        { text: 'Рабочие места',name: 'workPlaces', icon: 'mdi-sitemap' },
      ],
      adminMenu: "importcsv"
    }),
  computed: {
    adminComponent() {
      if (this.adminMenu == "importcsv") return importcsv;
      if (this.adminMenu == "users") return users;
      if (this.adminMenu == "statusLid") return statusLid;
      if (this.adminMenu == "workPlaces") return workPlaces;
    },
  },
  //  components:{
  //   'importcsv':() => import("./components/admin/importcsv.vue")
  //  }
};
</script>
