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

    <v-container>
      <v-row>
        <v-col>
          <v-radio-group v-model="email_tel" row>
            <v-radio label="email" value="email"></v-radio>
            <v-radio label="телефон" value="tel"></v-radio>
          </v-radio-group>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="6">
          <v-textarea
            class="border pa-3"
            v-model="list_email"
            label="Почтовые адреса или телефоны "
          ></v-textarea>
        </v-col>
        <v-col cols="6">
          <v-file-input
            v-model="file_emails"
            label="загрузить txt"
            show-size
            truncate-length="24"
            @change="onFileChange"
          ></v-file-input>
          <v-select
            v-model="printfields"
            :items="printfield"
            label="Поля для отчёта"
            multiple
          ></v-select>
          <v-btn @click="checkEmails" v-if="list_email" class="primary"
            >Проверить<v-progress-circular
              v-if="loading"
              indeterminate
              color="amber"
            ></v-progress-circular
          ></v-btn>
        </v-col>
        <v-col cols="12" v-if="duplicate_leads.length">
          <v-btn outlined rounded @click="exportXlsx" class="border">
            <v-icon left> mdi-file-excel </v-icon>
            Скачать таблицу
          </v-btn>
          <v-data-table
            :headers="duplicate_leads_headers"
            item-key="id"
            :items="duplicate_leads"
            ref="duplicatetable"
          >
          </v-data-table>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
export default {
  data: () => ({
    list_email: "",
    file_emails: [],
    in_db: [],
    out_db: [],
    message: "",
    snackbar: false,
    loading: false,
    email_tel: "email",
    duplicate_leads_headers: [
      { text: "Афилятор", value: "afilyator" },
      { text: "Емаил", value: "email" },
      { text: "Имя", value: "name" },
      { text: "Оффис", value: "office_name" },
      { text: "Провайдер", value: "provider_name" },
      { text: "Статус", value: "status_name" },
      { text: "Тел", value: "tel" },
      { text: "Оператор", value: "user_name" },
      { text: "Создан", value: "created" },
    ],
    printfield: [
      "tel",
      "name",
      "created",
      "updated",
      "status_id",
      "status_name",
      "email",
      "user_name",
      "provider_name",
      "afilyator",
      "office_name",
    ],
    printfields: [
      "tel",
      "name",
      "created",
      "updated",
      "status_id",
      "status_name",
      "email",
      "user_name",
      "provider_name",
      "afilyator",
      "office_name",
    ],
    duplicate_leads: [],
    files: [],
  }),
  computed: {
    // filteredItems() {
    //   let reg = new RegExp("^" + this.filtertel);
    //   return this.parse_csv.filter((i) => {
    //     return !this.filtertel || reg.test(i.tel);
    //   });
    // },
  },
  methods: {
    exportXlsx() {
      const self = this;
      // const obj = _.groupBy(self.filteredItems, "status");
      // const lidsByStatus = Array.from(Object.keys(obj), (k) => [
      //   `${k}`,
      //   obj[k],
      // ]);

      var wb = XLSX.utils.book_new(); // make Workbook of Excel
      window["list"] = XLSX.utils.json_to_sheet(self.duplicate_leads);
      XLSX.utils.book_append_sheet(wb, window["list"], "duplicate_emailes");
      const unique = self.out_db.map((i) => ({ email: i }));
      window["unique"] = XLSX.utils.json_to_sheet(unique);
      XLSX.utils.book_append_sheet(wb, window["unique"], "unique");

      // export Excel file
      XLSX.writeFile(
        wb,
        "dupl_" + self.email_tel + new Date().toDateString() + ".xlsx"
      ); // name of the file is 'book.xlsx'
    },
    checkEmails() {
      let vm = this;
      vm.loading = true;
      vm.snackbar = false;
      vm.message = "";
      vm.duplicate_leads = [];
      vm.in_db = [];
      vm.out_db = [];
      let data = {};
      data.emails = vm.list_email
        .replace(/[\r]/gm, "")
        .replaceAll(" ", "")
        .split("\n");
      data.check = 1;
      data.email_tel = vm.email_tel;
      data.printfields = vm.printfields;
      axios
        .post("api/checkEmails", data)
        .then(function (res) {
          vm.in_db = res.data.emails.filter((n) => n);

          vm.out_db = [
            ...new Set(
              data.emails.filter((i) => !vm.in_db.includes(i.toLowerCase()))
            ),
          ];
          vm.message =
            "Уникальных: " +
            vm.out_db.length +
            "<br>Дубликатов: " +
            vm.in_db.length;
          vm.snackbar = true;
          vm.duplicate_leads = res.data.leads;

          vm.loading = false;
          vm.list_email = "";
          vm.files = [];
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    onFileChange(f) {
      if (f == null) return;
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
    txt2Array(txt) {
      let vm = this;
      vm.list_email = txt;
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
          vm.parse_txt_emails = vm.txt2Array(vm.fileinput);
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
