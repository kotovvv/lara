<template>
  <div>
    <v-main>
      <v-row>
        <v-col cols="8">
          <v-row>
            <v-col cols="4">
              <v-card-title>
                <v-text-field
                  v-model="search"
                  append-icon="mdi-magnify"
                  label="Search"
                  single-line
                  hide-details
                ></v-text-field>
              </v-card-title>
            </v-col>
            <v-col cols="4">
              <v-card-title>
                <v-text-field
                  v-model.lazy.trim="filtertel"
                  append-icon="mdi-phone"
                  label="Start number"
                  single-line
                  hide-details
                ></v-text-field>
              </v-card-title>
            </v-col>
          </v-row>

          <v-card>
            <v-data-table
              v-model.lazy.trim="selected"
              :headers="headers"
              :search="search"
              :single-select="true"
              item-key="id"
              show-select
              @click:row="clickrow"
              :items="filteredItems"
              ref="datatable"
            >
  <template v-slot:expanded-item="{ headers, item }">
        <td :colspan="headers.length">
          More info about {{ item.name }}
        </td>
      </template>
            </v-data-table>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card height="100%" class="pa-5">
            <!-- <v-col cols="3" class="pt-3 mt-4">
          <v-select
            v-model="selectedStatus"
            :items="statuses"
            label="Status"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col> -->
            <v-list>
              <v-radio-group
                @change="putSelectedLidsDB"
                ref="radiogroup"
                id="statusesradiogroup"
                v-model="selectedStatus"
                :disabled="selected.length == 0"
              >
                <!-- v-bind="selected.length?selected[0].status_id:null" -->
                <!-- v-model="selectedStatus" -->
                <v-radio
                  :label="status.name"
                  :value="status.id"
                  v-for="status in statuses"
                  :key="status.id"
                >
                </v-radio>
              </v-radio-group>
            </v-list>
          </v-card>
        </v-col>
      </v-row>
    </v-main>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data: () => ({
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    selectedStatus: 0,
    selected: [],
    lids: [],
    search: "",
    filtertel: "",
    headers: [
      { text: "Name", value: "name" },
      { text: "Email", value: "email" },
      { text: "Tel.", align: "start", value: "tel" },
      { text: "Status", value: "status" },
      { text: "Date", value: "updated_at" },
      // { text: "Message", value: "text" },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
  }),
  mounted: function () {
    this.getUsers();
    this.getStatuses();
    this.getLids(3);
  },
  watch: {
    selected: function (newval, oldval) {
      // console.log(newval)
      // console.log(oldval)
      if (this.selected.length == 0) {
        this.selectedStatus = null;
      } else this.selectedStatus = newval[0].status_id;
    },
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.lids.filter((i) => {
        return !this.filtertel || reg.test(i.tel);
      });
    },
  },
  methods: {
    currentDateTime() {
      const date = new Date();
      // 01, 02, 03, ... 29, 30, 31
      const dd = (date.getDate() < 10 ? "0" : "") + date.getDate();
      // 01, 02, 03, ... 10, 11, 12
      const MM = (date.getMonth() + 1 < 10 ? "0" : "") + (date.getMonth() + 1);
      // 1970, 1971, ... 2015, 2016, ...
      const yyyy = date.getFullYear();
      const time = date.getHours() + ":" + date.getMinutes();
      // create the format you want
      return yyyy + "-" + MM + "-" + dd + " " + time;
    },
    putSelectedLidsDB() {
      const self = this;
      let send = {};
      let send_el = {};
      let costil = self.filtertel;
      self.filtertel = 1;
      self.filtertel = costil;

      let eli = self.lids.find((obj) => obj.id == self.selected[0].id);

      eli.status = self.statuses.find((s) => s.id === self.selectedStatus).name;
      eli.status_id = self.selectedStatus;
      eli.updated_at = self.currentDateTime();
      send.id = eli.id;
      send_el.tel = eli.tel;
      send_el.status_id = self.selectedStatus;
      send_el.user_id = eli.user_id;
      send_el.text = "some text";
      send.data = send_el;
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          console.log(response);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },

    clickrow() {
      console.log("You can click on row))");
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/users/getusers")
        .then((res) => {
          self.users = res.data.map(({ name, id, role_id, fio, hmlids }) => ({
            name,
            id,
            role_id,
            fio,
            hmlids,
          }));
        })
        .catch((error) => console.log(error));
    },

    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ name, id }) => ({
            name,
            id,
          }));
        })
        .catch((error) => console.log(error));
    },

    getLids(id) {
      console.log(id);
      let self = this;
      let a_temp = [];
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.user = self.users.find((u) => u.id === e.user_id).fio;
            e.status = self.statuses.find((s) => s.id === e.status_id).name;
            delete e.provider_id;
            e.updated_at = e.updated_at.substring(0, 16).replace("T", " ");
          });
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
