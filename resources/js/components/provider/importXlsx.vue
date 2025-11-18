<template>
  <v-container fluid>
    <v-snackbar v-model="snackbar" top right timeout="-1">
      <div v-html="message"></div>
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>

    <v-row>
      <v-col cols="3">
        <v-file-input v-model="files" ref="fileupload" label="Загрузить XLSX" show-size truncate-length="24"
          @change="onFileChange" accept=".xlsx,.xls"></v-file-input>
      </v-col>
      <v-spacer></v-spacer>
      <v-col cols="2" class="text-right">
        <v-btn color="primary" @click="uploadLids" v-if="table.length && !loading" :disabled="!isTelMapped">
          Upload
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="table.length && !isTelMapped" type="warning" dense class="mt-2">
      Обязательно укажите колонку "tel" (телефон)!
    </v-alert>

    <v-progress-linear :active="loading" :indeterminate="loading" color="deep-purple accent-4"></v-progress-linear>

    <v-row v-if="table.length && files">
      <v-col cols="12">
        <v-alert type="info" dense v-if="table.length > previewLimit">
          Показано первых {{ previewLimit }} строк из {{ table.length }}
        </v-alert>

        <v-simple-table id="loadedTable">
          <template v-slot:default>
            <thead>
              <tr>
                <th v-for="(col, index) in table[0].length" :key="index">
                  <v-select v-model="header[index]" :items="columnOptions" outlined dense hide-details
                    placeholder="-- не использовать --" @change="onHeaderChange">
                    <template v-slot:selection="{ item }">
                      <span :class="{ 'error--text font-weight-bold': item === 'tel' }">
                        {{ item || '-- пропустить --' }}
                      </span>
                    </template>
                    <template v-slot:item="{ item }">
                      <span :class="{ 'error--text font-weight-bold': item === 'tel' }">
                        {{ item || '-- пропустить --' }}
                      </span>
                    </template>
                  </v-select>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, rowIndex) in previewRows" :key="rowIndex">
                <td v-for="(cell, cellIndex) in row" :key="cellIndex">
                  {{ cell }}
                </td>
              </tr>
            </tbody>
          </template>
        </v-simple-table>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";

export default {
  props: {
    user: {
      type: Object,
      required: true
    }
  },
  data: () => ({
    loading: false,
    message: "",
    snackbar: false,
    files: null,
    table: [],
    header: [],
    columnOptions: ["", "name", "email", "tel", "afilyator"],
    previewLimit: 50,
  }),
  computed: {
    isTelMapped() {
      return this.header.includes("tel");
    },
    previewRows() {
      return this.table.slice(0, this.previewLimit);
    }
  },
  methods: {
    resetState() {
      this.files = null;
      this.table = [];
      this.header = [];
      this.loading = false;
      this.message = "";
      this.snackbar = false;
    },
    onHeaderChange() {
      // Ensure header array is updated
      this.$forceUpdate();
    },
    onFileChange(f) {
      if (f == null) {
        this.resetState();
        return;
      }

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
        this.message = "Неверный формат файла. Используйте .xlsx или .xls";
        this.snackbar = true;
        this.files = null;
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

        vm.loading = true;
        setTimeout(() => {
          vm.table = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

          // Initialize header array with empty values
          if (vm.table.length > 0) {
            vm.header = new Array(vm.table[0].length).fill("");
          }

          vm.loading = false;
        }, 100);
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
    prepareXlsxData() {
      let json = [];
      let obj = {};

      this.table.forEach((row) => {
        obj = {};
        this.header.forEach(function (key, index) {
          if (key != "") {
            if (!obj[key]) {
              obj[key] = row[index];
            } else {
              // Concatenate if duplicate column mapping (e.g., name + lastname)
              obj[key] += ` ${row[index]}`;
            }
          }
        });

        // Only add rows that have at least tel field
        if (obj.tel) {
          json.push(obj);
        }
      });

      return json;
    },
    uploadLids() {
      let self = this;

      if (!self.isTelMapped) {
        self.message = "Обязательно укажите колонку 'tel' (телефон)!";
        self.snackbar = true;
        return;
      }

      const lidsData = self.prepareXlsxData();

      if (lidsData.length === 0) {
        self.message = "Нет данных для загрузки. Убедитесь, что файл содержит номера телефонов.";
        self.snackbar = true;
        return;
      }

      let data = {};
      self.loading = true;
      self.message = "";
      data.provider_id = self.user.id;
      data.lids = lidsData;

      axios
        .post("api/provider_importlid", data)
        .then(function (res) {
          if (res.status == 200) {
            self.snackbar = true;
            self.message = res.data;
            self.loading = false;
            // Reset after successful upload
            self.resetState();
          }
          self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
          self.message = "Ошибка при загрузке лидов";
          self.snackbar = true;
          self.loading = false;
        });
    },
  },
};
</script>

<style scoped>
#loadedTable .v-data-table__wrapper {
  overflow: auto;
  max-height: 60vh;
}

#loadedTable th {
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}
</style>
