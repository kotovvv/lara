<template>
  <div>
    <v-container fluid>
      <v-row>
        <v-col cols="1" class="pt-3 mt-4">
          <v-select
            v-model="filterStatus"
            :items="statuses"
            label="Фильтр по статусам"
            item-text="name"
            item-value="id"
          ></v-select>
          <v-btn
            v-if="
              filterStatus &&
              $props.user.role_id == 1 &&
              filteredItems.length > 0
            "
          >
            <v-icon small @click="deleteItem()"> mdi-delete </v-icon>
          </v-btn>
        </v-col>
        <v-col cols="2" class="pt-3 mt-4">
          <v-select
            v-model="filterGStatus"
            :items="statuses"
            label="Глобальный фильтр по статусам"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>

        <v-col cols="2" class="pt-3 mt-4">
          <v-select
            v-model="filterProviders"
            :items="providers"
            label="Фильтр по поставщикам"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>

        <v-col cols="2">
          <v-card-title>
            <v-text-field
              v-model="searchAll"
              append-icon="mdi-magnify"
              label="Глобальный поиск"
              @click:append="searchInDB"
              single-line
              hide-details
            ></v-text-field>
          </v-card-title>
        </v-col>
        <v-col cols="2">
          <v-card-title>
            <v-text-field
              v-model.lazy.trim="filtertel"
              append-icon="mdi-phone"
              label="Первые цифры телефона"
              single-line
              hide-details
            ></v-text-field>
          </v-card-title>
        </v-col>

        <v-col cols="3" class="pt-3 mt-4">
          <v-select
            v-model="selectedStatus"
            :items="statuses"
            label="Назначение статусов"
            item-text="name"
            item-value="id"
          ></v-select>
          <v-btn
            v-if="selectedStatus && selected.length"
            class="ma-2"
            outlined
            @click="changeStatus"
          >
            Сменить статусы
          </v-btn>
        </v-col>
      </v-row>
    </v-container>

    <v-row>
      <v-col cols="9">
        <v-card>
          <v-data-table
            v-model.lazy.trim="selected"
            :headers="headers"
            :search="search"
            :single-select="false"
            item-key="id"
            show-select
            @click:row="clickrow"
            :items="filteredItems"
            ref="datatable"
            :footer-props="{
              'items-per-page-options': [10, 50, 100, 250, 500, -1],
              'items-per-page-text': 'Показать',
            }"
          >
            <template
              v-slot:top="{ pagination, options, updateOptions }"
              :footer-props="{
                'items-per-page-options': [10, 50, 100, 250, 500, -1],
                'items-per-page-text': 'Показать',
              }"
            >
              <v-row>
                <v-col cols="4">
                  <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Поиск"
                    single-line
                    hide-details
                    class="ml-3"
                  ></v-text-field>
                </v-col>
                <!-- v-if="telsDuplicates.length > 0" -->
                <v-col cols="2">
                  <v-card-title>
                    <v-checkbox
                      v-model="showDuplicates"
                      class="mt-0"
                      @click="getDuplicates"
                    >
                      <template v-slot:label>
                        <v-icon small> mdi-phone </v-icon>
                        <v-icon small> mdi-phone </v-icon>
                      </template>
                      ></v-checkbox
                    >
                  </v-card-title>
                </v-col>
                <v-col cols="6">
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[10, 50, 100, 250, 500, -1]"
                    :items-per-page-text="'Показать'"
                  />
                </v-col>
              </v-row>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
      <v-col cols="3">
        <v-card height="100%" class="pa-5">
          Укажите пользователя
          <v-list>
            <v-radio-group
              @change="changeLidsUser"
              ref="radiogroup"
              v-model="userid"
              v-bind="users"
              id="usersradiogroup"
            >
              <v-row v-for="user in users" :key="user.id">
                <v-radio
                  :label="user.fio"
                  :value="user.id"
                  :disabled="disableuser == user.id"
                >
                </v-radio>

                <v-btn
                  class="ml-3"
                  small
                  :color="usercolor(user)"
                  @click="getLids(user.id)"
                  :value="user.hmlids"
                  :disabled="disableuser == user.id"
                  >{{ user.hmlids }}</v-btn
                >
              </v-row>
            </v-radio-group>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";

export default {
  props: ["user"],
  data: () => ({
    datetime: "",
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    selectedStatus: 0,
    filterStatus: 0,
    filterGStatus: 0,
    filterProviders: 0,
    selected: [],
    lids: [],
    search: "",
    searchAll: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
      { text: "Сообщение", value: "text" },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
    providers: [],
    showDuplicates: false,
    telsDuplicates: [],
  }),
  mounted: function () {
    this.getProviders();
    this.getUsers();
    this.getStatuses();
  },
  watch: {
    filterGStatus: function (newval, oldval) {
       if (newval == 0) {
        this.getLids(this.$props.user.id);
      } else {
        this.getStatusLids(newval);
      }
    },
  },
  computed: {
    filteredItems() {
      // if (this.showDuplicates && this.telsDuplicates.length > 0)
      //   return this.telsDuplicates;
      let reg = new RegExp("^" + this.filtertel);
      return this.lids.filter((i) => {
        return (
          (!this.filterStatus || i.status_id == this.filterStatus) &&
          (!this.filterProviders || i.provider_id == this.filterProviders) &&
          (!this.filtertel || reg.test(i.tel)) &&
          (!this.showDuplicates || this.telsDuplicates.includes(i.id))
        );
      });
    },
  },
  methods: {
    getDuplicates() {
      this.telsDuplicates = this.lids
        .filter(this.duplicatesOnly)
        .map((f) => f.id);
    },
    duplicatesOnly(v1, i1, self) {
      let ndx = self.findIndex(function (v2, i2) {
        // make sure not looking at the same object (using index to verify)
        // use JSON.stringify for object comparison
        return i1 != i2 && v1.tel == v2.tel;
      });
      return i1 != ndx && ndx != -1;
    },
    searchInDB() {
      let self = this;
      axios
        .get("api/Lid/searchlids?search=" + self.searchAll)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.user = self.users.find((u) => u.id == e.user_id).fio;
            e.date_created = e.created_at.substring(0, 10);
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          self.disableuser = 0;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    deleteItem() {
      const self = this;
      let ids = [];
      this.filteredItems.forEach(function (el) {
        ids.push(el.id);
        self.lids.splice(
          self.lids.indexOf(self.lids.find((l) => l.id == el.id)),
          1
        );
      });

      axios
        .post("api/Lid/deletelids", ids)
        .then(function (response) {
          self.getUsers();
          // self.getLids(send.user_id);
          self.filterStatus = 0;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    changeLidsUser() {
      const self = this;
      let cur_user_id = this.disableuser;
      let send = {};
      send.user_id = this.userid;

      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else if (
        (this.search !== "" ||
          this.filtertel !== "" ||
          this.filterStatus !== 0 ||
          this.filterGStatus !== 0) &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
      }
      if (self.$props.user.role_id == 2) {
        //CallBack user not change
        send.data = send.data.filter((f) => f.status_id != 9);
      }
      axios
        .post("api/Lid/changelidsuser", send)
        .then(function (response) {
          self.search = "";
          self.filtertel = "";
          self.userid = null;
          self.$refs.radiogroup.lazyValue = null;
          self.selected = [];
          self.filterStatus = 0;
          self.getUsers();
          self.getLids(cur_user_id);
        })
        .catch(function (error) {
          console.log(error);
        });
      self.searchAll = "";
    },
    putSelectedLidsDB() {
      const self = this;
      let send = {};
      send.user_id = this.userid;

      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else if (
        (this.search !== "" || this.filtertel !== "") &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            self.getUsers();
            self.search = "";
            self.filtertel = "";
          })
          .catch(function (error) {
            console.log(error);
          });
      }
      if (this.lids.length == 0) {
        this.files = [];
      }
      this.userid = null;
      this.$refs.radiogroup.lazyValue = null;
      this.getUsers();
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },

    clickrow() {
      console.log("You can click on row))");
    },
    getUsers() {
      let self = this;
      let get = self.$props.user.role_id == 1 ? "/api/users" : "/api/getusers";
      axios
        .get(get)
        .then((res) => {
          self.users = res.data.map(
            ({ name, id, role_id, fio, hmlids, group_id }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
            })
          );
          if (self.$props.user.role_id != 1) {
            self.users = self.users.filter(
              (f) => f.group_id == self.$props.user.group_id
            );
          }
        })
        .catch((error) => console.log(error));
    },

    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ name, id, color }) => ({
            name,
            id,
            color,
          }));
          self.statuses.unshift({ name: "Default", id: 0 });
          self.getLids(self.$props.user.id);
        })
        .catch((error) => console.log(error));
    },

    getStatusLids(id) {
      let self = this;
      self.filterStatus = 0;
      self.search = "";
      self.filtertel = "";
      axios
        .get("/api/statuslids/" + id)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.user = self.users.find((u) => u.id == e.user_id).fio;
            e.date_created = e.created_at.substring(0, 10);
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          self.searchAll = "";
          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    getLids(id) {
      let self = this;
      self.filterStatus = 0;
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.user = self.users.find((u) => u.id == e.user_id).fio;
            e.date_created = e.created_at.substring(0, 10);
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          self.searchAll = "";
          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    changeStatus() {
      const self = this;
      let send = {};
      if (this.selected.length && this.selectedStatus) {
        this.selected.map(function (e) {
          e.status_id = self.selectedStatus;
          e.status = self.statuses.find((s) => s.id == e.status_id).name;
        });
        send.data = this.selected.map((e) => e);
        this.changeLids(send);
      }
    },
    changeLids(send) {
      const self = this;
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          self.afterUpdateLids();
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    afterUpdateLids() {
      const self = this;
      self.selected = [];
      self.selectedStatus = 0;
      self.getLids(self.disableuser);
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
          self.providers.unshift({ name: "выбор", id: 0 });
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
