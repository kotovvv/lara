<template>
  <div>
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
              {{user_fio}}
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
                <td>
                  <template>
  <v-row justify="center">
    <v-dialog
      v-model="dialog"
      persistent
      max-width="490"
    >
      <template v-slot:activator="{ on, attrs }">
        <v-btn
          color="primary"
          dark
          v-bind="attrs"
          v-on="on"
          :disabled="userid == 0"
        >
          Стереть
        </v-btn>
      </template>
      <v-card>
        <v-card-title class="text-h5">
          Стереть все данные пользователя?
        </v-card-title>
        <v-card-text>Балансы, телефонные звонки будут удалены без возможности восстановления!</v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="green darken-1"
            text
            @click="dialog = false"
          >
            Отмена
          </v-btn>
          <v-btn
            color="blue darken-1"
            text
            @click="dialog = false;delDataUser()"
          >
            Удалить
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-row>
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
    users: [],
    userid: 0,
    balans: "",
    lastBalans: "",
    StatusesDay: [],
    hmcall: "",
    alltime: "",
    loading: false,
    user_fio:'',
    dialog:false,
  }),
  mounted: function () {
    this.getUsers();
  },
   computed: {

   },
  methods: {
    delDataUser(){
      let self = this;
            axios
        .delete("/api/delDataUser/"+self.userid)
        .then((res) => {
self.getData(self.userid)
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
      self.userid = user_id
      self.hmcall = "";
      self.alltime = "";
      self.balans = 0;
      self.lastBalans = "";
      self.user_fio = ''
      self.loading = true;

      axios
        .get("/api/getDataDay/" + user_id)
        .then((res) => {
          self.loading = false;
          self.lastBalans = _.sumBy(res.data.balans, "balans").toString();
          self.hmcall = res.data.calls[0].count;
          self.alltime = res.data.calls[0].duration;
          self.StatusesDay = Object.entries(_.groupBy(res.data.statuses,'name'));
          self.user_fio = _.filter(self.users,{id:self.userid})[0].fio
        })
        .catch((error) => console.log(error));
    },
    setBalans(){
      let self = this;
      let data = {}
      data.balans = self.balans
      data.id = self.userid
            axios
        .post("/api/setBalans", data)
        .then((res) => {
self.lastBalans = (parseInt(self.lastBalans)+parseInt(self.balans)).toString()
self.balans = ''
        })
        .catch((error) => console.log(error));
    }
  },
    computed: {
    group() {
      return _.uniqBy(this.users, "group_id").filter((i) => i.group_id > 0);
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