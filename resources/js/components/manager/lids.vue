<template>
  <div>
    <v-container fluid>
      <v-row>
        <v-col cols="3" class="pt-3 mt-4">
          <v-select
            v-model="selectedStatus"
            :items="statuses"
            label="Status"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>
        <v-col cols="3">
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
        <v-col cols="3">
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
    </v-container>
    <v-main>
      <v-row>
        <v-col cols="8">
          <v-card>
            <v-data-table
              v-model.lazy.trim="selected"
              :headers="headers"
              :search="search"
              :single-select="false"
              item-key="id"
              show-select
              @click:row="clickrow"
              :items="lids"
              ref="datatable"
            ></v-data-table>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card height="100%" class="pa-5">
            Select user for filtered lids

            <v-list>
              <v-radio-group
                @change="putSelectedLidsDB"
                ref="radiogroup"
                v-model="userid"
                v-bind="users"
                id="usersradiogroup"
              >
                <v-radio :value="user.id" v-for="user in users" :key="user.id">
                  <template v-slot:label>
                    {{ user.fio }}
                    <v-badge
                      :content="user.hmlids"
                      :value="user.hmlids"
                      :color="usercolor(user)"
                      overlap
                    >
                      <v-icon large v-if="user.role_id === 2">
                        mdi-account-group-outline
                      </v-icon>
                      <v-icon large v-else> mdi-account-outline </v-icon>
                    </v-badge>
                  </template>
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
      { text: "Manager", value: "manager" },
    ],
    parse_header: [],
    parse_csv: [],
    sortOrders: {},
    sortKey: "tel",
  }),
  mounted: function () {
    this.getUsers();
    this.getStatuses();
    this.getLids();
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
    putSelectedLidsDB() {
      let self = this;
      let send = {};
      send.user_id = this.userid;
      send.provider_id = this.selectedProvider;
      if (this.selectedStatus !== "") {
        send.status_id = this.selectedStatus;
      }

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
    getLids() {
      let self = this;
      axios
        .get("/api/lids")
        .then((res) => {
          console.log(res.data)
          self.lids =Object.entries(res.data).map(e => e[1])
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
