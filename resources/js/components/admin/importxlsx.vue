<template>
  <div>
    <v-snackbar v-model="snackbar" top right timeout="-1">
      <v-card-text v-html="message"></v-card-text>
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>

    <v-content>
      <v-row>
        <v-col cols="2">
          <v-select
            v-model="selectedProvider"
            :items="providers"
            label="Провайдер"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>
        <v-col cols="3" v-if="selectedProvider">
          <v-file-input
            v-model="files"
            ref="fileupload"
            label="загрузить Excel"
            show-size
            truncate-length="24"
            @change="onFileChange"
          ></v-file-input>
        </v-col>
        <v-col cols="3">
          <v-select
            v-model="selectedStatus"
            :items="statuses"
            label="Статус"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>
        <v-col cols="4">
          <v-textarea v-model="message" label="Сообщение" rows="1"></v-textarea>
        </v-col>
      </v-row>
      <v-progress-linear
        :active="loading"
        indeterminate
        color="purple"
      ></v-progress-linear>
      <v-row v-if="table.length && files">
        <v-col cols="9">
          <v-simple-table id="loadedTable">
            <template v-slot:default>
              <thead>
                <tr>
                  <th v-for="el in table[0].length" :key="el">
                    <v-select
                      :items="[
                        '',
                        'name',
                        'lastname',
                        'email',
                        'tel',
                        'afilyator',
                        'text',
                      ]"
                      outlined
                      @change="makeHeader"
                    >
                    </v-select>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, ix) in table" :key="ix">
                  <td v-for="(it, i) in item" :key="i">{{ it }}</td>
                </tr>
              </tbody>
            </template>
          </v-simple-table>
        </v-col>
        <v-col cols="3" v-if="header.length > 2">
          <v-card height="100%" class="pa-5">
            Укажите пользователя для лидов
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
    </v-content>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";

export default {
  props: ["user"],
  data: () => ({
    loading: false,
    message: "",
    snackbar: false,
    providers: [],
    selectedProvider: 0,
    users: [],
    statuses: [],
    selectedStatus: 0,
    files: null,
    table: [],
    header: [],
    userid: null,
    related_user: [],
    tab: 0,
  }),

  mounted() {
    this.getProviders();
    this.getStatuses();
  },
  watch: {
    selectedProvider: function (newval) {
      this.users = [];
      this.related_user = [];
      let rel_user = this.providers.find(
        (p) => p.id == newval
      ).related_users_id;
      if (rel_user.length > 2) {
        this.related_user = JSON.parse(rel_user);
        this.getUsers();
      }
    },
  },
  methods: {
    getheader() {
      setTimeout(() => {
        this.header = document
          .querySelector("#loadedTable table")
          .tHead.innerText.split("\t")
          .map((i) => i.replaceAll(/[\W_]+/g, ""));
      }, 300);
    },
    makeHeader() {
      this.getheader();
    },
    onFileChange(f) {
      if (f == null) return;
      const ftype = [
        "application/vnd.ms-excel",
        "application/vnd-xls",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "application/xls",
        "application/x-xls",
        "application/vnd.ms-excel",
        "application/msexcel",
        "application/x-msexcel",
        "application/x-ms-excel",
        "application/x-excel",
        "application/x-dos_ms_excel",
        "application/excel",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      }
    },
    createInput(f) {
      let vm = this;
      var reader = new FileReader();
      reader.onload = function (e) {
        var data = e.target.result,
          fixedData = vm.fixdata(data),
          workbook = XLSX.read(btoa(fixedData), { type: "base64" }),
          firstSheetName = workbook.SheetNames[0],
          worksheet = workbook.Sheets[firstSheetName];
        vm.table = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      };
      reader.readAsArrayBuffer(f);
    },
    fixdata(data) {
      var o = "",
        l = 0,
        w = 10240;
      for (; l < data.byteLength / w; ++l)
        o += String.fromCharCode.apply(
          null,
          new Uint8Array(data.slice(l * w, l * w + w))
        );
      o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w)));
      return o;
    },
    putSelectedLidsDB() {
      this.loading = true;

      let json = {};
      //make json from header and body
      json = this.table.map((_, row) =>
        this.header.reduce(
          (json, key, col) => ({
            ...json,
            [key]: this.table[row][col] ?? "",
          }),
          {}
        )
      );
      //remove empty columns
      json = Object.entries(json).map(
        (e) =>
          (e[1] = Object.fromEntries(
            Object.entries(e[1]).filter((el) => el[0])
          ))
      );

      let self = this;
      self.loading = true;
      let send = {};
      send.user_id = this.userid;
      send.provider_id = this.selectedProvider;
      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      send.message = self.message;
      send.data = json;
      axios
        .post("api/Lid/newlids", send)
        .then(function (response) {
          self.getUsers();
          self.loading = false;
          self.files = [];
          self.table = [];
          // save to imports db
          //======================
          let info = {};

          info.start = response.data.date_start
            .substring(0, 19)
            .replace("T", " ");
          info.end = response.data.date_end.substring(0, 19).replace("T", " ");
          info.provider_id = self.selectedProvider;
          info.user_id = self.user.id;
          info.message = self.message;
          // console.log(info);
          axios
            .post("api/imports", info)
            .then(function (response) {})
            .catch(function (error) {
              console.log(error);
            });
        })

        .catch(function (error) {
          console.log(error);
        });
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id, related_users_id }) => ({
            name,
            id,
            related_users_id,
          }));
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;
      this.loading = true;
      axios
        .post("/api/getusers", self.related_user)
        .then((res) => {
          self.users = res.data.map(({ name, id, role_id, fio, hmlids }) => ({
            name,
            id,
            role_id,
            fio,
            hmlids,
          }));
          self.loading = false;
        })
        .catch((error) => console.log(error));
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ uname, name, id, color, order }) => ({
            uname,
            name,
            id,
            color,
            order,
          }));
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
