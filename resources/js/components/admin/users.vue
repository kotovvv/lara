<template>
<v-card
    class="mx-auto"
  >
    <!-- max-width="900" -->
      <v-data-table
      v-model="selected"
        :headers="headers"
        :items="users"
        sort-by="role_id"
        show-select
        class="elevation-1"
        :single-select=true
      >
        <template v-slot:top>
          <v-toolbar flat>
            <v-toolbar-title>Пользователи</v-toolbar-title>
            <v-divider class="mx-4" inset vertical></v-divider>
            <v-spacer></v-spacer>
            <v-dialog v-model="dialog" max-width="500px">
              <template v-slot:activator="{ on, attrs }">
                <!-- <v-btn color="primary" dark class="mb-2 ml-2" @click="report">
                  Отчёт
                </v-btn> -->
<statusUsers :o_users="selected" />
                <v-btn
                  color="primary"
                  dark
                  class="mb-2"
                  v-bind="attrs"
                  v-on="on"
                >
                  Добавить пользователя
                </v-btn>
              </template>
              <v-card>
                <v-card-title>
                  <span class="headline">{{ formTitle }}</span>
                </v-card-title>

                <v-card-text>
                  <v-container>
                    <v-row>
                      <v-col cols="12">
                        <v-text-field
                          v-model="editedItem.fio"
                          label="ФИО"
                        ></v-text-field>
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="editedItem.name"
                          label="Логин"
                        ></v-text-field>
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="editedItem.password"
                          label="Пароль"
                        ></v-text-field>
                      </v-col>
                      <v-col cols="6">
                        <v-select
                          :items="roles"
                          v-model="editedItem.role_id"
                          item-text="name"
                          item-value="id"
                          label="Роль"
                        ></v-select>
                      </v-col>
                      <v-col cols="6">
                        <v-select
                          :items="group"
                          v-model="editedItem.group_id"
                          item-text="fio"
                          item-value="id"
                          label="Группа"
                        ></v-select>
                      </v-col>
                      <v-col cols="6">
                        <v-switch
                          v-model="editedItem.active"
                          label="Показывать:"
                        ></v-switch>
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model="editedItem.balans"
                          label="Баланс"
                          type="number"
            :messages="lastBalans"
                        ></v-text-field>
                      </v-col>
                       </v-row>
                  </v-container>
                </v-card-text>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn color="blue darken-1" text @click="close">
                    Отмена
                  </v-btn>
                  <v-btn color="blue darken-1" text @click="save">
                    Сохранить
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>
            <v-dialog v-model="dialogDelete" max-width="500px">
              <v-card>
                <v-card-title class="headline"
                  >Уверены в удалении пользователя?</v-card-title
                >
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn color="blue darken-1" text @click="closeDelete"
                    >Нет</v-btn
                  >
                  <v-btn color="blue darken-1" text @click="deleteItemConfirm"
                    >Да</v-btn
                  >
                  <v-spacer></v-spacer>
                </v-card-actions>
              </v-card>
            </v-dialog>
          </v-toolbar>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-icon small class="mr-2" @click="editItem(item)">
            mdi-pencil
          </v-icon>
          <v-icon small @click="deleteItem(item)"> mdi-delete </v-icon>
        </template>
        <template v-slot:no-data>
          <v-btn color="primary" @click="getUsers"> Reset </v-btn>
        </template>
      </v-data-table>
</v-card>
</template>

<script>
import statusUsers from './statusUsers'
import axios from "axios";
export default {
  data: () => ({
    lastBalans:'',
    balans:'',
    selected:[],
    dialog: false,
    dialogDelete: false,
    users: [],
    group:[{fio:'Без группы', id:0}],
    roles: [
      { id: 1, name: "Administrator" },
      { id: 2, name: "CRM Manager" },
      { id: 3, name: "Manager" },
    ],
    headers: [
      { text: "Логин", value: "name" },
      { text: "ФИО", value: "fio" },
      { text: "Роль", value: "role" },
      { text: "Группа", value: "group_id" },
      { text: "Показывать", value: "active" },
      { text: "Действия", value: "actions", sortable: false },
    ],

    editedIndex: -1,
    editedItem: {
      name: "",
      fio: "",
      role_id: 0,
      password: "",
      active: 0,
      balans:''
    },
    defaultItem: {
      name: "",
      fio: "",
      role_id: 0,
      password: "",
      active: 0,
      balans:''
    },
  }),

  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? "Новый пользователь"
        : "Редактировать пользователя";
    },
  },

  watch: {
    dialog(val) {
      val || this.close();
    },
    dialogDelete(val) {
      val || this.closeDelete();
    },
  },

  created() {
    // this.initialize(),
    this.getUsers();
  },

  methods: {
    // report() {
    //   let selectedUsers = Object.keys(this.$refs.datausers.selection);
    //   if (selectedUsers.length) {
    //     let self = this;
    //     axios
    //       .post("/api/status_users", selectedUsers)
    //       .then((res) => {
    //         console.log(res.data);
    //       })
    //       .catch((error) => console.log(error));
    //   }
    // },
    rolename(user) {
      user.role = (this.roles.find((r) => r.id == user.role_id)).name;
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/users")
        .then((res) => {
          self.users = res.data;
          self.users.map(function (u) {
            u.role = (self.roles.find((r) => r.id == u.role_id)).name;
            if (u.role_id == 2) self.group.push(u)
            u.balans = ''
          });
        })
        .catch((error) => console.log(error));
    },
    saveUsers(u) {
      let self = this;
      axios
        .post("/api/user/update", u)
        .then((res) => {
          console.log(res);
        })
        .catch((error) => console.log(error));
    },
    initialize() {},
    editItem(item) {
      this.editedIndex = this.users.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.getLastBalans(this.editedItem.id)
      this.dialog = true;
    },
getLastBalans(user_id){
        let self = this;
      axios
        .get("/api/lastBalans/" + user_id)
        .then((res) => {
          self.lastBalans = res.data
        })
        .catch((error) => console.log(error));
  // return ['600 (2021-12-12)']
},
    deleteItem(item) {
      this.editedIndex = this.users.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialogDelete = true;
    },

    deleteItemConfirm() {
      axios
      .delete("/api/user/"+ this.editedItem.id)
      .then((res) => {
        console.log(res);
      })
      .catch((error) => console.log(error));
      this.users.splice(this.editedIndex, 1);
        this.closeDelete();
       },

    close() {
      this.dialog = false;
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    closeDelete() {
      this.dialogDelete = false;
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      if (this.editedIndex > -1) {
        delete this.editedItem.role;
        this.saveUsers(this.editedItem);
        this.rolename(this.editedItem);
        Object.assign(this.users[this.editedIndex], this.editedItem);
      } else {
        delete this.editedItem.role;
        this.saveUsers(this.editedItem);
        this.rolename(this.editedItem);
        this.users.push(this.editedItem);
      }
      this.close();
    },
  },
  components:{
    statusUsers
  }
};
</script>
