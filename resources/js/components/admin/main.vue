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

      <v-toolbar-title>Пользователь в системе: Admin ВЫХОД</v-toolbar-title>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" fixed temporary>
      <!-- menu -->
  <v-card
    class="mx-auto"
    max-width="300"
    tile
  >
    <v-list disabled>
      <v-subheader>REPORTS</v-subheader>
      <v-list-item-group
        v-model="selectedItem"
        color="primary"
      >
        <v-list-item
          v-for="(item, i) in items"
          :key="i"
        >
          <v-list-item-icon>
            <v-icon v-text="item.icon"></v-icon>
          </v-list-item-icon>
          <v-list-item-content>
            <v-list-item-title v-text="item.text" v-on:click="adminMenu = 'item.name'"></v-list-item-title>
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
import tablenewlid from './tablenewlid';
const importcsv  = () => import("./importcsv");
// const tableNewLid = () => import("./components/admin/tablenewlid");
// const User = () => import("./components/User");
// const Member = () => import("./components/Member");

  // import { component } from "vue/types/umd";
export default {
    data: () => ({
       drawer: null,
      selectedItem: 1,
      items: [
        { text: 'Импорт CSV',name:'importcsv', icon: 'mdi-progress-upload' },
        { text: 'Пользователи',name: 'users', icon: 'mdi-account' },
        { text: 'Статусы лидов',name: 'statusLid', icon: 'mdi-list-status' },
        { text: 'Рабочие места',name: 'workPlaces', icon: 'mdi-account-network' },
      ],
      adminMenu: "importcsv"
    }),
  computed: {
    adminComponent() {
      if (this.adminMenu == "importcsv") return importcsv;
      if (this.adminMenu == "tablenewlid") return tablenewlid;
      // if (this.adminMenu  == 'two') return User;
      // if (this.adminMenu == 'three') return Member;
    },
  },
  // components:{
  //   tablenewlid
  // }
};
</script>
