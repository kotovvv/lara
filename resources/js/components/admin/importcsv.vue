<template>
  <div>
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
          label="загрузить CSV"
          show-size
          truncate-length="24"
          @change="onFileChange"
        ></v-file-input>
      </v-col>
      <v-col cols="3" v-if="parse_csv.length">
        <v-select
          v-model="selectedStatus"
          :items="statuses"
          label="Статус"
          item-text="name"
          item-value="id"
        ></v-select>
      </v-col>
      <v-col cols="4" v-if="parse_csv.length">
        <v-textarea v-model="message" label="Сообщение" rows="1"></v-textarea>
      </v-col>
    </v-row>

    <v-main v-if="parse_csv.length && files">
      <v-row>
        <v-col cols="8">
          <v-card>
            <v-container>
              <v-row>
                <v-col cols="4">
                  <v-card-title>
                    <v-text-field
                      v-model="search"
                      append-icon="mdi-magnify"
                      label="Поиск"
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
                      label="Начальные цифры"
                      single-line
                      hide-details
                    ></v-text-field>
                  </v-card-title>
                </v-col>
              </v-row>
            </v-container>
            <v-data-table
              v-model.lazy.trim="selected"
              :headers="headers"
              :search="search"
              :single-select="false"
              item-key="tel+afilyator"
              show-select
              @click:row="clickrow"
              :items="filteredItems"
              ref="datatable"
              :loading="loading"
            ></v-data-table>
          </v-card>
        </v-col>
        <v-col cols="4">
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
    </v-main>
    <v-row v-else>
      <v-col>
        <v-data-table
          :headers="import_headers"
          item-key="id"
          :items="imports"
          ref="importtable"
        >
          <template v-slot:item.id="{ item }"> </template>
        </v-data-table>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data: () => ({
    message: "",
    loading: false,
    userid: null,
    users: [],
    providers: [],
    statuses: [],
    imports: [],
    selectedStatus: 8,
    selectedProvider: 0,
    related_user: [],
    selected: [],
    files: [],
    search: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Тел.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
    ],
    import_headers: [
      { text: "", value: "id" },
      { text: "Дата час початку", value: "start" },
      { text: "Дата час закінчення", value: "end" },
      { text: "Поставщик", value: "provider" },
      { text: "Хто імпортував", value: "user" },
      { text: "Коментар", value: "message" },
    ],
    parse_header: [],
    parse_csv: [],
    sortOrders: {},
    sortKey: "tel",
  }),
  watch:{
    selectedProvider: function(newval){
      this.users = []
      this.related_user = []
      let rel_user = this.providers.find((p) => p.id == newval).related_users_id
      if (rel_user.length > 2 ) {
this.related_user = JSON.parse(rel_user)
this.getUsers();
      }
    },
  },
  mounted() {
    this.getImports();
    this.getProviders();
    // this.getUsers();
    this.getStatuses();
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.parse_csv.filter((i) => {
        return !this.filtertel || reg.test(i.tel);
      });
    },
  },
  methods: {
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    putSelectedLidsDB() {
      let start = new Date().toJSON().slice(0, 19).replace("T", " ");
      let self = this;
      self.loading = true;
      let send = {};
      send.user_id = this.userid;
      send.provider_id = this.selectedProvider;
      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.$refs.datatable.items.length > 0) {
        if (this.selected.legth) send.data = this.selected;
        else send.data = this.$refs.datatable.items;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            // console.log(response);
            if (self.selected.length) {
              self.parse_csv = self.parse_csv.filter(
                (ar) => !self.selected.find((rm) => rm.tel === ar.tel)
              );
            } else {
              self.parse_csv = [];
            }
            self.selected = [];
            self.getUsers();
            self.loading = false;
            self.files = [];
            // save to imports db
            //======================
            let info = {};

            info.start = start;
            info.end = new Date().toJSON().slice(0, 19).replace("T", " ");
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports", info)
              .then(function (response) {
                // console.log(response);
              })
              .catch(function (error) {
                console.log(error);
              });
            //====================
          })
          .catch(function (error) {
            console.log(error);
          });
      } else if (
        (this.search !== "" || this.filtertel !== "") &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            // console.log(response);
            self.parse_csv = self.parse_csv.filter(
              (ar) =>
                !self.$refs.datatable.$children[0].filteredItems.find(
                  (rm) => rm.tel === ar.tel
                )
            );
            self.getUsers();
            self.search = "";
            self.filtertel = "";
            self.loading = false;
            // save to imports db
            //======================
            let info = {};

            info.start = start;
            info.end = new Date().toJSON().slice(0, 19).replace("T", " ");
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports", info)
              .then(function (response) {
                // console.log(response);
              })
              .catch(function (error) {
                console.log(error);
              });
            //====================
          })
          .catch(function (error) {
            console.log(error);
          });
      }
      if (this.parse_csv.length == 0) {
        this.files = [];
        this.selectedProvider = "";
      }
      this.userid = null;
      this.$refs.radiogroup.lazyValue = null;
      this.getUsers();
    },
    clickrow() {
      console.log("You can click on row))");
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id, related_users_id}) => ({ name, id, related_users_id }));
        })
        .catch((error) => console.log(error));
    },
    getImports() {
      let self = this;
      axios
        .get("/api/imports")
        .then((res) => {
          self.imports = res.data.map(
            ({ id, start, end, provider, user, message }) => ({
              id,
              start,
              end,
              provider,
              user,
              message,
            })
          );
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;

      axios
        .post("/api/getusers",self.related_user)
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
    onFileChange(f) {
      if (f == null) return
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
        "text/plain",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      } else {
        this.files = [];
      }
    },
    csvJSON(csv) {
      var vm = this;
      var lines = csv.split("\n");
      var result = [];
      var headers = lines[0].split(";");
      headers = ["name", "email", "tel", "afilyator"];
      // vm.parse_header = lines[0].split(",");
      // lines[0].split(",").forEach(function (key) {
      //   vm.sortOrders[key] = 1;
      // });

      lines.map(function (line, indexLine) {
        if (indexLine < 1) return; // Jump header line

        var obj = {};
        line = line.trim();
        var currentline = line.split(";");

        headers.map(function (header, indexHeader) {
          obj[header] = currentline[indexHeader];
        });

        result.push(obj);
      });

      result.pop(); // remove the last item because undefined values

      return result; // JavaScript object
    },
    createInput(file) {
      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        var vm = this;
        reader.onload = (e) => {
          resolve((vm.fileinput = reader.result));
        };
        reader.readAsText(file);
      });

      promise.then(
        (result) => {
          let vm = this;
          /* handle a successful result */
          // console.log(vm.csvJSON(this.fileinput));
          // reader.onload = function(event) {
          // arr.filter((v,i,a)=>a.findIndex(t=>(t.id === v.id))===i)
          vm.parse_csv = vm
            .csvJSON(this.fileinput)
            .filter((v, i, a) => a.findIndex((t) => t.afilyator+t.tel == v.afilyator+v.tel) === i);
          // console.log(vm.parse_csv);

          // };
        },
        (error) => {
          /* handle an error */
          console.log(error);
        }
      );
    },
  },
};
</script>
