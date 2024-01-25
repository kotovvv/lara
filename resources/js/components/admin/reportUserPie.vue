<template>
  <v-container fluid>
    <v-row>
      <v-col cols="3">
        <div class="status_wrp wrp_date pl-5">
          <v-row align="center">
            <v-col>
              <v-menu
                v-model="dateFrom"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateTimeFrom"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="dateTimeFrom"
                  @input="
                    dateFrom = false;
                    pieUser();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
            <v-col>
              <v-menu
                v-model="dateTo"
                :close-on-content-click="false"
                :nudge-right="40"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dateTimeTo"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="dateTimeTo"
                  @input="
                    dateTo = false;
                    pieUser();
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
          </v-row>
        </div>
      </v-col>
      <v-col
        cols="3"
        v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0"
      >
        <v-select
          v-model="filterOffices"
          :items="offices"
          item-text="name"
          item-value="id"
          outlined
          rounded
          @change="
            getUsers();
            getPage(0);
          "
        >
        </v-select>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="8">
        <PieChart :datap="chartDataTime" />
      </v-col>
      <v-col cols="4">
        <div class="pa-5 w-100 border wrp_users">
          <div class="my-3">Поиск пользователей</div>
          <v-autocomplete
            v-model="selectedUser"
            :items="users"
            label="Выбор"
            item-text="fio"
            item-value="id"
            :return-object="true"
            append-icon="mdi-close"
            outlined
            rounded
            @click:append="clearuser()"
          ></v-autocomplete>

          <div class="scroll-y">
            <v-list>
              <v-radio-group
                id="usersradiogroup"
                ref="radiogroup"
                v-model="userid"
                v-bind="users"
                @change="changeLidsUser"
              >
                <div
                  v-for="office in filterOffices == 0
                    ? offices
                    : offices.filter((o) => o.id == filterOffices)"
                  :key="office.id"
                >
                  <p class="title" v-if="office.id > 0">{{ office.name }}</p>
                  <v-expansion-panels v-model="akkvalue[office.id]">
                    <v-expansion-panel
                      v-for="item in group.filter(
                        (g) => g.office_id == office.id
                      )"
                      :key="item.group_id"
                    >
                      <v-expansion-panel-header>
                        <div
                          height="60"
                          width="60"
                          class="img v-expansion-panel-header__icon mr-1"
                        >
                          {{ item.fio.slice(0, 3) }}
                        </div>

                        {{ item.fio }}
                        <div></div>
                      </v-expansion-panel-header>
                      <v-expansion-panel-content>
                        <v-col
                          v-for="user in users.filter(function (i) {
                            return i.group_id == item.group_id;
                          })"
                          :key="user.id"
                        >
                          <v-radio
                            :label="user.fio"
                            :value="user.id"
                            :disabled="disableuser == user.id"
                          >
                          </v-radio>
                          <div class="mb-3 grp_btn">
                            <v-btn
                              small
                              :color="usercolor(user)"
                              @click="
                                disableuser = user.id;
                                filterGroups = [];
                                getPage();
                              "
                              :value="user.hmlids"
                              :disabled="disableuser == user.id"
                              >{{ user.hmlids }}</v-btn
                            >
                          </div>
                        </v-col>
                      </v-expansion-panel-content>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </div>
              </v-radio-group>
            </v-list>
          </div>
        </div>
      </v-col>
    </v-row>
    <v-row>
      <v-progress-linear
        :active="loading"
        :indeterminate="loading"
        color="deep-purple accent-4"
      ></v-progress-linear>
    </v-row>
  </v-container>
</template>

<script>
import PieChart from "../provider/pieComponents";
import axios from "axios";
import _ from "lodash";
export default {
  name: "ReportUserPie",
  components: {
    PieChart,
  },
  data() {
    return {
      loading: false,
      chartDataTime: {
        labels: [],
        datasets: [
          {
            backgroundColor: [],
            data: [],
          },
        ],
      },
      dateFrom: false,
      dateTo: false,
      dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substring(0, 10),
      dateTimeTo: new Date().toISOString().substring(0, 10),
      selectedUser: {},
      users: [],
      userid: null,
      disableuser: 0,
      offices: [],
      filterOffices: 1,
      akkvalue: [],
      group: null,
    };
  },

  mounted() {
    this.getUsers();
    this.getOffices();
  },
  watch: {
    selectedUser(user) {
      if (user == {}) {
        this.userid = null;
        this.akkvalue = [];
        return;
      }
      //this.disableuser = user.id;
      this.akkvalue = [];
      this.akkvalue[user.office_id] = _.findIndex(
        this.group.filter((g) => g.office_id == user.office_id),
        {
          group_id: user.group_id,
        }
      );
    },
  },
  methods: {
    pieUser() {
      const self = this;
      const user_id = this.user.id;
      self.loading = true;
      axios
        .get("api/pieUser/" + user_id + "/" + self.dateFrom + "/" + self.dateTo)
        .then(function (res) {
          self.chartDataTime.labels = res.data.labels;
          self.chartDataTime.datasets[0].backgroundColor =
            res.data.backgroundColor;
          self.chartDataTime.datasets[0].data = res.data.data;
          self.statuses_time = res.data.statuses;
          self.allLidsTime = self.statuses_time.reduce(
            (all, el) => all + el.hm,
            0
          );
          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    changeLidsUser() {
      const self = this;
      let send = {};
      send.data = [];
      send.user_id = this.userid;

      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else {
        this.userid = null;
        return false;
      }
      if (self.$attrs.user.role_id == 2) {
        //CallBack user not change
        send.data = send.data.filter((f) => f.status_id != 9);
      }
      axios
        .post("api/Lid/changelidsuser", send)
        .then(function (response) {
          self.search = "";
          self.$refs.radiogroup.lazyValue = null;
          self.selected = [];
          self.archSelected = [];
          // if (self.savedates == true) {
          //   self.disableuser = 0;
          // }

          // self.getUsers();
          // self.pieUser();
          // self.userid = null;
        })
        .catch(function (error) {
          console.log(error);
        });
      self.searchAll = "";
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    getOffices() {
      let self = this;
      self.filterOffices = self.$attrs.user.office_id;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
          if (self.$attrs.user.role_id == 1) {
            self.offices.unshift({ id: 0, name: "--выбор--" });
            self.filterOffices = self.offices[1].id;
          }
          if (self.$attrs.user.office_id > 0) {
            self.offices = self.offices.filter(
              (o) => o.id == self.$attrs.user.office_id
            );
          }
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;
      // let get = self.$attrs.user.role_id == 1 ? "/api/users" : "/api/getusers";
      let get = "/api/getusers";
      axios
        .get(get)
        .then((res) => {
          self.users = res.data.map(
            ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              order,
              statnew,
              pic,
              inp,
              cb,
              office_id,
              notint,
              noans,
              lang,
              trash,
            }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              pic,
              group_id,
              order,
              statnew,
              inp,
              cb,
              office_id,
              notint,
              noans,
              lang,
              trash,
            })
          );
          if (self.$attrs.user.role_id == 1 && self.filterOffices > 0) {
            self.users = self.users.filter(
              (f) => f.office_id == self.filterOffices
            );
          }
          if (self.$attrs.user.role_id != 1) {
            self.users = self.users.filter(
              (f) => f.group_id == self.$attrs.user.group_id
            );
          }
          self.group = self.getGroup();
        })
        .catch((error) => console.log(error));
    },
    getGroup() {
      return _.filter(this.users, function (o) {
        return o.group_id == o.id;
      });
    },
  },
};
</script>
