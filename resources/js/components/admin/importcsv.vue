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
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
import _ from "lodash";

export default {
  data: () => ({
    message: "",
    snackbar: false,
    providers: [],
    selectedProvider: 0,
    imports: {},
    users: [],
    statuses: [],
    selectedStatus: 0,
    files: null,
  }),

  mounted() {
    this.getImports();
    this.getProviders();
    this.getStatuses();
  },

  methods: {
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
      } else {
        this.files = [];
      }
    },
    createInput(f) {
      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        var vm = this;
        reader.onload = (e) => {
          resolve((vm.fileinput = reader.result));
        };
        reader.readAsText(f);
      });
      promise.then(
        (result) => {
          let vm = this;
          // vm.parse_csv = vm.csvJSON(this.fileinput)
          let workbook = XLSX.read(vm.fileinput, { type: "binary" });
          console.log("workbook1");
          console.log(workbook);
          console.log("SheetNames");
          console.log(workbook.SheetNames);
        },
        (error) => {
          console.log(error);
        }
      );
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
    getImports() {
      let self = this;
      axios
        .get("/api/imports")
        .then((res) => {
          self.imports = res.data.map(
            ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
            }) => ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
            })
          );
          if (self.$attrs.user.group_id > 0) {
            self.imports = self.imports.filter(
              (i) => i.group_id == self.$attrs.user.group_id
            );
          }
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;

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
        })
        .catch((error) => console.log(error));
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
