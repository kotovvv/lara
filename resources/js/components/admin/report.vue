<template>
  <div>
    <v-row>
      <v-col>
        <!-- <v-card class="pa-5 w-100"> -->
        <v-expansion-panels>
          <v-expansion-panel v-for="(item, i) in group" :key="i">
            <v-expansion-panel-header>
              {{ item.fio }}
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <v-data-table
                :headers="tableHeaders"
                :items="tableData.filter((i) => i.group_id == item.group_id)"
                :single-expand="singleExpand"
                :expanded.sync="expanded"
                item-key="id"
                show-expand
                class="elevation-1"
                :disable-pagination="true"
              >
                <template v-slot:item.deleteData="{ item }">
                  <v-btn color="primary" dark> Стереть </v-btn>
                </template>
                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length">
                    More info about {{ item.name }}
                  </td>
                </template>
              </v-data-table>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
        <!-- </v-card> -->
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="4">
        <div>
          <v-card class="pa-5 w-100">
            Укажите пользователя
            <v-card-text class="scroll-y">
              <v-list>
                <v-expansion-panels>
                  <v-expansion-panel v-for="(item, i) in group" :key="i">
                    <v-expansion-panel-header>
                      {{ item.fio }}
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <v-row
                        v-for="user in users.filter(function (i) {
                          return i.group_id == item.group_id;
                        })"
                        :key="user.id"
                      >
                        <v-btn
                          class="ma-3 w-100"
                          small
                          @click="getData(user.id)"
                          :value="user.id"
                          >{{ user.fio }}</v-btn
                        >
                      </v-row>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-list>
            </v-card-text>
          </v-card>
        </div>
      </v-col>

      <v-col cols="8">
        <v-card>
          <v-card-title>
            {{ user_fio }}
          </v-card-title>
        </v-card>

        <v-simple-table>
          <template v-slot:default>
            <thead>
              <tr>
                <th class="text-left">Кол-во наборов</th>
                <th class="text-left">Общее время</th>
                <th class="text-left">Баланс</th>
                <th class="text-left">Очистить</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ hmcall }}</td>
                <td>{{ alltime }}</td>
                <td>
                  <v-text-field
                    v-model="balans"
                    :label="lastBalans"
                    type="number"
                  >
                    <template v-slot:append>
                      <v-btn
                        depressed
                        tile
                        color="primary"
                        class="ma-0"
                        @click="setBalans"
                        :disabled="userid == 0 || balans == 0"
                      >
                        Записать
                      </v-btn>
                    </template></v-text-field
                  >
                </td>
                <td></td>
              </tr></tbody></template></v-simple-table></v-col
    ></v-row>
  </div>
</template>
                </td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
        <v-progress-linear
          :active="loading"
          :indeterminate="loading"
          color="deep-purple accent-4"
        ></v-progress-linear>
        <v-card class="mt-5">
          <v-card-title primary-title>
            <h3>Статусы</h3>
          </v-card-title>
          <v-card-actions>
            <div>
              <template v-for="(i, ix) in StatusesDay">
                <div class="status_wrp" :key="ix">
                  <span :style="{ background: i[1][0].color }">{{ i[0] }}</span
                  ><b>{{ i[1].length }}</b>
                </div>
              </template>
            </div>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
import _ from "lodash";
export default {
  data: () => ({
    expanded: [],
    singleExpand: false,
    tableHeaders: [
      { text: "ФИО", value: "fio" },
      { text: "Кол-во лидов", value: "hmlids" },
      { text: "Кол-во наборов", value: "hmcalls" },
      { text: "Общее время", value: "alltime" },
      { text: "Баланс", value: "balans" },
      { text: "Очистить", value: "deleteData" },
    ],
    tableData: [],
    users: [],
    userid: 0,
    balans: "",
    lastBalans: "",
    StatusesDay: [],
    hmcall: "",
    alltime: "",
    loading: false,
    user_fio: "",
    dialog: false,
  }),
  mounted: function () {
    this.getUsers();
    this.getData();
  },
  computed: {},
  methods: {
    delDataUser(user_id) {
      let self = this;
      axios
        .delete("/api/delDataUser/" + user_id)
        .then((res) => {
          // self.getData(self.userid);
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/getusers")
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
        })
        .catch((error) => console.log(error));
    },
    getData(user_id) {
      let self = this;
      self.userid = user_id;
      self.hmcall = "";
      self.alltime = "";
      self.balans = 0;
      self.lastBalans = "";
      self.user_fio = "";
      self.loading = true;

      axios
        .get("/api/getDataDay/" + user_id)
        .then((res) => {
          self.loading = false;
          self.tableData = res.data;
        })
        .catch((error) => console.log(error));
    },
    setBalans() {
      let self = this;
      let data = {};
      data.balans = self.balans;
      data.id = self.userid;
      axios
        .post("/api/setBalans", data)
        .then((res) => {
          self.lastBalans = (
            parseInt(self.lastBalans) + parseInt(self.balans)
          ).toString();
          self.balans = "";
        })
        .catch((error) => console.log(error));
    },
  },
  computed: {
    group() {
      return _.uniqBy(this.tableData, "group_id").filter(
        (i) => i.group_id == i.id
      );
    },
  },
};
</script>

<style scoped>
.status_wrp span {
  padding: 5px;
  word-break: break-all;
}

.status_wrp b {
  padding: 0 8px;
}

.status_wrp {
  margin-right: 15px;
  border: 1px solid grey;
  padding: 3px 0;
  margin-bottom: 15px;
  display: inline-block;
}
</style>
