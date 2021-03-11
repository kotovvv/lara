<template>
  <div>
    <v-row>
      <v-col cols="3">
        <v-select
          :items="providers"
          label="Поставщик"
          item-text="name"
          item-value="id"
        ></v-select>
      </v-col>
      <v-col cols="3">
        <v-file-input
          v-model="files"
          ref="fileupload"
          label="Загрузите CSV"
          show-size
          truncate-length="24"
          @change="onFileChange"
        ></v-file-input>
      </v-col>
    </v-row>
    <v-main>
      <v-row>
        <v-col cols="8">
          <v-card>
            <v-card-title>
              <v-text-field
                v-model="search"
                append-icon="mdi-magnify"
                label="Поиск"
                single-line
                hide-details
              ></v-text-field>
            </v-card-title>
            <v-data-table
              :headers="headers"
              :items="parse_csv"
              :search="search"
              :single-select=false
              item-key="tel"
              show-select

            ></v-data-table>
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
    providers: [],
    files: [],
    search: "",
    headers: [
      { text: "Tel.",align: "start",value: "tel" },
      { text: "Email", value: "email" },
      { text: "Name", value: "fio" },
    ],
    parse_header: [],
    parse_csv: [],
    sortOrders: {},
    sortKey: "tel",
  }),
  mounted() {
    this.getProviders();
  },
  methods: {
    getProviders() {
      axios
        .get("/api/provider")
        .then((res) => {
          this.providers = res.data.map(({ name, id }) => ({ name, id }));
        })
        .catch((error) => console.log(error));
    },
    onFileChange(f) {
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
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
      var headers = lines[0].split(",");
      headers = ['tel','email','fio'];
      // vm.parse_header = lines[0].split(",");
      // lines[0].split(",").forEach(function (key) {
      //   vm.sortOrders[key] = 1;
      // });

      lines.map(function (line, indexLine) {
        if (indexLine < 1) return; // Jump header line

        var obj = {};
        line = line.trim();
        var currentline = line.split(",");

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
          // console.log(this.fileinput);
          // reader.onload = function(event) {

          vm.parse_csv = vm.csvJSON(this.fileinput);
          console.log(vm.parse_csv);

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
