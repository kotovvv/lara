<template>
  <div>
    <v-row>
      <v-col cols="10">
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
          <v-col cols="4" class="pt-3 mt-4">
            <v-select
              v-model="filterStatus"
              :items="filterstatuses"
              label="Status"
              item-text="name"
              item-value="id"
            ></v-select>
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
            :items="filteredItems"
            ref="datatable"
            :expanded="expanded"
            :footer-props="{
              'items-per-page-options': [10, 50, 100, 250, 500, -1],
              'items-per-page-text': 'Показать',
            }"
            @click:row="clickrow"
          >
            <template v-slot:item.tel="{ item }">
              <td class="tel" @click="call(item.tel)">{{ item.tel }}</td>
            </template>
            <template v-slot:item.status="{ item }">
              <td class="px-1" :style="color(item.status_id)">{{ item.status }}</td>
            </template>
            <!-- expand -->
            <!-- :expanded="expanded" -->
            <!-- show-expand -->
            <template v-slot:expanded-item="{ headers, item }">
              <td :colspan="headers.length">
                <v-row>
                  <v-col cols="8">
                    <v-textarea
                      class="mx-2"
                      label="message"
                      rows="1"
                      prepend-icon="mdi-comment"
                      v-model="item.text"
                      :value="item.text"
                      @change="changemes(item)"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="4">
                    <v-datetime-picker
                      label="Select Datetime"
                      v-model="datetime"
                    >
                    </v-datetime-picker>
                  </v-col>
                </v-row>
              </td>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
      <v-col cols="2">
        <v-card height="100%" class="pa-5">
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
                <span slot="label" class="px-1" :style="{ background: status.color}">{{
                  status.name
                }}</span>
              </v-radio>
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
    perpage: [10, 50, 100, 250, 500, -1],
    expanded: ["Donut"],
    singleExpand: true,
    datetime: "",
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    filterstatuses: [],
    selectedStatus: 0,
    filterStatus: 0,
    selected: [],
    lids: [],
    search: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Статус", value: "status" },
      { text: "Дата", value: "date" },
      // { text: "Message", value: "text" },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
  }),
  mounted: function () {
    // this.getUsers();
    this.getStatuses();
  },
  watch: {
    selected: function (newval, oldval) {
      // console.log(newval)
      // console.log(oldval)
      if (this.selected.length == 0) {
        this.selectedStatus = null;
        this.expanded = [];
      } else {
        this.selectedStatus = newval[0].status_id;
        this.expanded = this.selected;
        // props.expanded = !props.expanded
      }
    },
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.lids.filter((i) => {
        if (this.filterStatus)
          return !this.filterStatus || i.status_id === this.filterStatus;
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
    changemes(eli) {
      const self = this;
      let send = {};
      let send_el = {};
      send_el.tel = eli.tel;
      send_el.text = eli.text;
      send.id = eli.id;
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

    clickrow(item,row) {
      row.select(!row.isSelected);
      if (row.isSelected) console.log(item.tel);
    },
    // getUsers() {
    //   let self = this;
    //   axios
    //     .get("/api/users/getusers")
    //     .then((res) => {
    //       self.users = res.data.map(({ name, id, role_id, fio, hmlids }) => ({
    //         name,
    //         id,
    //         role_id,
    //         fio,
    //         hmlids,
    //       }));
    //     })
    //     .catch((error) => console.log(error));
    // },

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
          self.filterstatuses = self.statuses.map((e) => e);
          self.filterstatuses.unshift({ name: "" });
          self.getLids(self.$props.user.id);
        })
        .catch((error) => console.log(error));
    },

    getLids(id) {
      let self = this;
      let a_temp = [];
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            // e.user = self.users.find((u) => u.id === e.user_id).fio;
            delete e.provider_id;
            e.date = e.updated_at.substring(0, 10);
            e.status =
              self.statuses.find((s) => s.id === e.status_id).name || "";
          });
        })
        .catch((error) => console.log(error));
    },
    call(tel) {
      alert(tel);
    },
    color(status_id){
return 'padding:0 5px 0 5px;background:'+this.statuses.find((e) => e.id === status_id ).color
    },
  },
};
</script>

<style scoped>
.tel:hover {
  cursor: url(/img/cellphone-sound.svg) 10 10, none;
}

</style>
